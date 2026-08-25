import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/providers/theme_provider.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';

void main() {
  runApp(const ProviderScope(child: FlatCareApp()));
}

class FlatCareApp extends ConsumerWidget {
  const FlatCareApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    // Defaults to ThemeMode.light (see ThemeModeController) so the app
    // looks exactly as it did before a Theme setting existed, until a
    // resident explicitly opts into Dark/System under Profile > Theme.
    final themeMode = ref.watch(themeModeControllerProvider).valueOrNull ?? ThemeMode.light;

    return MaterialApp.router(
      title: 'FlatCare',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      darkTheme: AppTheme.dark(),
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
