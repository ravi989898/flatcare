import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../data/bill.dart';
import '../data/bill_repository.dart';
import '../data/razorpay_order.dart';
import '../providers/bill_providers.dart';
import '../widgets/bill_ui.dart';
import 'payment_success_screen.dart';

/// Where the payment is: nothing running, creating the Razorpay order,
/// Razorpay's own checkout on screen, or the backend verifying it.
enum _PayStage { idle, starting, checkout, verifying }

/// One maintenance bill: the amount due up top, then the flat, bill
/// information, an itemised breakdown, notes and payment history - with a
/// sticky bar at the bottom that always shows the amount due and a large
/// Pay Now button, so nobody has to scroll to find how to pay.
///
/// [autoPay] (`/bills/{id}?pay=1`, the Pay Now button on My Bills) starts
/// the payment as soon as the bill has loaded.
///
/// Payment is the existing Razorpay flow: the backend creates the order
/// from the bill's own balance, Razorpay Checkout takes the money, and only
/// the backend's verification of that result counts as paid - then the
/// confirmation screen shows the transaction.
class BillDetailScreen extends ConsumerStatefulWidget {
  const BillDetailScreen({super.key, required this.id, this.autoPay = false});

  final int id;
  final bool autoPay;

  @override
  ConsumerState<BillDetailScreen> createState() => _BillDetailScreenState();
}

class _BillDetailScreenState extends ConsumerState<BillDetailScreen> {
  // razorpay_flutter is a native Android/iOS plugin with no web
  // implementation at all — constructing it unconditionally throws
  // MissingPluginException the moment this screen mounts in a browser.
  // Only ever created outside web, so Pay Now can fail with one clear
  // message on web instead of the whole screen erroring on load.
  Razorpay? _razorpay;
  _PayStage _stage = _PayStage.idle;
  RazorpayOrderInfo? _order;
  Bill? _payingBill;
  bool _autoPayHandled = false;
  bool _downloading = false;

  bool get _busy => _stage != _PayStage.idle;

  @override
  void initState() {
    super.initState();
    if (!kIsWeb) {
      _razorpay = Razorpay()
        ..on(Razorpay.EVENT_PAYMENT_SUCCESS, _onPaymentSuccess)
        ..on(Razorpay.EVENT_PAYMENT_ERROR, _onPaymentError)
        ..on(Razorpay.EVENT_EXTERNAL_WALLET, _onExternalWallet);
    }
  }

  @override
  void dispose() {
    _razorpay?.clear();
    super.dispose();
  }

