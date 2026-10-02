import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../bills/widgets/bill_ui.dart';
import '../data/society_admin_repository.dart';

enum _Filter { all, pending, paid }

/// Society admin: for one billing run (e.g. "October 2026 Maintenance"),
/// who has paid and who still owes - flat by flat, pending first, with the
/// totals collected / outstanding on top.
class PaymentStatusScreen extends ConsumerStatefulWidget {
  const PaymentStatusScreen({super.key});

  @override
  ConsumerState<PaymentStatusScreen> createState() => _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends ConsumerState<PaymentStatusScreen> {
  String? _title; // null = newest billing run
  _Filter _filter = _Filter.all;
  String _search = '';

  @override
  Widget build(BuildContext context) {
    final periods = ref.watch(paymentPeriodsProvider).valueOrNull ?? const [];
    final list = ref.watch(paymentStatusProvider(_title));

    return Scaffold(
      backgroundColor: AppTheme.pageBackground,
      body: Column(
        children: [
          const BillHeaderBar(title: 'Payment Status'),
          Expanded(
            child: AsyncView<PaymentStatusList>(
              value: list,
              onRetry: () => ref.invalidate(paymentStatusProvider(_title)),
              builder: (context, data) {
                final query = _search.trim().toLowerCase();
                final items = data.items.where((item) {
                  if (_filter == _Filter.paid && !item.isPaid) return false;
                  if (_filter == _Filter.pending && item.isPaid) return false;
                  return query.isEmpty || item.flatLabel.toLowerCase().contains(query) || (item.residentName ?? '').toLowerCase().contains(query);
                }).toList();

                return RefreshIndicator(
                  onRefresh: () {
                    ref.invalidate(paymentPeriodsProvider);
                    return ref.refresh(paymentStatusProvider(_title).future);
                  },
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
                    children: [
                      if (periods.isNotEmpty)
                        DropdownButtonFormField<String>(
                          initialValue: data.title,
                          isExpanded: true,
                          decoration: InputDecoration(
                            labelText: 'Bill',
                            prefixIcon: const Icon(Icons.receipt_long_rounded),
                            filled: true,
                            fillColor: Colors.white,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          items: [
                            for (final p in periods) DropdownMenuItem(value: p.title, child: Text('${p.title}  (${p.bills})', overflow: TextOverflow.ellipsis)),
                          ],
                          onChanged: (value) => setState(() => _title = value),
                        ),
                      const SizedBox(height: 14),
                      _SummaryCards(summary: data.summary),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          for (final (filter, label) in [
                            (_Filter.all, 'All (${data.items.length})'),
                            (_Filter.pending, 'Pending (${data.summary.pendingCount})'),
                            (_Filter.paid, 'Paid (${data.summary.paidCount})'),
                          ])
                            Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: ChoiceChip(
                                label: Text(label),
                                selected: _filter == filter,
                                onSelected: (_) => setState(() => _filter = filter),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        onChanged: (value) => setState(() => _search = value),
                        decoration: InputDecoration(
                          hintText: 'Search flat number or name',
                          prefixIcon: const Icon(Icons.search_rounded),
                          isDense: true,
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (data.items.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 32),
                          child: EmptyState(title: 'No bills yet', message: 'Bills raised for the flats will show here.', icon: Icons.receipt_long_outlined),
                        )
                      else if (items.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 32),
                          child: EmptyState(title: 'Nothing here', message: 'No flat matches this filter.', icon: Icons.search_off_rounded),
                        ),
                      for (final item in items) ...[
                        _FlatPaymentTile(item: item),
                        const SizedBox(height: 8),
                      ],
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCards extends StatelessWidget {
  const _SummaryCards({required this.summary});

  final PaymentSummary summary;

  @override
  Widget build(BuildContext context) {
    Widget card(String label, double amount, int count, String countLabel, Color color, IconData icon) => Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(icon, size: 18, color: color),
                    const SizedBox(width: 6),
                    Text(label, style: const TextStyle(fontSize: 12.5, color: AppColors.textMuted, fontWeight: FontWeight.w600)),
                  ],
                ),
                const SizedBox(height: 6),
                FittedBox(fit: BoxFit.scaleDown, child: BillAmount(amount, size: 20, color: color, weight: FontWeight.w800)),
                Text('$count $countLabel', style: TextStyle(fontSize: 12.5, color: color, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        );

    return Row(
      children: [
        card('Collected', summary.totalCollected, summary.paidCount, summary.paidCount == 1 ? 'flat paid' : 'flats paid', AppTheme.statusPaid, Icons.check_circle_rounded),
        const SizedBox(width: 10),
        card('Pending', summary.totalPending, summary.pendingCount, summary.pendingCount == 1 ? 'flat to pay' : 'flats to pay', AppTheme.statusDue, Icons.schedule_rounded),
      ],
    );
  }
}

class _FlatPaymentTile extends StatelessWidget {
  const _FlatPaymentTile({required this.item});

  final FlatPayment item;

  @override
  Widget build(BuildContext context) {
    final partly = !item.isPaid && item.paid > 0;
    final color = item.isPaid ? AppTheme.statusPaid : (partly ? AppTheme.statusPending : AppTheme.statusDue);
    final statusLabel = item.isPaid ? 'Paid' : (partly ? 'Part paid' : (item.status == 'overdue' ? 'Overdue' : 'Pending'));

    final detail = item.isPaid
        ? [
            if (item.paidOn != null) 'Paid ${formatDate(item.paidOn)}',
            if (item.paymentMethod != null) paymentMethodLabel(item.paymentMethod!),
          ].join(' · ')
        : [
            if (partly) '${formatCurrency(item.paid)} paid',
            if (item.dueDate != null) 'Due ${formatDate(item.dueDate)}',
          ].join(' · ');

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 12, 14, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border(left: BorderSide(color: color, width: 4)),
      ),
      child: Row(
        children: [
          Container(
            constraints: const BoxConstraints(minWidth: 58),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
            child: Text(item.flatNumber, textAlign: TextAlign.center, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: color)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.residentName ?? item.flatLabel,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                ),
                if (detail.isNotEmpty)
                  Text(detail, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5, color: AppColors.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              BillAmount(item.isPaid ? item.amount : item.balance, size: 15.5, color: color, weight: FontWeight.w800),
              const SizedBox(height: 3),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
                child: Text(statusLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color)),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
