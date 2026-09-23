import 'package:flutter/material.dart';

import 'app_colors.dart';

export 'app_colors.dart';

/// A single, simple Material 3 theme (light only, for now) — matches the
/// "keep the scaffold simple" call for this phase's Flutter setup.
class AppTheme {
  AppTheme._();

  /// The vivid header blue used on the home screen's top bar, the "Scan QR
  /// Code" bar, login, and now the app's Material seed color too — one
  /// consistent brand palette instead of a separate green Material default.
  ///
  /// These now alias the FlatCare color system in app_colors.dart so every
  /// older screen that still reads `AppTheme.brand*` picks up the same
  /// palette as the new components.
  static const brandBlue = AppColors.primary;
  static const brandBlueDark = AppColors.primaryDark;
  static const brandTeal = AppColors.secondary;
  static const brandNavy = AppColors.textPrimary;

  static const brandGradient = AppColors.primaryGradient;

  /// Off-white background behind white cards on the colorful gradient-header
  /// screens (bills list/detail) — shared so every such screen matches.
  static const pageBackground = AppColors.background;

  /// Status → color, shared by the bills list and detail screens so a bill's
  /// amount/badge reads the same color everywhere it appears.
  static const statusDue = AppColors.danger;
  static const statusPaid = AppColors.success;
  static const statusPending = AppColors.warning;

  static Color billStatusColor(String status) {
    return switch (status) {
      'paid' => statusPaid,
      'overdue' => statusDue,
      _ => statusPending,
    };
  }

  static const seedColor = AppColors.primary;

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(seedColor: seedColor).copyWith(
      primary: AppColors.primary,
      onPrimary: Colors.white,
      secondary: AppColors.secondary,
      error: AppColors.danger,
      surface: AppColors.surface,
      onSurface: AppColors.textPrimary,
      onSurfaceVariant: AppColors.textSecondary,
      outline: AppColors.textMuted,
      outlineVariant: AppColors.border,
    );

    return _build(colorScheme, scaffold: AppColors.background, fieldFill: AppColors.surface);
  }

  /// Only reachable by explicitly choosing "Dark" under Profile > Theme —
  /// the app defaults to light() regardless of device brightness (see
  /// ThemeModeController), so this doesn't change anyone's default look.
  static ThemeData dark() {
    final colorScheme = ColorScheme.fromSeed(seedColor: seedColor, brightness: Brightness.dark);

    return _build(colorScheme, scaffold: colorScheme.surface, fieldFill: colorScheme.surfaceContainerHighest);
  }

  static ThemeData _build(ColorScheme colorScheme, {required Color scaffold, required Color fieldFill}) {
    OutlineInputBorder border(Color color, [double width = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusSm + 2),
          borderSide: BorderSide(color: color, width: width),
        );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: scaffold,
      appBarTheme: AppBarTheme(
        backgroundColor: scaffold,
        foregroundColor: colorScheme.onSurface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        centerTitle: false,
        titleTextStyle: TextStyle(color: colorScheme.onSurface, fontSize: 20, fontWeight: FontWeight.w700),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: fieldFill,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: border(colorScheme.outlineVariant),
        enabledBorder: border(colorScheme.outlineVariant),
        focusedBorder: border(colorScheme.primary, 1.6),
        errorBorder: border(colorScheme.error),
        focusedErrorBorder: border(colorScheme.error, 1.6),
        labelStyle: TextStyle(color: colorScheme.onSurfaceVariant),
        floatingLabelStyle: TextStyle(color: colorScheme.primary, fontWeight: FontWeight.w600),
        hintStyle: TextStyle(color: colorScheme.outline),
        prefixIconColor: colorScheme.onSurfaceVariant,
        errorMaxLines: 3,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          minimumSize: const Size(0, AppSpacing.buttonHeight),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, AppSpacing.buttonHeight),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(0, AppSpacing.buttonHeight),
          side: BorderSide(color: colorScheme.outlineVariant),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: colorScheme.primary,
        foregroundColor: colorScheme.onPrimary,
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        extendedTextStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd + 2),
          side: BorderSide(color: colorScheme.outlineVariant),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radiusLg)),
        titleTextStyle: TextStyle(color: colorScheme.onSurface, fontSize: 18, fontWeight: FontWeight.w700),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      dividerTheme: DividerThemeData(color: colorScheme.outlineVariant, thickness: 1),
    );
  }
}
