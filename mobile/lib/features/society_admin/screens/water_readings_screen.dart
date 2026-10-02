import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../bills/widgets/bill_ui.dart';
import '../data/society_admin_repository.dart';

/// "2026-10" for a month.
String monthKey(DateTime month) => DateFormat('yyyy-MM').format(month);

/// Society admin: water readings, step 1 - pick the month (this month or
/// one of the last few) and a block. Each block shows how many of its flats
/// already have this month's reading; tapping it opens the entry sheet.
class WaterReadingsScreen extends ConsumerStatefulWidget {
  const WaterReadingsScreen({super.key});

  @override
  ConsumerState<WaterReadingsScreen> createState() => _WaterReadingsScreenState();
}

class _WaterReadingsScreenState extends ConsumerState<WaterReadingsScreen> {
  /// This month and the five before it - readings can't be entered for a future month.
  final List<DateTime> _months = [
    for (var i = 0; i < 6; i++) DateTime(DateTime.now().year, DateTime.now().month - i),
  ];
  late DateTime _month = _months.first;

  @override
  Widget build(BuildContext context) {
    final key = monthKey(_month);
    final blocks = ref.watch(waterBlocksProvider(key));

    return Scaffold(
      backgroundColor: AppTheme.pageBackground,
      body: Column(
        children: [
          const BillHeaderBar(title: 'Water Readings'),
          SizedBox(
            height: 56,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              children: [
                for (final month in _months)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(DateFormat('MMM yyyy').format(month)),
                      selected: month == _month,
                      onSelected: (_) => setState(() => _month = month),
                    ),
                  ),
              ],
            ),
          ),
          Expanded(
            child: AsyncView<WaterBlocks>(
              value: blocks,
              onRetry: () => ref.invalidate(waterBlocksProvider(key)),
              builder: (context, data) => RefreshIndicator(
                onRefresh: () => ref.refresh(waterBlocksProvider(key).future),
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  children: [
                    _RatesCard(rates: data.rates),
                    const SizedBox(height: 14),
                    if (data.blocks.isEmpty)
                      const Padding(
                        padding: EdgeInsets.only(top: 40),
                        child: EmptyState(title: 'No blocks yet', message: 'Add blocks and flats on the website first.', icon: Icons.apartment_rounded),
                      ),
                    for (final block in data.blocks) ...[
                      _BlockTile(
                        block: block,
                        onTap: () async {
                          await context.push('/admin/water-readings/${block.id}?month=$key&name=${Uri.encodeComponent(block.name)}');
                          ref.invalidate(waterBlocksProvider(key));
                        },
                      ),
                      const SizedBox(height: 10),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _RatesCard extends StatelessWidget {
  const _RatesCard({required this.rates});

  final BillingRates rates;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.07), borderRadius: BorderRadius.circular(14)),
      child: Row(
        children: [
          const Icon(Icons.calculate_outlined, color: AppColors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Bill = units × ${formatCurrency(rates.waterUnitRate)} + ${formatCurrency(rates.fixedMaintenance)} fixed',
              style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}

class _BlockTile extends StatelessWidget {
  const _BlockTile({required this.block, required this.onTap});

  final WaterBlock block;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final done = block.isComplete;
    final color = done ? AppTheme.statusPaid : (block.enteredCount > 0 ? AppTheme.statusPending : AppColors.textMuted);

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.apartment_rounded, color: AppColors.primary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(block.name, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                    const SizedBox(height: 6),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: block.flatsCount == 0 ? 0 : block.enteredCount / block.flatsCount,
                        minHeight: 6,
                        backgroundColor: AppColors.border,
                        color: color,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${block.enteredCount} of ${block.flatsCount} flats entered',
                      style: TextStyle(fontSize: 12.5, color: color, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(done ? Icons.check_circle_rounded : Icons.chevron_right_rounded, color: done ? AppTheme.statusPaid : AppColors.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}
