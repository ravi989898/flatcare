import 'package:flutter/material.dart';

/// FlatCare's real app icon (the green house-and-leaf mark), shown on the
/// splash, login and About screens so the in-app logo always matches the
/// launcher icon. The source is assets/icon/app_icon.png, trimmed and
/// resized to assets/images/app_logo.png.
class AppLogo extends StatelessWidget {
  const AppLogo({super.key, this.size = 88, this.halo = false});

  final double size;

  /// Wraps the mark in a white card so it stays visible on a dark
  /// background — e.g. the splash screen.
  final bool halo;

  @override
  Widget build(BuildContext context) {
    final logo = Image.asset(
      'assets/images/app_logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
      semanticLabel: 'FlatCare',
    );

    if (!halo) return logo;

    final haloSize = size * 1.34;
    return Container(
      width: haloSize,
      height: haloSize,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(haloSize * 0.28),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: size * 0.22, offset: Offset(0, size * 0.1)),
        ],
      ),
      child: logo,
    );
  }
}
