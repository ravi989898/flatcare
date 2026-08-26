import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers/theme_provider.dart';

class ThemeScreen extends ConsumerWidget {
  const ThemeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final current = ref.watch(themeModeControllerProvider).valueOrNull ?? ThemeMode.light;

    Widget option(ThemeMode mode, String label, IconData icon) {
      return ListTile(
        leading: Icon(icon),
        title: Text(label),
        trailing: current == mode ? const Icon(Icons.check, color: Colors.green) : null,
        onTap: () => ref.read(themeModeControllerProvider.notifier).setThemeMode(mode),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Theme')),
      body: ListView(
        children: [
          option(ThemeMode.light, 'Light', Icons.light_mode_outlined),
          option(ThemeMode.dark, 'Dark', Icons.dark_mode_outlined),
          option(ThemeMode.system, 'System Default', Icons.settings_suggest_outlined),
        ],
      ),
    );
  }
}