  void _setStage(_PayStage stage) {
    if (mounted) setState(() => _stage = stage);
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message), behavior: SnackBarBehavior.floating));
  }

  Future<void> _payNow(Bill bill) async {
    if (_busy || !bill.isPending || bill.balance <= 0) return;

    if (kIsWeb) {
      _snack('Online payment is available in the FlatCare mobile app.');
      return;
    }

    _payingBill = bill;
    _setStage(_PayStage.starting);

    try {
      final order = await ref.read(billRepositoryProvider).createPaymentOrder(bill.id);
      _order = order;
      _setStage(_PayStage.checkout);

      _razorpay!.open({
        'key': order.key,
        'amount': order.amountPaise,
        'currency': order.currency,
        'name': order.name,
        'description': order.description,
        'order_id': order.razorpayOrderId,
        'prefill': {
          if (order.prefillName != null) 'name': order.prefillName,
          if (order.prefillEmail != null) 'email': order.prefillEmail,
          if (order.prefillContact != null) 'contact': order.prefillContact,
        },
        'theme': {'color': '#0E7C9B'},
      });
      // The stage stays `checkout` — Razorpay's native checkout is now in
      // control; one of the three event handlers below always fires next.
    } on ApiException catch (e) {
      _setStage(_PayStage.idle);
      _showFailure(title: "Couldn't start the payment", message: e.message, bill: bill);
    } catch (_) {
      // Razorpay.open() can throw synchronously for malformed options
      // (e.g. no key configured yet), or the network may be down.
      _setStage(_PayStage.idle);
      _showFailure(
        title: "Couldn't start the payment",
        message: 'Please check your internet connection and try again.',
        bill: bill,
      );
    }
  }

  /// Checkout finishing is only ever an unverified claim from the device —
  /// the bill is never treated as paid, and nothing here updates the UI
  /// optimistically, until the backend independently verifies it.
  Future<void> _onPaymentSuccess(PaymentSuccessResponse response) async {
    final orderId = response.orderId;
    final paymentId = response.paymentId;
    final signature = response.signature;
    final bill = _payingBill;

    if (orderId == null || paymentId == null || signature == null || bill == null) {
      _setStage(_PayStage.idle);
      _showUnconfirmed(paymentId);
      return;
    }

    _setStage(_PayStage.verifying);

    try {
      final updated = await ref.read(billRepositoryProvider).verifyPayment(
            bill.id,
            orderId: orderId,
            paymentId: paymentId,
            signature: signature,
          );

      ref.invalidate(billDetailProvider(bill.id));
      ref.invalidate(billListProvider(null));
      if (!mounted) return;

      final payment = updated.payments.where((p) => p.referenceNumber == paymentId).firstOrNull;
      final receipt = PaymentReceipt(
        billId: bill.id,
        billHeading: bill.heading,
        amount: payment?.amount ?? (_order?.amountPaise ?? 0) / 100,
        transactionId: paymentId,
        paymentDate: payment?.paymentDate ?? DateTime.now().toIso8601String(),
        paymentMethod: payment?.paymentMethod,
        fullyPaid: !updated.isPending,
        balance: updated.balance,
      );

      _stage = _PayStage.idle;
      context.pushReplacement('/bills/${bill.id}/paid', extra: receipt);
    } on ApiException catch (e) {
      _setStage(_PayStage.idle);
      _showUnconfirmed(paymentId, reason: e.message);
    } catch (_) {
      _setStage(_PayStage.idle);
      _showUnconfirmed(paymentId);
    }
  }

  void _onPaymentError(PaymentFailureResponse response) {
    _setStage(_PayStage.idle);

    if (response.code == Razorpay.PAYMENT_CANCELLED) {
      _snack('Payment cancelled. You have not been charged.');
      return;
    }

    _showFailure(
      title: 'Payment failed',
      message: response.message?.trim().isNotEmpty == true
          ? response.message!
          : 'The payment could not be completed. No money has been taken for this attempt.',
      bill: _payingBill,
    );
  }

  void _onExternalWallet(ExternalWalletResponse response) {
    _setStage(_PayStage.idle);
    _snack('Opened ${response.walletName ?? 'your wallet'} — complete the payment there.');
  }

  /// A failed attempt: why, and a Try Again that runs the whole flow again.
  void _showFailure({required String title, required String message, Bill? bill}) {
    if (!mounted) return;

    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (sheet) => _ResultSheet(
        icon: Icons.error_outline_rounded,
        color: AppTheme.statusDue,
        title: title,
        message: message,
        primaryLabel: bill == null ? 'Close' : 'Try Again',
        onPrimary: () {
          Navigator.of(sheet).pop();
          if (bill != null) _payNow(bill);
        },
        secondaryLabel: bill == null ? null : 'Close',
        onSecondary: () => Navigator.of(sheet).pop(),
      ),
    );
  }

  /// Razorpay reported success but the backend couldn't confirm it. Never
  /// offers to pay again (that could charge twice) - only to re-check.
  void _showUnconfirmed(String? paymentId, {String? reason}) {
    if (!mounted) return;

    final id = widget.id;
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (sheet) => _ResultSheet(
        icon: Icons.hourglass_top_rounded,
        color: AppTheme.statusPending,
        title: "We couldn't confirm your payment yet",
        message: [
          ?reason,
          'Please do not pay again. If money was deducted, the bill will be updated shortly or the amount refunded by your bank.',
          if (paymentId != null) 'Payment ID: $paymentId',
        ].join('\n\n'),
        primaryLabel: 'Check Bill Status',
        onPrimary: () {
          Navigator.of(sheet).pop();
          ref.invalidate(billDetailProvider(id));
          ref.invalidate(billListProvider(null));
        },
      ),
    );
  }

  /// Leaving mid-payment could lose track of it, so ask first.
  Future<void> _confirmLeave() async {
    final leave = await showDialog<bool>(
      context: context,
      builder: (dialog) => AlertDialog(
        icon: const Icon(Icons.warning_amber_rounded, color: AppTheme.statusPending, size: 36),
        title: const Text('Payment in progress'),
        content: Text(
          _stage == _PayStage.verifying
              ? "We're confirming your payment with the bank. If you leave now, the bill may take a little longer to show as paid."
              : 'Your payment is still being set up. Do you want to leave this screen?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialog).pop(true), child: const Text('Leave')),
          FilledButton(onPressed: () => Navigator.of(dialog).pop(false), child: const Text('Stay')),
        ],
      ),
    );

    if (leave == true && mounted) {
      _stage = _PayStage.idle;
      context.canPop() ? context.pop() : context.go('/bills');
    }
  }

  Future<void> _download(Bill bill) async {
    setState(() => _downloading = true);
    await shareBillPdf(context, ref, bill.id, receipt: !bill.isPending);
    if (mounted) setState(() => _downloading = false);
  }

  @override
  Widget build(BuildContext context) {
    final billValue = ref.watch(billDetailProvider(widget.id));
    final bill = billValue.valueOrNull;

    if (widget.autoPay && !_autoPayHandled && bill != null) {
      _autoPayHandled = true;
      if (bill.isPending) WidgetsBinding.instance.addPostFrameCallback((_) => _payNow(bill));
    }

    return PopScope(
      canPop: !_busy,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _confirmLeave();
      },
      child: Scaffold(
        backgroundColor: AppTheme.pageBackground,
        bottomNavigationBar: bill == null
            ? null
            : _PayBar(
                bill: bill,
                stage: _stage,
                downloading: _downloading,
                onPay: () => _payNow(bill),
                onDownload: () => _download(bill),
              ),
        body: Stack(
          children: [
            Column(
              children: [
                BillHeaderBar(
                  title: 'Maintenance Bill',
                  actions: [
                    if (bill != null)
                      IconButton(
                        tooltip: bill.isPending ? 'Download Bill' : 'Payment Receipt',
                        onPressed: _downloading ? null : () => _download(bill),
                        icon: const Icon(Icons.download_rounded, color: Colors.white),
                      ),
                  ],
                ),
                Expanded(
                  child: AsyncView<Bill>(
                    value: billValue,
                    onRetry: () => ref.invalidate(billDetailProvider(widget.id)),
                    builder: (context, bill) => RefreshIndicator(
                      onRefresh: () => ref.refresh(billDetailProvider(widget.id).future),
                      child: _BillBody(bill: bill, downloading: _downloading, onDownload: () => _download(bill)),
                    ),
                  ),
                ),
              ],
            ),
            if (_stage == _PayStage.verifying) const _VerifyingOverlay(),
          ],
        ),
      ),
    );
  }
}

