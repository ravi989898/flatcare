import 'dart:typed_data';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'bill.dart';
import 'razorpay_order.dart';

class BillRepository {
  BillRepository(this._client);

  final ApiClient _client;

  Future<List<Bill>> list({String? status}) async {
    final response = await _client.get('/bills', query: {
      if (status != null) 'status': status,
    });

    return (response['data'] as List).map((item) => Bill.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<Bill> show(int id) async {
    final response = await _client.get('/bills/$id');
    return Bill.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// Starts a payment — the backend computes the amount from the bill's own
  /// balance; nothing here ever sends one.
  Future<RazorpayOrderInfo> createPaymentOrder(int billId) async {
    final response = await _client.post('/bills/$billId/pay/order');
    return RazorpayOrderInfo.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// Hands Razorpay Checkout's completion callback to the backend for
  /// independent verification — see BillDetailScreen for why this response
  /// (not the checkout callback itself) is what the UI treats as success.
  Future<Bill> verifyPayment(
    int billId, {
    required String orderId,
    required String paymentId,
    required String signature,
  }) async {
    final response = await _client.post('/bills/$billId/pay/verify', data: {
      'razorpay_order_id': orderId,
      'razorpay_payment_id': paymentId,
      'razorpay_signature': signature,
    });

    return Bill.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<Uint8List> downloadReceipt(int billId) {
    return _client.downloadBytes('/bills/$billId/receipt');
  }
}

final billRepositoryProvider = Provider<BillRepository>((ref) {
  return BillRepository(ref.watch(apiClientProvider));
});
