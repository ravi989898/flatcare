import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../bills/widgets/bill_ui.dart';
import '../data/society_admin_repository.dart';

/// Society admin: water readings, step 2 - one block's flats for a month.
/// Type each flat's meter reading; the units and the bill it will create
/// are shown as you type. Saving creates the bills (and notifies those
/// residents). A reading can be corrected until its bill is paid - paid
/// flats are shown locked.
class WaterReadingEntryScreen extends ConsumerStatefulWidget {
  const WaterReadingEntryScreen({super.key, required this.blockId, required this.month, this.blockName});

  final int blockId;
  final String month; // yyyy-MM
  final String? blockName;

  @override
  ConsumerState<WaterReadingEntryScreen> createState() => _WaterReadingEntryScreenState();
}

class _WaterReadingEntryScreenState extends ConsumerState<WaterReadingEntryScreen> {
  final Map<int, TextEditingController> _current = {};
  final Map<int, TextEditingController> _previous = {};
  bool _saving = false;
  bool _dirty = false;

  (String, int) get _key => (widget.month, widget.blockId);

  @override
  void dispose() {
    for (final c in [..._current.values, ..._previous.values]) {
      c.dispose();
    }
    super.dispose();
  }

  /// Controllers are (re)filled from the server's rows whenever they load.
  void _sync(WaterSheet sheet) {
    for (final row in sheet.rows) {
      _current.putIfAbsent(row.flatId, () => TextEditingController(text: _fmt(row.currentReading)));
      _previous.putIfAbsent(row.flatId, () => TextEditingController(text: _fmt(row.previousReading)));
    }
  }

  String _fmt(double? value) {
    if (value == null) return '';
    return value == value.roundToDouble() ? value.toStringAsFixed(0) : value.toString();
  }

  double? _parse(TextEditingController? c) => c == null || c.text.trim().isEmpty ? null : double.tryParse(c.text.trim());

  Future<void> _save(WaterSheet sheet) async {
    FocusScope.of(context).unfocus();
    final messenger = ScaffoldMessenger.of(context);
    final readings = <Map<String, dynamic>>[];

    for (final row in sheet.rows) {
      if (row.locked) continue;
      final current = _parse(_current[row.flatId]);
      if (current == null) continue;

      final previous = row.hasHistory ? row.previousReading : _parse(_previous[row.flatId]);
      if (previous == null) {
        messenger.showSnackBar(SnackBar(content: Text('Flat ${row.flatNumber}: enter the previous reading (first reading for this flat).')));
        return;
      }
      if (current < previous) {
        messenger.showSnackBar(SnackBar(content: Text('Flat ${row.flatNumber}: current reading is lower than the previous one (${_fmt(previous)}).')));
        return;
      }

      readings.add({'flat_id': row.flatId, 'previous_reading': previous, 'current_reading': current});
    }

    if (readings.isEmpty) {
      messenger.showSnackBar(const SnackBar(content: Text('Enter at least one current reading.')));
      return;
    }

    setState(() => _saving = true);
    try {
      final message = await ref.read(societyAdminRepositoryProvider).saveWaterReadings(widget.month, readings);
      _dirty = false;
      ref.invalidate(waterSheetProvider(_key));
      messenger.showSnackBar(SnackBar(content: Text(message), backgroundColor: AppTheme.statusPaid));
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      messenger.showSnackBar(const SnackBar(content: Text("Couldn't save. Check your internet and try again.")));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<bool> _confirmDiscard() async {
    if (!_dirty) return true;

    final leave = await showDialog<bool>(
      context: context,
      builder: (dialog) => AlertDialog(
        title: const Text('Discard readings?'),
        content: const Text("The readings you typed haven't been saved."),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialog).pop(true), child: const Text('Discard')),
          FilledButton(onPressed: () => Navigator.of(dialog).pop(false), child: const Text('Keep editing')),
        ],
      ),
    );

