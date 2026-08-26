import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/emergency_contact.dart';
import '../data/emergency_contact_repository.dart';

final emergencyContactListProvider = FutureProvider.autoDispose<List<EmergencyContact>>((ref) {
  return ref.watch(emergencyContactRepositoryProvider).list();
});
