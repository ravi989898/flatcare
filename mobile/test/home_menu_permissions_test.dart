import 'package:flatcare_mobile/core/models/society.dart';
import 'package:flatcare_mobile/core/models/user_profile.dart';
import 'package:flatcare_mobile/features/auth/providers/auth_provider.dart';
import 'package:flatcare_mobile/features/bills/providers/bill_providers.dart';
import 'package:flatcare_mobile/features/home/data/app_menu_repository.dart';
import 'package:flatcare_mobile/features/home/providers/app_menu_providers.dart';
import 'package:flatcare_mobile/features/home/screens/home_screen.dart';
import 'package:flatcare_mobile/features/notifications/providers/notification_providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

/// Home menu vs. App Permission: the Society Admin tiles (Water Readings,
/// Payment Status) appear for another role only when the society grants them.
class _FakeAuth extends AuthController {
  _FakeAuth(this.roles);

  final List<String> roles;

  @override
  Future<AuthState?> build() async => AuthState(
        society: Society(id: 1, name: 'Madhav Residency', slug: 'madhav'),
        user: UserProfile(id: 7, name: 'Ravi', email: 'ravi@example.test', status: 'active', roles: roles),
      );
}

const _managed = {'app-water-readings', 'app-payment-status'};

Future<void> _pumpHome(WidgetTester tester, {required List<String> roles, required Set<String> visible}) async {
  tester.view.physicalSize = const Size(1080, 4000);
  tester.view.devicePixelRatio = 2;
  addTearDown(tester.view.reset);

  await tester.pumpWidget(ProviderScope(
    overrides: [
      authControllerProvider.overrideWith(() => _FakeAuth(roles)),
      appMenuAccessProvider.overrideWith((ref) async => AppMenuAccess(visible: visible, managed: _managed)),
      billListProvider(null).overrideWith((ref) async => []),
      unreadNotificationCountProvider.overrideWith((ref) async => 0),
    ],
    child: const MaterialApp(home: HomeScreen()),
  ));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('Secretary granted Water Readings sees only that admin tile', (tester) async {
    await _pumpHome(tester, roles: ['secretary'], visible: {'app-water-readings'});

    expect(find.text('Society Admin'), findsOneWidget);
    expect(find.text('Water Readings'), findsOneWidget);
    expect(find.text('Payment Status'), findsNothing);
    expect(find.text('My Bills'), findsOneWidget);
  });

  testWidgets('Resident with nothing granted sees no admin tiles but the usual menu', (tester) async {
    await _pumpHome(tester, roles: ['resident'], visible: {});

    expect(find.text('Society Admin'), findsNothing);
    expect(find.text('Water Readings'), findsNothing);
    expect(find.text('My Bills'), findsOneWidget);
    expect(find.text('Complaints'), findsOneWidget);
    expect(find.text('Members'), findsOneWidget);
  });

  testWidgets('Society Admin always sees both admin tiles', (tester) async {
    await _pumpHome(tester, roles: ['admin'], visible: {});

    expect(find.text('Water Readings'), findsOneWidget);
    expect(find.text('Payment Status'), findsOneWidget);
  });
}