    return leave ?? false;
  }

  @override
  Widget build(BuildContext context) {
    final sheet = ref.watch(waterSheetProvider(_key));
    final monthLabel = DateFormat('MMMM yyyy').format(DateTime.parse('${widget.month}-01'));

    return PopScope(
      canPop: !_dirty,
      onPopInvokedWithResult: (didPop, _) async {
        if (!didPop && await _confirmDiscard() && context.mounted) {
          _dirty = false;
          Navigator.of(context).pop();
        }
      },
      child: Scaffold(
        backgroundColor: AppTheme.pageBackground,
        bottomNavigationBar: sheet.valueOrNull == null
            ? null
            : SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    onPressed: _saving ? null : () => _save(sheet.value!),
                    icon: _saving
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                        : const Icon(Icons.save_rounded),
                    label: Text(_saving ? 'Saving…' : 'Save & Generate Bills', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                  ),
                ),
              ),
        body: Column(
          children: [
            BillHeaderBar(title: '${widget.blockName ?? 'Block'} · $monthLabel'),
            Expanded(
              child: AsyncView<WaterSheet>(
                value: sheet,
                onRetry: () => ref.invalidate(waterSheetProvider(_key)),
                builder: (context, data) {
                  _sync(data);
                  if (data.rows.isEmpty) {
                    return const EmptyState(title: 'No flats', message: 'This block has no active flats.', icon: Icons.home_outlined);
                  }

                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 16),
                    itemCount: data.rows.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 6),
                    itemBuilder: (context, index) {
                      final row = data.rows[index];
                      return _ReadingCard(
                        row: row,
                        rates: data.rates,
                        current: _current[row.flatId]!,
                        previous: _previous[row.flatId]!,
                        onChanged: () => setState(() => _dirty = true),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReadingCard extends StatelessWidget {
  const _ReadingCard({required this.row, required this.rates, required this.current, required this.previous, required this.onChanged});

  final WaterReadingRow row;
  final BillingRates rates;
  final TextEditingController current;
  final TextEditingController previous;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    final prev = row.hasHistory ? row.previousReading : double.tryParse(previous.text.trim());
    final cur = double.tryParse(current.text.trim());
    final units = prev != null && cur != null && cur >= prev ? cur - prev : null;
    final invalid = prev != null && cur != null && cur < prev;

    final statusColor = row.locked ? AppTheme.statusPaid : (row.isEntered ? AppColors.primary : AppColors.textMuted);

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 10, 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border(left: BorderSide(color: statusColor, width: 3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('Flat ${row.flatNumber}', style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
              const Spacer(),
              if (row.locked)
                const _Tag(icon: Icons.lock_rounded, label: 'Paid · locked', color: AppTheme.statusPaid)
              else if (row.isEntered)
                const _Tag(icon: Icons.check_rounded, label: 'Billed · can edit', color: AppColors.primary)
              else if (!row.hasHistory)
                const _Tag(icon: Icons.fiber_new_rounded, label: 'First reading', color: AppTheme.statusPending),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: row.hasHistory || row.locked
                    ? _ReadOnlyValue(label: 'Previous', value: row.previousReading)
                    : _NumberField(controller: previous, label: 'Previous', onChanged: onChanged),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: row.locked
                    ? _ReadOnlyValue(label: 'Current', value: row.currentReading)
                    : _NumberField(controller: current, label: 'Current', onChanged: onChanged, error: invalid),
              ),
            ],
          ),
          const SizedBox(height: 6),
          if (invalid)
            const Text('Current reading is lower than the previous one.', style: TextStyle(fontSize: 11.5, color: AppTheme.statusDue, fontWeight: FontWeight.w600))
          else if (row.locked && row.billAmount != null)
            Text('Bill ${formatCurrency(row.billAmount!)} · paid', style: const TextStyle(fontSize: 12, color: AppTheme.statusPaid, fontWeight: FontWeight.w700))
          else if (units != null)
            Text(
              '${_trim(units)} units  →  Bill ${formatCurrency(rates.billFor(units))}',
              style: const TextStyle(fontSize: 12, color: AppColors.textPrimary, fontWeight: FontWeight.w700),
            )
          else
            const Text('Enter the meter reading', style: TextStyle(fontSize: 11.5, color: AppColors.textMuted)),
        ],
      ),
    );
  }

  String _trim(double v) => v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toStringAsFixed(2);
}

class _NumberField extends StatelessWidget {
  const _NumberField({required this.controller, required this.label, required this.onChanged, this.error = false});

  final TextEditingController controller;
  final String label;
  final VoidCallback onChanged;
  final bool error;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      textInputAction: TextInputAction.next,
      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
      onChanged: (_) => onChanged(),
      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 12),
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        enabledBorder: error ? OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.statusDue)) : null,
      ),
    );
  }
}

class _ReadOnlyValue extends StatelessWidget {
  const _ReadOnlyValue({required this.label, required this.value});

  final String label;
  final double? value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: AppTheme.pageBackground, borderRadius: BorderRadius.circular(10)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textMuted)),
          Text(
            value == null ? '—' : (value == value!.roundToDouble() ? value!.toStringAsFixed(0) : value.toString()),
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
          ),
        ],
      ),
    );
  }
}

class _Tag extends StatelessWidget {
  const _Tag({required this.icon, required this.label, required this.color});

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 3),
          Text(label, style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: color)),
        ],
      ),
    );
  }
}
