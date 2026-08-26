import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_logo.dart';

/// Shown only while AuthController.build() is resolving the stored token
/// (see app_router.dart's redirect) — never a real destination route.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.brandNavy,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const AppLogo(size: 96, halo: true),
            const SizedBox(height: 28),
            const CircularProgressIndicator(color: AppTheme.brandTeal),
          ],
        ),
      ),
    );
  }
}
