import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/bill.dart';
import '../providers/bill_providers.dart';

class BillDetailScreen extends ConsumerWidget {
  const BillDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bill = ref.watch(billDetailProvider(id));

    return Scaffold(
      appBar: AppBar(title: const Text('Bill Details')),
      body: AsyncView<Bill>(
        value: bill,
        onRetry: () => ref.invalidate(billDetailProvider(id)),
        builder: (context, item) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(child: Text(item.title, style: Theme.of(context).textTheme.titleLarge)),
                  StatusChip(label: item.status),
                ],
              ),
              if (item.flat != null) ...[
                const SizedBox(height: 4),
                Text(item.flat!.displayLabel, style: Theme.of(context).textTheme.bodySmall),
              ],
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _row(context, 'Amount', '₹${item.amount.toStringAsFixed(2)}'),
                      _row(context, 'Paid', '₹${item.paidAmount.toStringAsFixed(2)}'),
                      _row(context, 'Balance', '₹${item.balance.toStringAsFixed(2)}'),
                      _row(context, 'Due date', item.dueDate ?? '-'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              // Razorpay online payment isn't wired up yet — see PHASE_1 plan.
              OutlinedButton.icon(
                onPressed: null,
                icon: const Icon(Icons.payments_outlined),
                label: const Text('Pay Online — Coming Soon'),
              ),
              if (item.payments.isNotEmpty) ...[
                const SizedBox(height: 24),
                Text('Payment history', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                ...item.payments.map(
                  (payment) => Card(
                    child: ListTile(
                      title: Text('₹${payment.amount.toStringAsFixed(2)}'),
                      subtitle: Text('${payment.paymentMethod ?? ''} · ${payment.paymentDate ?? ''}'),
                      trailing: payment.referenceNumber != null ? Text(payment.referenceNumber!) : null,
                    ),
                  ),
                ),
              ],
            ],
          );
        },
      ),
    );
  }

  Widget _row(BuildContext context, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodyMedium),
          Text(value, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
