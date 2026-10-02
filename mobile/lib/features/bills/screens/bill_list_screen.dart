import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../data/bill.dart';
import '../providers/bill_providers.dart';
import '../widgets/bill_ui.dart';

/// My Bills: totals at the top, Pending / Paid tabs, and a card per bill
/// with its own View Bill / Pay Now buttons so paying never takes more than
/// one tap to find.
///
/// The full list is fetched once (no server-side status filter) and split
/// into Pending/Paid client-side — a resident's bill history is small
/// enough that this is simpler than two separate requests, and it lets the
/// tabs share one pull-to-refresh.
class BillListScreen extends ConsumerWidget {
  const BillListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bills = ref.watch(billListProvider(null));
    Future<void> refresh() => ref.refresh(billListProvider(null).future);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: AppTheme.pageBackground,
        body: Column(
          children: [
            const BillHeaderBar(title: 'My Bills'),
            Expanded(
              child: AsyncView<List<Bill>>(
                value: bills,
                onRetry: () => ref.invalidate(billListProvider(null)),
                builder: (context, items) {
                  // Oldest due first, so the bill to pay next is always on top.
                  final pending = items.where((b) => b.isPending).toList()
                    ..sort((a, b) => (a.dueDate ?? '').compareTo(b.dueDate ?? ''));
                  final paid = items.where((b) => !b.isPending).toList();
                  final totalDue = pending.fold<double>(0, (sum, b) => sum + b.balance);
                  final totalPaid = items.fold<double>(0, (sum, b) => sum + b.paidAmount);

                  return Column(
                    children: [
                      const SizedBox(height: 14),
                      _SummaryRow(totalDue: totalDue, totalPaid: totalPaid, pendingCount: pending.length),
                      _PillTabBar(pendingCount: pending.length, paidCount: paid.length),
                      Expanded(
                        child: TabBarView(
                          children: [
                            _BillTab(
                              bills: pending,
                              onRefresh: refresh,
                              emptyIcon: Icons.task_alt_rounded,
                              emptyTitle: "You're all caught up!",
                              emptyMessage: 'There are no pending bills right now.',
                            ),
                            _BillTab(
                              bills: paid,
                              onRefresh: refresh,
                              emptyIcon: Icons.receipt_long_outlined,
                              emptyTitle: 'No paid bills yet',
                              emptyMessage: 'Bills you pay will show up here with their receipts.',
                            ),
                          ],
                        ),
                      ),
                    ],
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

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.totalDue, required this.totalPaid, required this.pendingCount});

  final double totalDue;
  final double totalPaid;
  final int pendingCount;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
      child: Row(
        children: [
          Expanded(
            child: _SummaryCard(
              icon: Icons.account_balance_wallet_outlined,
              label: 'Total Due',
              value: formatCurrency(totalDue),
              color: totalDue > 0 ? AppTheme.statusDue : AppTheme.statusPaid,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _SummaryCard(
              icon: Icons.check_circle_outline_rounded,
              label: 'Paid',
              value: formatCurrency(totalPaid),
              color: AppTheme.statusPaid,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _SummaryCard(
              icon: Icons.receipt_long_outlined,
              label: 'Pending Bills',
              value: '$pendingCount',
              color: pendingCount > 0 ? AppTheme.statusPending : AppTheme.statusPaid,
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.icon, required this.label, required this.value, required this.color});

  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    // Soft tinted card in the stat's own colour, so the three totals read
    // apart at a glance.
    return Container(
      padding: const EdgeInsets.fromLTRB(10, 9, 8, 9),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [color.withValues(alpha: 0.16), color.withValues(alpha: 0.05)],
        ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(value, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 15)),
          ),
        ],
      ),
    );
  }
}

/// A rounded, pill-style tab bar (grey track, solid blue selected pill)
/// with each tab's count.
class _PillTabBar extends StatelessWidget {
  const _PillTabBar({required this.pendingCount, required this.paidCount});

  final int pendingCount;
  final int paidCount;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFFE3E7F3),
        borderRadius: BorderRadius.circular(30),
      ),
      child: TabBar(
        dividerColor: Colors.transparent,
        indicatorSize: TabBarIndicatorSize.tab,
        indicator: BoxDecoration(gradient: AppTheme.brandGradient, borderRadius: BorderRadius.circular(26)),
        labelColor: Colors.white,
        unselectedLabelColor: const Color(0xFF5B6178),
        labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13),
        tabs: [
          Tab(height: 34, text: 'Pending ($pendingCount)'),
          Tab(height: 34, text: 'Paid ($paidCount)'),
        ],
      ),
    );
  }
}

