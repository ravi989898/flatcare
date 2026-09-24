import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

class SupportPhone {
  const SupportPhone({required this.display, required this.dial, required this.whatsapp});

  factory SupportPhone.fromJson(Map<String, dynamic> json) => SupportPhone(
        display: json['display'] as String,
        dial: json['dial'] as String,
        whatsapp: json['whatsapp'] as String,
      );

  /// As the super admin typed it, e.g. "+91 96646 53896".
  final String display;

  /// For tel: links, e.g. "+919664653896".
  final String dial;

  /// Digits for wa.me links, e.g. "919664653896".
  final String whatsapp;

  Map<String, dynamic> toJson() => {'display': display, 'dial': dial, 'whatsapp': whatsapp};
}

/// FlatCare customer-service contacts. The super admin edits these under
/// Settings › Branding on the website; the app reads them from
/// GET /app-config, so a changed number shows up without an app update.
class SupportContacts {
  const SupportContacts({required this.email, required this.phones});

  factory SupportContacts.fromJson(Map<String, dynamic> json) => SupportContacts(
        email: json['email'] as String,
        phones: (json['phones'] as List).map((p) => SupportPhone.fromJson(p as Map<String, dynamic>)).toList(),
      );

  final String email;
  final List<SupportPhone> phones;

  Map<String, dynamic> toJson() => {'email': email, 'phones': [for (final p in phones) p.toJson()]};
}

class SupportContactsRepository {
  SupportContactsRepository(this._client);

  final ApiClient _client;
  final _storage = const FlutterSecureStorage();

  static const _cacheKey = 'flatcare_support_contacts';

  /// Fresh values from the server, saved locally so the last known numbers
  /// still show when the phone is offline. Throws only if the server can't
  /// be reached *and* nothing has been cached yet.
  Future<SupportContacts> fetch() async {
    try {
      final response = await _client.get('/app-config');
      final support = (response['data'] as Map<String, dynamic>)['support'] as Map<String, dynamic>;
      final contacts = SupportContacts.fromJson(support);
      await _storage.write(key: _cacheKey, value: jsonEncode(contacts.toJson()));
      return contacts;
    } catch (_) {
      final cached = await _storage.read(key: _cacheKey);
      if (cached != null) {
        return SupportContacts.fromJson(jsonDecode(cached) as Map<String, dynamic>);
      }
      rethrow;
    }
  }
}

final supportContactsRepositoryProvider = Provider<SupportContactsRepository>((ref) {
  return SupportContactsRepository(ref.watch(apiClientProvider));
});

/// Re-fetched every time a screen that shows it is opened (autoDispose), so
/// a number changed by the super admin appears the next time you look.
final supportContactsProvider = FutureProvider.autoDispose<SupportContacts>((ref) {
  return ref.watch(supportContactsRepositoryProvider).fetch();
});
