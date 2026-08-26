import 'package:flutter/material.dart';

/// A single, simple Material 3 theme (light only, for now) — matches the
/// "keep the scaffold simple" call for this phase's Flutter setup.
class AppTheme {
  AppTheme._();

  /// The vivid header blue used on the home screen's top bar, the "Scan QR
  /// Code" bar, login, and now the app's Material seed color too — one
  /// consistent brand palette instead of a separate green Material default.
  static const brandBlue = Color(0xFF1E9CE0);
  static const brandBlueDark = Color(0xFF1783C0);
  static const brandTeal = Color(0xFF2FE0C0);
  static const brandNavy = Color(0xFF0B1E3D);

  static const brandGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [brandTeal, brandBlue],
  );

  /// Off-white background behind white cards on the colorful gradient-header
  /// screens (bills list/detail) — shared so every such screen matches.
  static const pageBackground = Color(0xFFEEF1FA);

  /// Status → color, shared by the bills list and detail screens so a bill's
  /// amount/badge reads the same color everywhere it appears.
  static const statusDue = Color(0xFFE0245E);
  static const statusPaid = Color(0xFF2AB930);
  static const statusPending = Color(0xFFF5A623);

  static Color billStatusColor(String status) {
    return switch (status) {
      'paid' => statusPaid,
      'overdue' => statusDue,
      _ => statusPending,
    };
  }

  static const seedColor = brandBlue;

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(seedColor: seedColor);

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: colorScheme.surface,
      appBarTheme: AppBarTheme(
        backgroundColor: colorScheme.surface,
        foregroundColor: colorScheme.onSurface,
        elevation: 0,
        centerTitle: false,
      ),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
        filled: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: colorScheme.outlineVariant),
        ),
      ),
    );
  }

  /// Only reachable by explicitly choosing "Dark" under Profile > Theme —
  /// the app defaults to light() regardless of device brightness (see
  /// ThemeModeController), so this doesn't change anyone's default look.
  static ThemeData dark() {
    final colorScheme = ColorScheme.fromSeed(seedColor: seedColor, brightness: Brightness.dark);

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: colorScheme.surface,
      appBarTheme: AppBarTheme(
        backgroundColor: colorScheme.surface,
        foregroundColor: colorScheme.onSurface,
        elevation: 0,
        centerTitle: false,
      ),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
        filled: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: colorScheme.outlineVariant),
        ),
      ),
    );
  }
}
