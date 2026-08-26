import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/bill.dart';
import '../providers/bill_providers.dart';

/// The full list is fetched once (no server-side status filter) and split
/// into Pending/Paid client-side — a resident's bill history is small
/// enough that this is simpler than two separate requests, and it lets the
/// tabs share one pull-to-refresh.
class BillListScreen extends ConsumerWidget {
  const BillListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bills = ref.watch(billListProvider(null));

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: AppTheme.pageBackground,
        body: Column(
          children: [
            const _BlueHeaderBar(),
            Expanded(
              child: AsyncView<List<Bill>>(
                value: bills,
                onRetry: () => ref.invalidate(billListProvider(null)),
                builder: (context, items) {
                  final pending = items.where((b) => b.isPending).toList();
                  final paid = items.where((b) => !b.isPending).toList();
                  final totalDue = pending.fold<double>(0, (sum, b) => sum + b.balance);
                  final totalPaid = paid.fold<double>(0, (sum, b) => sum + b.amount);

                  return Column(
                    children: [
                      _SummaryCard(totalDue: totalDue, totalPaid: totalPaid, pendingCount: pending.length),
                      const _PillTabBar(),
                      Expanded(
                        child: TabBarView(
                          children: [
                            _BillTab(bills: pending, emptyMessage: 'No pending bills. You\'re all caught up!'),
                            _BillTab(bills: paid, emptyMessage: 'No paid bills yet.'),
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

/// The blue gradient bar with the back button and title — kept outside
/// AsyncView so it (and the way back) is always on screen, even while the
/// bill list is loading or failed to load.
class _BlueHeaderBar extends StatelessWidget {
  const _BlueHeaderBar();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(4, 4, 16, 16),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back, color: Colors.white),
                onPressed: () => context.pop(),
              ),
              const Text(
                'My Bills',
                style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.totalDue, required this.totalPaid, required this.pendingCount});

  final double totalDue;
  final double totalPaid;
  final int pendingCount;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      padding: const EdgeInsets.symmetric(vertical: 18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 16, offset: const Offset(0, 6)),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: _SummaryStat(label: 'Total Due', value: formatCurrency(totalDue), color: AppTheme.statusDue),
          ),
          const _StatDivider(),
          Expanded(
            child: _SummaryStat(label: 'Paid', value: formatCurrency(totalPaid), color: AppTheme.statusPaid),
          ),
          const _StatDivider(),
          Expanded(
            child: _SummaryStat(label: 'Pending Bills', value: '$pendingCount', color: AppTheme.statusPending),
          ),
        ],
      ),
    );
  }
}

class _StatDivider extends StatelessWidget {
  const _StatDivider();

  @override
  Widget build(BuildContext context) {
    return Container(height: 34, width: 1, color: Theme.of(context).colorScheme.outlineVariant);
  }
}

class _SummaryStat extends StatelessWidget {
  const _SummaryStat({required this.label, required this.value, required this.color});

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 17),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall,
          textAlign: TextAlign.center,
        ),
      ],
    );
  }
}

/// A rounded, pill-style tab bar (grey track, solid blue selected pill)
/// standing in for the plain Material TabBar the screen used before.
class _PillTabBar extends StatelessWidget {
  const _PillTabBar();

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
        labelStyle: const TextStyle(fontWeight: FontWeight.bold),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500),
        tabs: const [Tab(text: 'Pending'), Tab(text: 'Paid')],
      ),
    );
  }
}

class _BillTab extends StatelessWidget {
  const _BillTab({required this.bills, required this.emptyMessage});

  final List<Bill> bills;
  final String emptyMessage;

  @override
  Widget build(BuildContext context) {
    if (bills.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          EmptyState(message: emptyMessage, icon: Icons.receipt_long_outlined),
        ],
      );
    }

    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      itemCount: bills.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) => _BillCard(bill: bills[index]),
    );
  }
}

class _BillCard extends StatelessWidget {
  const _BillCard({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final amountColor = AppTheme.billStatusColor(bill.status);

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => context.push('/bills/${bill.id}'),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      bill.billingPeriod ?? bill.title,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      bill.isPending ? 'Due: ${formatDate(bill.dueDate)}' : 'Paid: ${formatDate(bill.dueDate)}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    if (bill.isPending) ...[
                      const SizedBox(height: 6),
                      StatusChip(label: bill.status),
                    ],
                  ],
                ),
              ),
              Text(
                formatCurrency(bill.isPending ? bill.balance : bill.amount),
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: amountColor),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
