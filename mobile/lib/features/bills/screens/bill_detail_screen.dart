import 'dart:io';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:path_provider/path_provider.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/bill.dart';
import '../data/bill_repository.dart';
import '../providers/bill_providers.dart';

class BillDetailScreen extends ConsumerWidget {
  const BillDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bill = ref.watch(billDetailProvider(id));

    return Scaffold(
      backgroundColor: AppTheme.pageBackground,
      body: Column(
        children: [
          const _BlueHeaderBar(),
          Expanded(
            child: AsyncView<Bill>(
              value: bill,
              onRetry: () => ref.invalidate(billDetailProvider(id)),
              builder: (context, item) => _BillDetailBody(bill: item),
            ),
          ),
        ],
      ),
    );
  }
}

/// Back button + title only — kept outside AsyncView so the way back stays
/// on screen even while the bill is loading or failed to load, matching
/// bill_list_screen.dart's header.
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
                'Maintenance Bill',
                style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BillDetailBody extends ConsumerStatefulWidget {
  const _BillDetailBody({required this.bill});

  final Bill bill;

  @override
  ConsumerState<_BillDetailBody> createState() => _BillDetailBodyState();
}

class _BillDetailBodyState extends ConsumerState<_BillDetailBody> {
  // razorpay_flutter is a native Android/iOS plugin with no web
  // implementation at all — constructing it unconditionally throws
  // MissingPluginException the moment this screen mounts in a browser.
  // Only ever created outside web, so Pay Now can fail with one clear
  // message on web instead of the whole screen erroring on load.
  Razorpay? _razorpay;
  bool _isPaying = false;
  bool _isDownloading = false;

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

