import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/family_member.dart';
import '../data/profile_repository.dart';
import '../data/vehicle.dart';

final familyMemberListProvider = FutureProvider.autoDispose<List<FamilyMember>>((ref) {
  return ref.watch(profileRepositoryProvider).familyMembers();
});

final vehicleListProvider = FutureProvider.autoDispose<List<Vehicle>>((ref) {
  return ref.watch(profileRepositoryProvider).vehicles();
});