class _BillBody extends StatelessWidget {
  const _BillBody({required this.bill, required this.downloading, required this.onDownload});

  final Bill bill;
  final bool downloading;
  final VoidCallback onDownload;

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        _SummaryCard(bill: bill),
        const SizedBox(height: 14),
        _BillInfoSection(bill: bill),
        const SizedBox(height: 14),
        _BreakdownSection(bill: bill),
        if (bill.notes != null && bill.notes!.trim().isNotEmpty) ...[
          const SizedBox(height: 14),
          BillSection(
            title: 'Notes',
            icon: Icons.sticky_note_2_outlined,
            child: Text(bill.notes!, style: const TextStyle(fontSize: 14, height: 1.45, color: AppTheme.brandNavy)),
          ),
        ],
        if (bill.payments.isNotEmpty) ...[
          const SizedBox(height: 14),
          _PaymentHistorySection(bill: bill),
        ],
        const SizedBox(height: 16),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(48),
            foregroundColor: AppTheme.brandBlue,
            side: BorderSide(color: AppTheme.brandBlue.withValues(alpha: 0.5)),
            backgroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          ),
          onPressed: downloading ? null : onDownload,
          icon: downloading
              ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.picture_as_pdf_outlined),
          label: Text(bill.isPending ? 'Download Bill (PDF)' : 'Payment Receipt (PDF)', style: const TextStyle(fontWeight: FontWeight.w600)),
        ),
      ],
    );
  }
}

