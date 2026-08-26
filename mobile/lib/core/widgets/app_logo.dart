import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// A code-drawn stand-in for FlatCare's app icon (navy badge, teal→blue "FC"
/// wordmark, a row of building silhouettes) — used anywhere the real PNG
/// icon isn't available yet (splash, login). Swap this out once the actual
/// logo asset is added to the project; until then it keeps the brand mark
/// consistent across the auth screens without needing an image file.
class AppLogo extends StatelessWidget {
  const AppLogo({super.key, this.size = 88, this.halo = false});

  final double size;

  /// Wraps the badge in a white card so it stays visible on a background
  /// that's close to the badge's own navy — e.g. the login/splash screens'
  /// navy banner, where the badge would otherwise blend straight into it.
  final bool halo;

  @override
  Widget build(BuildContext context) {
    final badge = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(size * 0.28),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.brandNavy, Color(0xFF123A5E)],
        ),
        boxShadow: halo
            ? null
            : [
                BoxShadow(color: Colors.black.withValues(alpha: 0.25), blurRadius: size * 0.18, offset: Offset(0, size * 0.08)),
              ],
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Positioned(
            top: size * 0.14,
            child: ShaderMask(
              shaderCallback: (bounds) => const LinearGradient(
                colors: [AppTheme.brandTeal, AppTheme.brandBlue],
              ).createShader(bounds),
              child: Text(
                'FC',
                style: TextStyle(fontSize: size * 0.4, fontWeight: FontWeight.w900, color: Colors.white, height: 1),
              ),
            ),
          ),
          Positioned(
            bottom: size * 0.16,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                _building(size * 0.09, size * 0.18, AppTheme.brandTeal),
                SizedBox(width: size * 0.025),
                _building(size * 0.12, size * 0.26, Colors.white),
                SizedBox(width: size * 0.025),
                _building(size * 0.09, size * 0.20, AppTheme.brandBlue),
              ],
            ),
          ),
        ],
      ),
    );

    if (!halo) return badge;

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
      child: badge,
    );
  }

  Widget _building(double width, double height, Color color) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(2)),
    );
  }
}
