import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

/// Society admin screens (water readings, payment status) - backed by the
/// admin-only /api/v1/admin/* endpoints (role:admin on the backend).

double? _toDouble(Object? value) => value == null ? null : (value as num).toDouble();

/// The society's billing rates: amount = units x waterUnitRate + fixedMaintenance.
class BillingRates {
  const BillingRates({required this.fixedMaintenance, required this.waterUnitRate});

  factory BillingRates.fromJson(Map<String, dynamic> json) => BillingRates(
        fixedMaintenance: _toDouble(json['fixed_maintenance']) ?? 0,
        waterUnitRate: _toDouble(json['water_unit_rate']) ?? 0,
      );

  final double fixedMaintenance;
  final double waterUnitRate;

  double billFor(double units) => units * waterUnitRate + fixedMaintenance;
}

class WaterBlock {
  const WaterBlock({required this.id, required this.name, required this.flatsCount, required this.enteredCount});

  factory WaterBlock.fromJson(Map<String, dynamic> json) => WaterBlock(
        id: json['id'] as int,
        name: json['name'] as String,
        flatsCount: json['flats_count'] as int? ?? 0,
        enteredCount: json['entered_count'] as int? ?? 0,
      );

  final int id;
  final String name;
  final int flatsCount;
  final int enteredCount;

  bool get isComplete => flatsCount > 0 && enteredCount >= flatsCount;
}

class WaterBlocks {
  const WaterBlocks({required this.rates, required this.blocks});

  final BillingRates rates;
  final List<WaterBlock> blocks;
}

/// One flat's row on the reading sheet for a month.
class WaterReadingRow {
  const WaterReadingRow({
    required this.flatId,
    required this.flatNumber,
    required this.flatLabel,
    required this.previousReading,
    required this.currentReading,
    required this.hasHistory,
    required this.billAmount,
    required this.billStatus,
    required this.locked,
  });

  factory WaterReadingRow.fromJson(Map<String, dynamic> json) => WaterReadingRow(
        flatId: json['flat_id'] as int,
        flatNumber: json['flat_number'] as String,
        flatLabel: json['flat_label'] as String? ?? json['flat_number'] as String,
        previousReading: _toDouble(json['previous_reading']),
        currentReading: _toDouble(json['current_reading']),
        hasHistory: json['has_history'] as bool? ?? false,
        billAmount: _toDouble(json['bill_amount']),
        billStatus: json['bill_status'] as String?,
        locked: json['locked'] as bool? ?? false,
      );

  final int flatId;
  final String flatNumber;
  final String flatLabel;
  final double? previousReading;
  final double? currentReading;

  /// False the first time a flat is read: its previous reading must be typed in.
  final bool hasHistory;
  final double? billAmount;
  final String? billStatus;

  /// The bill already has a payment - the reading can no longer change.
  final bool locked;

  bool get isEntered => currentReading != null;
}

class WaterSheet {
  const WaterSheet({required this.rates, required this.rows});

  final BillingRates rates;
  final List<WaterReadingRow> rows;
}

class PaymentPeriod {
  const PaymentPeriod({required this.title, required this.bills});

  factory PaymentPeriod.fromJson(Map<String, dynamic> json) =>
      PaymentPeriod(title: json['title'] as String, bills: json['bills'] as int? ?? 0);

  final String title;
  final int bills;
}

class PaymentSummary {
  const PaymentSummary({
    required this.totalBilled,
    required this.totalCollected,
    required this.totalPending,
    required this.paidCount,
    required this.pendingCount,
  });

  factory PaymentSummary.fromJson(Map<String, dynamic> json) => PaymentSummary(
        totalBilled: _toDouble(json['total_billed']) ?? 0,
        totalCollected: _toDouble(json['total_collected']) ?? 0,
        totalPending: _toDouble(json['total_pending']) ?? 0,
        paidCount: json['paid_count'] as int? ?? 0,
        pendingCount: json['pending_count'] as int? ?? 0,
      );

  final double totalBilled;
  final double totalCollected;
  final double totalPending;
  final int paidCount;
  final int pendingCount;
}

class FlatPayment {
  const FlatPayment({
    required this.billId,
    required this.flatNumber,
    required this.flatLabel,
    required this.residentName,
    required this.amount,
    required this.paid,
    required this.balance,
    required this.status,
    required this.dueDate,
    required this.paidOn,
    required this.paymentMethod,
  });