/// Top card: month, status, the amount due in large type and the due date.
class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final color = bill.statusColor;
    final hint = bill.dueHint;
    final paidOn = bill.lastPayment?.paymentDate;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border(top: BorderSide(color: color, width: 4)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.07), blurRadius: 18, offset: const Offset(0, 6))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(color: AppTheme.brandBlue.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.receipt_long_rounded, color: AppTheme.brandBlue, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      bill.heading,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.brandNavy),
                    ),
                    Text(
                      bill.subheading ?? 'Maintenance bill',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12.5, color: AppColors.textMuted),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              BillStatusBadge(bill: bill, large: true),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            bill.isPending ? 'Amount Due' : 'Amount Paid',
            style: const TextStyle(fontSize: 13, color: AppColors.textMuted, fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 2),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: BillAmount(bill.isPending ? bill.balance : bill.amount, size: 28, color: color, weight: FontWeight.w800),
          ),
          if (bill.isPartiallyPaid) ...[
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: bill.amount <= 0 ? 0 : (bill.paidAmount / bill.amount).clamp(0, 1).toDouble(),
                minHeight: 8,
                backgroundColor: AppColors.border,
                color: AppTheme.statusPaid,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              '${formatCurrency(bill.paidAmount)} of ${formatCurrency(bill.amount)} paid',
              style: const TextStyle(fontSize: 12.5, color: AppColors.textSecondary),
            ),
          ],
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: (bill.isOverdue ? AppTheme.statusDue : AppTheme.brandBlue).withValues(alpha: 0.06),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(
                  bill.isPending ? Icons.event_outlined : Icons.event_available_outlined,
                  size: 20,
                  color: bill.isOverdue ? AppTheme.statusDue : AppTheme.brandBlue,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        bill.isPending ? 'Payment Due Date' : 'Paid On',
                        style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                      ),
                      Text(
                        formatDate(bill.isPending ? bill.dueDate : (paidOn ?? bill.dueDate)),
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: bill.isOverdue ? AppTheme.statusDue : AppTheme.brandNavy,
                        ),
                      ),
                    ],
                  ),
                ),
                if (hint != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: (bill.isOverdue ? AppTheme.statusDue : AppTheme.statusPending).withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      hint,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: bill.isOverdue ? AppTheme.statusDue : AppTheme.statusPending,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _BillInfoSection extends StatelessWidget {
  const _BillInfoSection({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final flat = bill.flat;

    return BillSection(
      title: 'Bill Information',
      icon: Icons.info_outline_rounded,
      child: Column(
        children: [
          _InfoRow(label: 'Bill No.', value: '#${bill.id}'),
          if (bill.billingPeriod != null) _InfoRow(label: 'Billing Period', value: bill.billingPeriod!),
          _InfoRow(label: 'Bill Date', value: formatDate(bill.billDate)),
          _InfoRow(
            label: 'Due Date',
            value: formatDate(bill.dueDate),
            valueColor: bill.isOverdue ? AppTheme.statusDue : null,
          ),
          _InfoRow(label: 'Status', value: bill.statusLabel, valueColor: bill.statusColor),
          if (flat?.ownerName != null) _InfoRow(label: 'Owner Name', value: flat!.ownerName!),
          if (flat?.areaSqft != null) _InfoRow(label: 'Area', value: '${flat!.areaSqft!.toStringAsFixed(0)} sq. ft.'),
          if (bill.mobileNumber != null) _InfoRow(label: 'Mobile Number', value: bill.mobileNumber!),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value, this.valueColor});

  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 14, color: AppColors.textSecondary)),
          const SizedBox(width: 16),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: valueColor ?? AppTheme.brandNavy),
            ),
          ),
        ],
      ),
    );
  }
}

