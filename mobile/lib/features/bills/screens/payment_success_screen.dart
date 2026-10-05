import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../widgets/bill_ui.dart';

/// What the confirmation screen shows - built by BillDetailScreen from the
/// backend-verified bill, never from Razorpay's unverified callback alone.
class PaymentReceipt {
  const PaymentReceipt({
    required this.billId,
    required this.billHeading,
    required this.amount,
    required this.transactionId,
    required this.paymentDate,
    required this.fullyPaid,
    required this.balance,
    this.paymentMethod,
  });

  final int billId;
  final String billHeading;
  final double amount;
  final String transactionId;
  final String paymentDate;
  final String? paymentMethod;
  final bool fullyPaid;
  final double balance;
}

/// "Payment Successful": the amount, transaction ID, date and bill, with the
/// receipt download and a way back to My Bills.
class PaymentSuccessScreen extends ConsumerStatefulWidget {
  const PaymentSuccessScreen({super.key, required this.receipt});

  final PaymentReceipt receipt;

  @override
  ConsumerState<PaymentSuccessScreen> createState() => _PaymentSuccessScreenState();
}

class _PaymentSuccessScreenState extends ConsumerState<PaymentSuccessScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pop = AnimationController(vsync: this, duration: const Duration(milliseconds: 650))..forward();
  bool _downloading = false;

  @override
  void dispose() {
    _pop.dispose();
    super.dispose();
  }

  void _done() => context.canPop() ? context.pop() : context.go('/bills');

  Future<void> _download() async {
    setState(() => _downloading = true);
    await shareBillPdf(context, ref, widget.receipt.billId, receipt: true);
    if (mounted) setState(() => _downloading = false);
  }

  @override
  Widget build(BuildContext context) {
    final receipt = widget.receipt;

    return Scaffold(
      backgroundColor: AppTheme.pageBackground,
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 32, 20, 24),
          children: [
            Center(
              child: ScaleTransition(
                scale: CurvedAnimation(parent: _pop, curve: Curves.elasticOut),
                child: Container(
                  width: 96,
                  height: 96,
                  decoration: BoxDecoration(
                    color: AppTheme.statusPaid,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: AppTheme.statusPaid.withValues(alpha: 0.35), blurRadius: 24, offset: const Offset(0, 8))],
                  ),
                  child: const Icon(Icons.check_rounded, color: Colors.white, size: 60),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Payment Successful',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppTheme.brandNavy),
            ),
            const SizedBox(height: 6),
            Text(
              receipt.fullyPaid
                  ? 'Your ${receipt.billHeading} bill is fully paid. Thank you!'
                  : '${formatCurrency(receipt.balance)} is still due on this bill.',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 14.5, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 16, offset: const Offset(0, 6))],
              ),
              child: Column(
                children: [
                  const Text('Amount Paid', style: TextStyle(fontSize: 13, color: AppColors.textMuted)),
                  const SizedBox(height: 2),
                  BillAmount(receipt.amount, size: 32, color: AppTheme.statusPaid, weight: FontWeight.w800),
                  const Padding(padding: EdgeInsets.symmetric(vertical: 14), child: Divider(height: 1)),
                  // A Razorpay id is long - it gets its own full-width line.
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Transaction ID', style: TextStyle(fontSize: 14, color: AppColors.textSecondary)),
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.fromLTRB(12, 4, 4, 4),
                          decoration: BoxDecoration(color: AppTheme.pageBackground, borderRadius: BorderRadius.circular(10)),
                          child: Row(
                            children: [
                              Expanded(
                                child: SelectableText(
                                  receipt.transactionId,
                                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.brandNavy),
                                ),
                              ),
                              IconButton(
                                tooltip: 'Copy',
                                visualDensity: VisualDensity.compact,
                                icon: const Icon(Icons.copy_rounded, size: 18, color: AppTheme.brandBlue),
                                onPressed: () {
                                  Clipboard.setData(ClipboardData(text: receipt.transactionId));
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Transaction ID copied'), behavior: SnackBarBehavior.floating),
                                  );
                                },
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  _Row(label: 'Payment Date', value: formatDate(receipt.paymentDate)),
                  _Row(label: 'Bill', value: receipt.billHeading),
                  if (receipt.paymentMethod != null) _Row(label: 'Paid Via', value: paymentMethodLabel(receipt.paymentMethod!)),
                  _Row(
                    label: 'Status',
                    value: receipt.fullyPaid ? 'Paid' : 'Partially Paid',
                    valueColor: receipt.fullyPaid ? AppTheme.statusPaid : AppTheme.statusPending,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            FilledButton.icon(
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(54),
                backgroundColor: AppTheme.brandBlue,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              onPressed: _downloading ? null : _download,
              icon: _downloading
                  ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.download_rounded),
              label: const Text('Payment Receipt', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(52),
                foregroundColor: AppTheme.brandBlue,
                side: const BorderSide(color: AppTheme.brandBlue),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              onPressed: _done,
              child: const Text('Back to My Bills', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            ),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.valueColor});

  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          SizedBox(width: 112, child: Text(label, style: const TextStyle(fontSize: 14, color: AppColors.textSecondary))),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: valueColor ?? AppTheme.brandNavy),
            ),
          ),
        ],
      ),
    );
  }
}