  Future<void> _payNow() async {
    if (kIsWeb) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Online payment is available in the FlatCare mobile app.')),
      );
      return;
    }

    setState(() => _isPaying = true);

    try {
      final order = await ref.read(billRepositoryProvider).createPaymentOrder(widget.bill.id);

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
        'theme': {'color': '#1E9CE0'},
      });
      // _isPaying stays true — Razorpay's native checkout is now in
      // control; one of the three event handlers below always fires next
      // and clears it.
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _isPaying = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      // Razorpay.open() can throw synchronously for malformed options
      // (e.g. no key configured yet).
      if (!mounted) return;
      setState(() => _isPaying = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open the payment screen. Please try again.')),
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

    if (orderId == null || paymentId == null || signature == null) {
      _onVerificationFailed();
      return;
    }

    _showVerifyingDialog();

    try {
      await ref.read(billRepositoryProvider).verifyPayment(
            widget.bill.id,
            orderId: orderId,
            paymentId: paymentId,
            signature: signature,
          );

      if (!mounted) return;
      final messenger = ScaffoldMessenger.of(context);
      _dismissVerifyingDialog();
      setState(() => _isPaying = false);

      ref.invalidate(billDetailProvider(widget.bill.id));
      ref.invalidate(billListProvider(null));

      messenger.showSnackBar(
        const SnackBar(content: Text('Payment successful!'), backgroundColor: Color(0xFF2AB930)),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      _dismissVerifyingDialog();
      setState(() => _isPaying = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message), showCloseIcon: true));
    }
  }

  void _onVerificationFailed() {
    if (!mounted) return;
    setState(() => _isPaying = false);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Payment could not be confirmed. Please contact support if money was deducted.')),
    );
  }

  void _onPaymentError(PaymentFailureResponse response) {
    if (!mounted) return;
    setState(() => _isPaying = false);
    final message = response.code == Razorpay.PAYMENT_CANCELLED
        ? 'Payment cancelled.'
        : (response.message ?? 'Payment failed. Please try again.');
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  void _onExternalWallet(ExternalWalletResponse response) {
    if (!mounted) return;
    setState(() => _isPaying = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Opened ${response.walletName ?? 'external wallet'} — complete payment there.')),
    );
  }

  void _showVerifyingDialog() {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => PopScope(
        canPop: false,
        child: Dialog(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: const [
                CircularProgressIndicator(),
                SizedBox(width: 16),
                Flexible(child: Text('Verifying payment…')),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _dismissVerifyingDialog() {
    Navigator.of(context, rootNavigator: true).pop();
  }

  Future<void> _downloadReceipt() async {
    // path_provider's directories and dart:io's File are both unavailable
    // on web — same reasoning as the Pay Now guard above.
    if (kIsWeb) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Receipt download is available in the FlatCare mobile app.')),
      );
      return;
    }

    setState(() => _isDownloading = true);

    try {
      final bytes = await ref.read(billRepositoryProvider).downloadReceipt(widget.bill.id);
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/bill-${widget.bill.id}-receipt.pdf');
      await file.writeAsBytes(bytes);

      if (!mounted) return;
      await Share.shareXFiles([XFile(file.path)], text: 'Maintenance bill receipt');
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bill = widget.bill;
    final flat = bill.flat;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _HeaderCard(bill: bill),
        const SizedBox(height: 16),
        _SummaryTiles(bill: bill),
        const SizedBox(height: 20),
        _SectionCard(
          title: 'Bill Details',
          child: Column(
            children: [
              if (flat != null) ...[
                _DetailRow(icon: Icons.home_outlined, label: 'Flat No.', value: flat.flatNumber),
                if (flat.blockName != null)
                  _DetailRow(icon: Icons.apartment_outlined, label: 'Tower / Building', value: flat.blockName!),
                if (flat.floorNumber != null)
                  _DetailRow(icon: Icons.layers_outlined, label: 'Floor', value: flat.floorNumber!),
                if (flat.flatType != null)
                  _DetailRow(icon: Icons.sell_outlined, label: 'Type', value: flat.flatType!),
                if (flat.areaSqft != null)
                  _DetailRow(
                    icon: Icons.open_in_full,
                    label: 'Area',
                    value: '${flat.areaSqft!.toStringAsFixed(0)} Sq. Ft.',
                  ),
              ],
              if (bill.billingPeriod != null)
                _DetailRow(icon: Icons.event_outlined, label: 'Billing Period', value: bill.billingPeriod!),
              _DetailRow(icon: Icons.calendar_today_outlined, label: 'Bill Date', value: formatDate(bill.billDate)),
              _DetailRow(
                icon: Icons.event_busy_outlined,
                label: 'Due Date',
                value: formatDate(bill.dueDate),
                valueColor: bill.isPending ? Theme.of(context).colorScheme.error : null,
              ),
              if (flat?.ownerName != null)
                _DetailRow(icon: Icons.person_outline, label: 'Owner Name', value: flat!.ownerName!),
              if (bill.mobileNumber != null)
                _DetailRow(icon: Icons.phone_outlined, label: 'Mobile Number', value: bill.mobileNumber!),
            ],
          ),
        ),
        const SizedBox(height: 16),
        _SectionCard(
          title: 'Bill Breakdown',
          padded: false,
          child: _BreakdownTable(bill: bill),
        ),
        if (bill.notes != null && bill.notes!.isNotEmpty) ...[
          const SizedBox(height: 16),
          _SectionCard(title: 'Notes', child: Text(bill.notes!)),
        ],
        if (bill.payments.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text('Payment History', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          ...bill.payments.map(
            (payment) => Card(
              child: ListTile(
                leading: const Icon(Icons.check_circle_outline, color: Color(0xFF2AB930)),
                title: Text(formatCurrency(payment.amount)),
                // Reference numbers used to be short manual entries (e.g.
                // "UPI2026070512345"); a Razorpay payment id
                // ("pay_xxxxxxxxxxxxxxxxxx") is longer, and ListTile's
                // `trailing` has no width limit of its own — putting it
                // there squeezed the title/subtitle column down to nothing.
                // Wrapping in the subtitle column instead lets it wrap
                // naturally.
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      [
                        if (payment.paymentMethod != null) payment.paymentMethod!.replaceAll('_', ' '),
                        formatDate(payment.paymentDate),
                      ].join(' · '),
                    ),
                    if (payment.referenceNumber != null)
                      Text(
                        payment.referenceNumber!,
                        style: Theme.of(context).textTheme.bodySmall,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
                isThreeLine: payment.referenceNumber != null,
              ),
            ),
          ),
        ],
        const SizedBox(height: 24),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _isDownloading ? null : _downloadReceipt,
                icon: _isDownloading
                    ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.description_outlined),
                label: Text(bill.isPending ? 'Download Bill' : 'Download Receipt'),
              ),
            ),
            if (bill.isPending) ...[
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _isPaying ? null : _payNow,
                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.brandBlue, foregroundColor: Colors.white),
                  icon: _isPaying
                      ? const SizedBox(
                          height: 16,
                          width: 16,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.payments_outlined),
                  label: const Text('Pay Now'),
                ),
              ),
            ],
          ],
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final amountColor = AppTheme.billStatusColor(bill.status);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 16, offset: const Offset(0, 6)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: AppTheme.brandBlue.withValues(alpha: 0.12),
                child: const Icon(Icons.home_repair_service_outlined, color: AppTheme.brandBlue, size: 26),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(bill.title, style: Theme.of(context).textTheme.titleLarge),
                    if (bill.billingPeriod != null)
                      Text(bill.billingPeriod!, style: Theme.of(context).textTheme.bodyMedium),
                  ],
                ),
              ),
              StatusChip(label: bill.status),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _DateChip(icon: Icons.calendar_today_outlined, label: 'Bill Date', date: bill.billDate),
              ),
              Expanded(
                child: _DateChip(
                  icon: Icons.event_busy_outlined,
                  label: 'Due Date',
                  date: bill.dueDate,
                  highlight: bill.isPending,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(bill.isPending ? 'Amount Due' : 'Total Amount', style: Theme.of(context).textTheme.bodySmall),
              Text(
                formatCurrency(bill.isPending ? bill.balance : bill.amount),
                style: Theme.of(context)
                    .textTheme
                    .headlineSmall
                    ?.copyWith(fontWeight: FontWeight.bold, color: amountColor),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _DateChip extends StatelessWidget {
  const _DateChip({required this.icon, required this.label, required this.date, this.highlight = false});

  final IconData icon;
  final String label;
  final String? date;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    final color = highlight ? Theme.of(context).colorScheme.error : Theme.of(context).colorScheme.onSurfaceVariant;

    return Row(
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 6),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: color)),
            Text(
              formatDate(date),
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: color, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ],
    );
  }
}