/// Itemised charges, any late fee or discount, the total, what has been
/// paid and what is left to pay.
class _BreakdownSection extends StatelessWidget {
  const _BreakdownSection({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    return BillSection(
      title: 'Bill Breakdown',
      icon: Icons.format_list_bulleted_rounded,
      child: Column(
        children: [
          for (final line in bill.breakdown)
            _MoneyRow(
              label: line.amount < 0 ? '${line.label} (Discount)' : line.label,
              amount: line.amount,
              color: line.amount < 0 ? AppTheme.statusPaid : null,
            ),
          if (bill.lateFee > 0) _MoneyRow(label: 'Late Fee', amount: bill.lateFee, color: AppTheme.statusDue),
          const Padding(padding: EdgeInsets.symmetric(vertical: 6), child: Divider(height: 1)),
          _MoneyRow(label: 'Total Amount', amount: bill.amount, bold: true),
          if (bill.paidAmount > 0) _MoneyRow(label: 'Amount Paid', amount: -bill.paidAmount, color: AppTheme.statusPaid),
          if (bill.isPending) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
              decoration: BoxDecoration(color: AppTheme.statusDue.withValues(alpha: 0.07), borderRadius: BorderRadius.circular(12)),
              child: Row(
                children: [
                  const Expanded(
                    child: Text('Balance Payable', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppTheme.brandNavy)),
                  ),
                  BillAmount(bill.balance, size: 17, color: AppTheme.statusDue, weight: FontWeight.w800),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _MoneyRow extends StatelessWidget {
  const _MoneyRow({required this.label, required this.amount, this.color, this.bold = false});

  final String label;
  final double amount;
  final Color? color;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      fontSize: bold ? 15 : 14,
      fontWeight: bold ? FontWeight.w800 : FontWeight.w500,
      color: color ?? AppTheme.brandNavy,
    );

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: Text(label, style: style.copyWith(color: color ?? (bold ? AppTheme.brandNavy : AppColors.textSecondary)))),
          const SizedBox(width: 12),
          Text(amount < 0 ? '- ${formatCurrency(-amount)}' : formatCurrency(amount), style: style),
        ],
      ),
    );
  }
}

class _PaymentHistorySection extends StatelessWidget {
  const _PaymentHistorySection({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    return BillSection(
      title: 'Payment History',
      icon: Icons.history_rounded,
      child: Column(
        children: [
          for (final (index, payment) in bill.payments.indexed) ...[
            if (index > 0) const Divider(height: 20),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: AppTheme.statusPaid.withValues(alpha: 0.12),
                  child: const Icon(Icons.check_rounded, color: AppTheme.statusPaid, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        [
                          formatDate(payment.paymentDate),
                          if (payment.paymentMethod != null) paymentMethodLabel(payment.paymentMethod!),
                        ].join(' · '),
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppTheme.brandNavy),
                      ),
                      // A Razorpay payment id ("pay_xxxxxxxxxxxxxx") is long,
                      // so it gets its own line and may wrap.
                      if (payment.referenceNumber != null)
                        SelectableText(
                          'Txn ID: ${payment.referenceNumber}',
                          style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                BillAmount(payment.amount, size: 15, color: AppTheme.statusPaid),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

/// The sticky bottom bar: amount due + Pay Now while money is owed, or the
/// receipt download once the bill is paid.
class _PayBar extends StatelessWidget {
  const _PayBar({
    required this.bill,
    required this.stage,
    required this.downloading,
    required this.onPay,
    required this.onDownload,
  });

  final Bill bill;
  final _PayStage stage;
  final bool downloading;
  final VoidCallback onPay;
  final VoidCallback onDownload;

  @override
  Widget build(BuildContext context) {
    final busy = stage != _PayStage.idle;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 16, offset: const Offset(0, -4))],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
          child: bill.isPending
              ? Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          flex: 4,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Text('Amount Due', maxLines: 1, style: TextStyle(fontSize: 12.5, color: AppColors.textMuted)),
                              FittedBox(
                                fit: BoxFit.scaleDown,
                                alignment: Alignment.centerLeft,
                                child: BillAmount(bill.balance, size: 22, color: bill.statusColor, weight: FontWeight.w800),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          flex: 5,
                          child: FilledButton(
                            style: FilledButton.styleFrom(
                              minimumSize: const Size.fromHeight(56),
                              backgroundColor: AppTheme.brandBlue,
                              disabledBackgroundColor: AppTheme.brandBlue.withValues(alpha: 0.6),
                              disabledForegroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            ),
                            onPressed: busy ? null : onPay,
                            child: busy
                                ? const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white)),
                                      SizedBox(width: 10),
                                      Flexible(
                                        child: FittedBox(
                                          fit: BoxFit.scaleDown,
                                          child: Text('Please wait…', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                                        ),
                                      ),
                                    ],
                                  )
                                : const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.lock_rounded, size: 20),
                                      SizedBox(width: 8),
                                      Flexible(
                                        child: FittedBox(
                                          fit: BoxFit.scaleDown,
                                          child: Text('Pay Now', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                                        ),
                                      ),
                                    ],
                                  ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.verified_user_outlined, size: 14, color: AppColors.textMuted),
                        SizedBox(width: 4),
                        Text('Secure payment · UPI, cards & net banking', style: TextStyle(fontSize: 11.5, color: AppColors.textMuted)),
                      ],
                    ),
                  ],
                )
              : FilledButton.icon(
                  style: FilledButton.styleFrom(
                    minimumSize: const Size.fromHeight(54),
                    backgroundColor: AppTheme.statusPaid,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  onPressed: downloading ? null : onDownload,
                  icon: downloading
                      ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.download_rounded),
                  label: const Text('Payment Receipt', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                ),
        ),
      ),
    );
  }
}

