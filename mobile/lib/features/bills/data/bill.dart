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

class Bill {
  Bill({
    required this.id,
    required this.title,
    required this.amount,
    required this.paidAmount,
    required this.balance,
    required this.status,
    this.dueDate,
    this.notes,
    this.flat,
    this.payments = const [],
  });

  factory Bill.fromJson(Map<String, dynamic> json) {
    return Bill(
      id: json['id'] as int,
      title: json['title'] as String,
      amount: (json['amount'] as num).toDouble(),
      paidAmount: (json['paid_amount'] as num).toDouble(),
      balance: (json['balance'] as num).toDouble(),
      status: json['status'] as String,
      dueDate: json['due_date'] as String?,
      notes: json['notes'] as String?,
      flat: json['flat'] != null ? Flat.fromJson(json['flat'] as Map<String, dynamic>) : null,
      payments: (json['payments'] as List?)
              ?.map((p) => Payment.fromJson(p as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }

  final int id;
  final String title;
  final double amount;
  final double paidAmount;
  final double balance;
  final String status;
  final String? dueDate;
  final String? notes;
  final Flat? flat;
  final List<Payment> payments;
}