class _BillTab extends StatelessWidget {
  const _BillTab({
    required this.bills,
    required this.onRefresh,
    required this.emptyIcon,
    required this.emptyTitle,
    required this.emptyMessage,
  });

  final List<Bill> bills;
  final Future<void> Function() onRefresh;
  final IconData emptyIcon;
  final String emptyTitle;
  final String emptyMessage;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: bills.isEmpty
          ? ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(32, 48, 32, 32),
              children: [
                Icon(emptyIcon, size: 56, color: AppTheme.statusPaid),
                const SizedBox(height: 12),
                Text(
                  emptyTitle,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppTheme.brandNavy),
                ),
                const SizedBox(height: 6),
                Text(emptyMessage, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.textMuted)),
              ],
            )
          : ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
              itemCount: bills.length,
              separatorBuilder: (_, _) => const SizedBox(height: 8),
              itemBuilder: (context, index) => _BillCard(bill: bills[index]),
            ),
    );
  }
}

class _BillCard extends StatelessWidget {
  const _BillCard({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final color = bill.statusColor;
    final hint = bill.dueHint;
    final paidOn = bill.lastPayment?.paymentDate;

    return Container(
      decoration: BoxDecoration(
        // White card washed with a hint of the status colour on the left.
        gradient: LinearGradient(colors: [color.withValues(alpha: 0.08), Colors.white], stops: const [0, 0.55]),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.22)),
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => context.push('/bills/${bill.id}'),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [color, color.withValues(alpha: 0.7)],
                        ),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(bill.isPending ? Icons.receipt_long_rounded : Icons.task_alt_rounded, color: Colors.white, size: 18),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            bill.heading,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: AppTheme.brandNavy),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            bill.isPending ? 'Due ${formatDate(bill.dueDate)}' : 'Paid on ${formatDate(paidOn ?? bill.dueDate)}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 11.5, color: AppColors.textMuted),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        BillAmount(bill.isPending ? bill.balance : bill.amount, size: 15, color: color, weight: FontWeight.w800),
                        const SizedBox(height: 3),
                        BillStatusBadge(bill: bill),
                      ],
                    ),
                  ],
                ),
                if (bill.subheading != null || hint != null) ...[
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          bill.subheading ?? '',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 11.5, color: AppColors.textMuted),
                        ),
                      ),
                      if (hint != null)
                        Text(
                          hint,
                          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: bill.isOverdue ? AppTheme.statusDue : AppTheme.statusPending),
                        ),
                    ],
                  ),
                ],
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(34),
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                          visualDensity: VisualDensity.compact,
                          foregroundColor: AppTheme.brandBlue,
                          backgroundColor: Colors.white,
                          side: BorderSide(color: AppTheme.brandBlue.withValues(alpha: 0.5)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () => context.push('/bills/${bill.id}'),
                        icon: Icon(bill.isPending ? Icons.visibility_outlined : Icons.receipt_outlined, size: 16),
                        label: Text(bill.isPending ? 'View Bill' : 'View Receipt', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                      ),
                    ),
                    if (bill.isPending) ...[
                      const SizedBox(width: 8),
                      Expanded(
                        child: FilledButton.icon(
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(34),
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            visualDensity: VisualDensity.compact,
                            backgroundColor: AppTheme.brandBlue,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          // Opens the bill and starts the payment straight away.
                          onPressed: () => context.push('/bills/${bill.id}?pay=1'),
                          icon: const Icon(Icons.payments_outlined, size: 16),
                          label: const Text('Pay Now', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