class _VerifyingOverlay extends StatelessWidget {
  const _VerifyingOverlay();

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: ColoredBox(
        color: Colors.black54,
        child: Center(
          child: Container(
            margin: const EdgeInsets.all(32),
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
            child: const Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(height: 44, width: 44, child: CircularProgressIndicator(strokeWidth: 3.5)),
                SizedBox(height: 18),
                Text('Confirming your payment…', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppTheme.brandNavy)),
                SizedBox(height: 6),
                Text(
                  'This takes a few seconds. Please don\'t close the app.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13.5, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ResultSheet extends StatelessWidget {
  const _ResultSheet({
    required this.icon,
    required this.color,
    required this.title,
    required this.message,
    required this.primaryLabel,
    required this.onPrimary,
    this.secondaryLabel,
    this.onSecondary,
  });

  final IconData icon;
  final Color color;
  final String title;
  final String message;
  final String primaryLabel;
  final VoidCallback onPrimary;
  final String? secondaryLabel;
  final VoidCallback? onSecondary;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 0, 24, 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircleAvatar(radius: 32, backgroundColor: color.withValues(alpha: 0.12), child: Icon(icon, color: color, size: 36)),
            const SizedBox(height: 14),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.brandNavy),
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(fontSize: 14, height: 1.45, color: AppColors.textSecondary)),
            const SizedBox(height: 20),
            Row(
              children: [
                if (secondaryLabel != null) ...[
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(50), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
                      onPressed: onSecondary,
                      child: Text(secondaryLabel!),
                    ),
                  ),
                  const SizedBox(width: 12),
                ],
                Expanded(
                  child: FilledButton(
                    style: FilledButton.styleFrom(
                      minimumSize: const Size.fromHeight(50),
                      backgroundColor: AppTheme.brandBlue,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: onPrimary,
                    child: Text(primaryLabel, style: const TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