  factory FlatPayment.fromJson(Map<String, dynamic> json) => FlatPayment(
        billId: json['bill_id'] as int,
        flatNumber: json['flat_number'] as String? ?? '-',
        flatLabel: json['flat_label'] as String? ?? json['flat_number'] as String? ?? '-',
        residentName: json['resident_name'] as String?,
        amount: _toDouble(json['amount']) ?? 0,
        paid: _toDouble(json['paid']) ?? 0,
        balance: _toDouble(json['balance']) ?? 0,
        status: json['status'] as String,
        dueDate: json['due_date'] as String?,
        paidOn: json['paid_on'] as String?,
        paymentMethod: json['payment_method'] as String?,
      );

  final int billId;
  final String flatNumber;
  final String flatLabel;
  final String? residentName;
  final double amount;
  final double paid;
  final double balance;
  final String status;
  final String? dueDate;
  final String? paidOn;
  final String? paymentMethod;

  bool get isPaid => status == 'paid';
}

class PaymentStatusList {
  const PaymentStatusList({required this.title, required this.summary, required this.items});

  final String? title;
  final PaymentSummary summary;
  final List<FlatPayment> items;
}

class SocietyAdminRepository {
  SocietyAdminRepository(this._client);

  final ApiClient _client;

  Future<WaterBlocks> waterBlocks(String month) async {
    final data = (await _client.get('/admin/water-readings/blocks', query: {'month': month}))['data'] as Map<String, dynamic>;

    return WaterBlocks(
      rates: BillingRates.fromJson(data['rates'] as Map<String, dynamic>),
      blocks: (data['blocks'] as List).map((b) => WaterBlock.fromJson(b as Map<String, dynamic>)).toList(),
    );
  }

  Future<WaterSheet> waterSheet(String month, int blockId) async {
    final data = (await _client.get('/admin/water-readings', query: {'month': month, 'block_id': blockId}))['data'] as Map<String, dynamic>;

    return WaterSheet(
      rates: BillingRates.fromJson(data['rates'] as Map<String, dynamic>),
      rows: (data['rows'] as List).map((r) => WaterReadingRow.fromJson(r as Map<String, dynamic>)).toList(),
    );
  }

  /// Returns the server's confirmation message ("Readings saved and bills generated for 3 flats.").
  Future<String> saveWaterReadings(String month, List<Map<String, dynamic>> readings) async {
    final response = await _client.post('/admin/water-readings', data: {'month': month, 'readings': readings});
    return response['message'] as String? ?? 'Readings saved.';
  }

  Future<List<PaymentPeriod>> paymentPeriods() async {
    final response = await _client.get('/admin/payments/periods');
    return (response['data'] as List).map((p) => PaymentPeriod.fromJson(p as Map<String, dynamic>)).toList();
  }

  Future<PaymentStatusList> payments({String? title}) async {
    final data = (await _client.get('/admin/payments', query: {'title': ?title}))['data'] as Map<String, dynamic>;

    return PaymentStatusList(
      title: data['title'] as String?,
      summary: PaymentSummary.fromJson(data['summary'] as Map<String, dynamic>),
      items: (data['items'] as List).map((i) => FlatPayment.fromJson(i as Map<String, dynamic>)).toList(),
    );
  }
}

final societyAdminRepositoryProvider = Provider<SocietyAdminRepository>((ref) {
  return SocietyAdminRepository(ref.watch(apiClientProvider));
});

final waterBlocksProvider = FutureProvider.autoDispose.family<WaterBlocks, String>((ref, month) {
  return ref.watch(societyAdminRepositoryProvider).waterBlocks(month);
});

final waterSheetProvider = FutureProvider.autoDispose.family<WaterSheet, (String, int)>((ref, key) {
  return ref.watch(societyAdminRepositoryProvider).waterSheet(key.$1, key.$2);
});

final paymentPeriodsProvider = FutureProvider.autoDispose<List<PaymentPeriod>>((ref) {
  return ref.watch(societyAdminRepositoryProvider).paymentPeriods();
});

/// null = the newest billing run.
final paymentStatusProvider = FutureProvider.autoDispose.family<PaymentStatusList, String?>((ref, title) {
  return ref.watch(societyAdminRepositoryProvider).payments(title: title);
});
