import '../../../core/models/flat.dart';

class Payment {
  Payment({
    required this.id,
    required this.amount,
    this.paymentDate,
    this.paymentMethod,
    this.referenceNumber,
    this.notes,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'] as int,
      amount: (json['amount'] as num).toDouble(),
      paymentDate: json['payment_date'] as String?,
      paymentMethod: json['payment_method'] as String?,
      referenceNumber: json['reference_number'] as String?,
      notes: json['notes'] as String?,
    );
  }

  final int id;
  final double amount;
  final String? paymentDate;
  final String? paymentMethod;
  final String? referenceNumber;
  final String? notes;
}

/// One line of a bill's breakdown (e.g. "Fixed Maintenance", "Water Charges
/// (35 units)") — see BillResource::breakdown() on the backend for how
/// these are derived rather than typed in by hand.
class BillLineItem {
  BillLineItem({required this.label, required this.amount});

  factory BillLineItem.fromJson(Map<String, dynamic> json) {
    return BillLineItem(
      label: json['label'] as String,
      amount: (json['amount'] as num).toDouble(),
    );
  }

  final String label;
  final double amount;
}

class Bill {
  Bill({
    required this.id,
    required this.title,
    required this.amount,
    required this.paidAmount,
    required this.balance,
    required this.status,
    this.billDate,
    this.dueDate,
    this.billingPeriod,
    this.lateFee = 0,
    this.notes,
    this.breakdown = const [],
    this.flat,
    this.payments = const [],
    this.mobileNumber,
  });

  factory Bill.fromJson(Map<String, dynamic> json) {
    return Bill(
      id: json['id'] as int,
      title: json['title'] as String,
      amount: (json['amount'] as num).toDouble(),
      paidAmount: (json['paid_amount'] as num).toDouble(),
      balance: (json['balance'] as num).toDouble(),
      status: json['status'] as String,
      billDate: json['bill_date'] as String?,
      dueDate: json['due_date'] as String?,
      billingPeriod: json['billing_period'] as String?,
      lateFee: (json['late_fee'] as num?)?.toDouble() ?? 0,
      notes: json['notes'] as String?,
      breakdown: (json['breakdown'] as List?)
              ?.map((line) => BillLineItem.fromJson(line as Map<String, dynamic>))
              .toList() ??
          const [],
      flat: json['flat'] != null ? Flat.fromJson(json['flat'] as Map<String, dynamic>) : null,
      payments: (json['payments'] as List?)
              ?.map((p) => Payment.fromJson(p as Map<String, dynamic>))
              .toList() ??
          const [],
      mobileNumber: json['mobile_number'] as String?,
    );
  }

  final int id;
  final String title;
  final double amount;
  final double paidAmount;
  final double balance;
  final String status;
  final String? billDate;
  final String? dueDate;
  final String? billingPeriod;
  final double lateFee;
  final String? notes;
  final List<BillLineItem> breakdown;
  final Flat? flat;
  final List<Payment> payments;
  final String? mobileNumber;

  /// Pending/paid bucket for the list screen's two tabs — "partially_paid"
  /// and "overdue" still have money owed, so they belong with "unpaid"
  /// rather than getting a third tab for what's really the same action
  /// (pay the remaining balance).
  bool get isPending => status != 'paid';
}
