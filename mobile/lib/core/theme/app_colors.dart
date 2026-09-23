import 'package:flutter/material.dart';

/// The FlatCare color system — every screen should pull colors from here
/// instead of hard-coding hex values or Material's `Colors.*` swatches, so
/// the app reads as one brand.
///
/// | Token        | Use                                                    |
/// |--------------|--------------------------------------------------------|
/// | primary      | Brand teal-blue: primary buttons, FABs, active states  |
/// | secondary    | Cyan: secondary actions, highlights, links             |
/// | success      | Approved, active, paid, checked-in                     |
/// | warning      | Pending, attention needed, expiring                    |
/// | danger       | Rejected, failed, delete/destructive actions           |
/// | info         | Informational banners and hints                        |
/// | background   | Blue-tinted page background behind cards               |
/// | surface      | White cards, sheets and dialogs                        |
/// | border       | Card outlines, dividers, input borders                 |
/// | textPrimary  | Headings and body text (dark navy)                     |
/// | textSecondary| Metadata / captions (muted but AA-contrast on white)   |
///
/// Each semantic color also has a `*Soft` tint for icon containers and
/// badge backgrounds.
class AppColors {
  AppColors._();

  // Brand
  static const primary = Color(0xFF0E7C9B);
  static const primaryDark = Color(0xFF0A5F77);
  static const primarySoft = Color(0xFFE0F2F7);
  static const secondary = Color(0xFF1FB6D5);
  static const secondarySoft = Color(0xFFE2F7FB);

  // Semantic
  static const success = Color(0xFF1E9E61);
  static const successSoft = Color(0xFFE3F6EC);
  static const warning = Color(0xFFE08A00);
  static const warningSoft = Color(0xFFFFF3DD);
  static const danger = Color(0xFFD93F4C);
  static const dangerSoft = Color(0xFFFDE8EA);
  static const info = Color(0xFF3B82F6);
  static const infoSoft = Color(0xFFE6F0FE);

  // Neutrals
  static const background = Color(0xFFF1F6FA);
  static const surface = Colors.white;
  static const border = Color(0xFFDDE6EE);
  static const textPrimary = Color(0xFF12263A);
  static const textSecondary = Color(0xFF55677A);
  static const textMuted = Color(0xFF7A8A9A);

  // Category accents — used to tell relation/vehicle/role categories apart
  // at a glance. Deliberately a short, muted set so lists never look like a
  // rainbow.
  static const accentTeal = Color(0xFF0E9384);
  static const accentIndigo = Color(0xFF5B5BD6);
  static const accentRose = Color(0xFFD1467A);
  static const accentAmber = Color(0xFFC77C02);
  static const accentSky = Color(0xFF0B84C6);
  static const accentViolet = Color(0xFF8E4EC6);
  static const accentSlate = Color(0xFF5E7185);

  static const primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [secondary, primary],
  );

  /// A soft tint of any accent, for icon containers and badge fills.
  static Color soft(Color color) => color.withValues(alpha: 0.12);
}

/// Spacing, radius and size tokens shared by the FlatCare components.
class AppSpacing {
  AppSpacing._();

  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const xl = 24.0;

  static const radiusSm = 10.0;
  static const radiusMd = 14.0;
  static const radiusLg = 20.0;

  static const inputHeight = 52.0;
  static const buttonHeight = 50.0;
  static const iconBox = 46.0;
}

/// The one elevation style used by FlatCare cards — a soft, low shadow
/// rather than Material's grey outline.
const List<BoxShadow> kCardShadow = [
  BoxShadow(color: Color(0x0F12263A), blurRadius: 12, offset: Offset(0, 4)),
];
