/// What POST /bills/{id}/pay/order returns — everything the Razorpay
/// Checkout SDK needs to open, plus the bill id it belongs to. Notably
/// absent: any amount the app chose — `amountPaise` is only ever an echo
/// of what the backend computed from the bill's own balance.
class RazorpayOrderInfo {
  RazorpayOrderInfo({
    required this.razorpayOrderId,
    required this.amountPaise,
    required this.currency,
    required this.key,
    required this.name,
    required this.description,
    required this.billId,
    this.prefillName,
    this.prefillEmail,
    this.prefillContact,
  });

  factory RazorpayOrderInfo.fromJson(Map<String, dynamic> json) {
    final prefill = json['prefill'] as Map<String, dynamic>?;

    return RazorpayOrderInfo(
      razorpayOrderId: json['razorpay_order_id'] as String,
      amountPaise: json['amount'] as int,
      currency: json['currency'] as String? ?? 'INR',
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? 'FlatCare',
      description: json['description'] as String? ?? '',
      billId: json['bill_id'] as int,
      prefillName: prefill?['name'] as String?,
      prefillEmail: prefill?['email'] as String?,
      prefillContact: prefill?['contact'] as String?,
    );
  }

  final String razorpayOrderId;
  final int amountPaise;
  final String currency;
  final String key;
  final String name;
  final String description;
  final int billId;
  final String? prefillName;
  final String? prefillEmail;
  final String? prefillContact;
}
