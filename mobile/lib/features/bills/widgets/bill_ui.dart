import 'dart:io';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../data/bill.dart';
import '../data/bill_repository.dart';

/// Shared look and wording for the bills screens (My Bills, the bill
/// details and the payment confirmation), so a bill's status, due date and
/// amount read the same everywhere:
///  * red   - unpaid or overdue (money owed, act now)
///  * amber - partially paid
///  * green - paid
///  * brand blue - payment actions
extension BillDisplay on Bill {
  DateTime? get _due => dueDate == null ? null : DateTime.tryParse(dueDate!);

  /// Days until the due date (negative once it has passed); null without one.
  int? get daysToDue {
    final due = _due;
    if (due == null) return null;

    final now = DateTime.now();
    return DateTime(due.year, due.month, due.day).difference(DateTime(now.year, now.month, now.day)).inDays;
  }

  /// The server marks a bill `overdue` when its due date passes; the date is
  /// also checked here so a bill doesn't read "Unpaid" for the day or two
  /// before that status is refreshed.
  bool get isOverdue => isPending && (status == 'overdue' || (daysToDue ?? 0) < 0);

  bool get isPartiallyPaid => isPending && paidAmount > 0;

  String get statusLabel {
    if (!isPending) return 'Paid';
    if (isOverdue) return 'Overdue';
    if (isPartiallyPaid) return 'Partially Paid';
    return 'Unpaid';
  }

  Color get statusColor {
    if (!isPending) return AppColors.success;
    if (isPartiallyPaid && !isOverdue) return AppColors.warning;
    return AppColors.danger;
  }

  /// "Due today" / "Due in 5 days" / "Overdue by 3 days" - null when paid or undated.
  String? get dueHint {
    final days = daysToDue;
    if (!isPending || days == null) return null;
    if (days < 0) return 'Overdue by ${-days} ${-days == 1 ? 'day' : 'days'}';
    if (days == 0) return 'Due today';
    if (days == 1) return 'Due tomorrow';
    return 'Due in $days days';
  }

  /// The month this bill covers, used as the card/page heading.
  String get heading => billingPeriod ?? title;

  /// The bill's own title under the heading, when it adds something (two
  /// bills for the same month are otherwise indistinguishable).
  String? get subheading => billingPeriod != null && title != billingPeriod ? title : null;

  /// The most recent payment (for "Paid on ..."), if any.
  Payment? get lastPayment {
    if (payments.isEmpty) return null;

    return payments.reduce((a, b) => (b.paymentDate ?? '').compareTo(a.paymentDate ?? '') > 0 ? b : a);
  }
}

class BillStatusBadge extends StatelessWidget {
  const BillStatusBadge({super.key, required this.bill, this.large = false});

  final Bill bill;
  final bool large;

  @override
  Widget build(BuildContext context) {
    final color = bill.statusColor;

    return Container(
      padding: EdgeInsets.symmetric(horizontal: large ? 12 : 10, vertical: large ? 6 : 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            bill.isPending ? (bill.isOverdue ? Icons.error_outline_rounded : Icons.schedule_rounded) : Icons.check_circle_rounded,
            size: large ? 16 : 14,
            color: color,
          ),
          const SizedBox(width: 4),
          Text(
            bill.statusLabel,
            style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: large ? 13 : 12),
          ),
        ],
      ),
    );
  }
}

/// The brand gradient bar with a back button, title and optional actions -
/// kept outside the loading state so the way back is always on screen.
class BillHeaderBar extends StatelessWidget {
  const BillHeaderBar({super.key, required this.title, this.actions = const []});

  final String title;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(4, 4, 8, 12),
          child: Row(
            children: [
              IconButton(
                tooltip: 'Back',
                icon: const Icon(Icons.arrow_back, color: Colors.white),
                onPressed: () => context.canPop() ? context.pop() : context.go('/home'),
              ),
              Expanded(
                child: Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
                ),
              ),
              ...actions,
            ],
          ),
        ),
      ),
    );
  }
}

/// A white rounded section with a heading, used for each block of the bill.
class BillSection extends StatelessWidget {
  const BillSection({super.key, required this.title, required this.icon, required this.child, this.trailing});

  final String title;
  final IconData icon;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.6)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                ),
              ),
              ?trailing,
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

/// Downloads the bill's PDF (a bill while unpaid, a receipt once paid - the
/// backend renders the same document with the payments on it) and opens the
/// share sheet so it can be saved, printed or sent.
Future<void> shareBillPdf(BuildContext context, WidgetRef ref, int billId, {required bool receipt}) async {
  final messenger = ScaffoldMessenger.of(context);

  // path_provider and dart:io's File don't exist on web.
  if (kIsWeb) {
    messenger.showSnackBar(const SnackBar(content: Text('Downloads are available in the FlatCare mobile app.')));
    return;
  }

  try {
    final bytes = await ref.read(billRepositoryProvider).downloadReceipt(billId);
    final dir = await getTemporaryDirectory();
    final name = receipt ? 'receipt' : 'bill';
    final file = File('${dir.path}/flatcare-$name-$billId.pdf');
    await file.writeAsBytes(bytes);

    await Share.shareXFiles([XFile(file.path)], text: receipt ? 'Maintenance payment receipt' : 'Maintenance bill');
  } on ApiException catch (e) {
    messenger.showSnackBar(SnackBar(content: Text(e.message)));
  } catch (_) {
    messenger.showSnackBar(const SnackBar(content: Text("Couldn't download the file. Please try again.")));
  }
}

/// "₹700.00" in the given size/color - the one place amounts get their weight.
class BillAmount extends StatelessWidget {
  const BillAmount(this.amount, {super.key, this.size = 16, this.color = AppColors.textPrimary, this.weight = FontWeight.w700});

  final num amount;
  final double size;
  final Color color;
  final FontWeight weight;

  @override
  Widget build(BuildContext context) {
    return Text(
      formatCurrency(amount),
      maxLines: 1,
      style: TextStyle(fontSize: size, fontWeight: weight, color: color, letterSpacing: -0.2),
    );
  }
}

/// "bank_transfer" -> "Bank Transfer", "upi" -> "UPI", "online" -> "Online".
String paymentMethodLabel(String method) => switch (method) {
      'upi' => 'UPI',
      'bank_transfer' => 'Bank Transfer',
      _ => method.isEmpty ? method : '${method[0].toUpperCase()}${method.substring(1).replaceAll('_', ' ')}',
    };
