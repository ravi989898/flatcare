// Basic smoke test: the app boots to the splash screen while the auth
// session (no stored token in this test environment) resolves, then lands
// on the login screen. It doesn't hit the real API — flutter_secure_storage
// has no platform channel in the widget-test environment, so this only
// verifies the app shell wires together without crashing.

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:flatcare_mobile/main.dart';

void main() {
  testWidgets('App boots without crashing', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FlatCareApp()));
    await tester.pump();

    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
