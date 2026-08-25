import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/preferences_storage.dart';

final preferencesStorageProvider = Provider<PreferencesStorage>((ref) => PreferencesStorage());

/// The mockup's Settings > Theme toggle. Defaults to ThemeMode.light — not
/// .system — because before this existed, main.dart set no themeMode/
/// darkTheme at all, which made the app render light regardless of device
/// brightness; defaulting to .light here preserves that exact behavior for
/// anyone who never opens this screen, rather than silently switching a
/// dark-mode device to a dark app the first time this ships.
class ThemeModeController extends AsyncNotifier<ThemeMode> {
  @override
  Future<ThemeMode> build() async {
    final saved = await ref.read(preferencesStorageProvider).readThemeMode();
    return switch (saved) {
      'dark' => ThemeMode.dark,
      'system' => ThemeMode.system,
      _ => ThemeMode.light,
    };
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    state = AsyncData(mode);
    await ref.read(preferencesStorageProvider).saveThemeMode(mode.name);
  }
}

final themeModeControllerProvider = AsyncNotifierProvider<ThemeModeController, ThemeMode>(ThemeModeController.new);