class _SummaryTiles extends StatelessWidget {
  const _SummaryTiles({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final tiles = [
      (icon: Icons.description_outlined, label: 'Total Amount', value: bill.amount, color: AppTheme.brandBlue),
      (icon: Icons.check_circle_outline, label: 'Paid Amount', value: bill.paidAmount, color: AppTheme.statusPaid),
      (icon: Icons.hourglass_bottom_outlined, label: 'Pending Amount', value: bill.balance, color: AppTheme.statusPending),
      (icon: Icons.currency_rupee, label: 'Late Fee', value: bill.lateFee, color: AppTheme.statusDue),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 8,
      crossAxisSpacing: 8,
      childAspectRatio: 2.6,
      children: tiles.map((tile) {
        return Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
          child: Row(
            children: [
              CircleAvatar(
                radius: 16,
                backgroundColor: tile.color.withValues(alpha: 0.12),
                child: Icon(tile.icon, size: 16, color: tile.color),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      tile.label,
                      style: Theme.of(context).textTheme.bodySmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      formatCurrency(tile.value),
                      style: const TextStyle(fontWeight: FontWeight.bold),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child, this.padded = true});

  final String title;
  final Widget child;
  final bool padded;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: AppTheme.brandBlue,
                  fontWeight: FontWeight.bold,
                ),
          ),
          const Divider(height: 20),
          padded ? child : Padding(padding: const EdgeInsets.only(top: 0), child: child),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.icon, required this.label, required this.value, this.valueColor});

  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 18, color: AppTheme.brandBlue),
          const SizedBox(width: 12),
          Expanded(child: Text(label, style: Theme.of(context).textTheme.bodyMedium)),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: valueColor,
                ),
          ),
        ],
      ),
    );
  }
}

class _BreakdownTable extends StatelessWidget {
  const _BreakdownTable({required this.bill});

  final Bill bill;

  @override
  Widget build(BuildContext context) {
    final headerStyle = Theme.of(context).textTheme.labelMedium?.copyWith(fontWeight: FontWeight.bold);

    return Column(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
          decoration: BoxDecoration(color: Theme.of(context).colorScheme.surfaceContainerHighest),
          child: Row(
            children: [
              Expanded(child: Text('Particulars', style: headerStyle)),
              Text('Amount (₹)', style: headerStyle),
            ],
          ),
        ),
        ...bill.breakdown.map(
          (line) => Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 10),
            child: Row(
              children: [
                Expanded(child: Text(line.label)),
                Text(line.amount.toStringAsFixed(2)),
              ],
            ),
          ),
        ),
        const Divider(height: 1),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 10),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  'Total',
                  style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.brandBlue),
                ),
              ),
              Text(
                bill.amount.toStringAsFixed(2),
                style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.brandBlue),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
