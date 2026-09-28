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
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
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
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 12, 10, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, size: 18, color: color),
          ),
          const SizedBox(height: 10),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(value, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 16)),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
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
        indicator: BoxDecoration(color: AppTheme.brandBlue, borderRadius: BorderRadius.circular(26)),
        labelColor: Colors.white,
        unselectedLabelColor: const Color(0xFF5B6178),
        labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
        tabs: [
          Tab(height: 40, text: 'Pending ($pendingCount)'),
          Tab(height: 40, text: 'Paid ($paidCount)'),
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
              separatorBuilder: (_, _) => const SizedBox(height: 12),
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

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => context.push('/bills/${bill.id}'),
        child: Container(
          decoration: BoxDecoration(border: Border(left: BorderSide(color: color, width: 4))),
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
                    child: Icon(bill.isPending ? Icons.receipt_long_rounded : Icons.task_alt_rounded, color: color, size: 22),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          bill.heading,
                          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: AppTheme.brandNavy),
                        ),
                        if (bill.subheading != null)
                          Text(
                            bill.subheading!,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12.5, color: AppColors.textMuted),
                          ),
                        const SizedBox(height: 6),
                        BillStatusBadge(bill: bill),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        bill.isPending ? 'Amount Due' : 'Amount Paid',
                        style: const TextStyle(fontSize: 11.5, color: AppColors.textMuted),
                      ),
                      const SizedBox(height: 2),
                      BillAmount(bill.isPending ? bill.balance : bill.amount, size: 18, color: color, weight: FontWeight.w800),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              const Divider(height: 1),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(
                    bill.isPending ? Icons.event_outlined : Icons.event_available_outlined,
                    size: 16,
                    color: AppColors.textMuted,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      bill.isPending ? 'Due ${formatDate(bill.dueDate)}' : 'Paid on ${formatDate(paidOn ?? bill.dueDate)}',
                      style: const TextStyle(fontSize: 13, color: AppTheme.brandNavy, fontWeight: FontWeight.w500),
                    ),
                  ),
                  if (hint != null)
                    Text(
                      hint,
                      style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: bill.isOverdue ? AppTheme.statusDue : AppTheme.statusPending),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(44),
                        foregroundColor: AppTheme.brandBlue,
                        side: const BorderSide(color: AppTheme.brandBlue),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: () => context.push('/bills/${bill.id}'),
                      icon: Icon(bill.isPending ? Icons.visibility_outlined : Icons.receipt_outlined, size: 18),
                      label: Text(bill.isPending ? 'View Bill' : 'View Receipt', style: const TextStyle(fontWeight: FontWeight.w600)),
                    ),
                  ),
                  if (bill.isPending) ...[
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(44),
                          backgroundColor: AppTheme.brandBlue,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        // Opens the bill and starts the payment straight away.
                        onPressed: () => context.push('/bills/${bill.id}?pay=1'),
                        icon: const Icon(Icons.payments_outlined, size: 18),
                        label: const Text('Pay Now', style: TextStyle(fontWeight: FontWeight.w700)),
                      ),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
