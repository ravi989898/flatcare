import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';

/// One accent color, icon and emoji per Visitor.purposes value, shared by
/// the gate lists and the walk-in check-in form so a "delivery" entry looks
/// the same everywhere in the gate app instead of each screen picking its
/// own colors.
class PurposeStyle {
  const PurposeStyle(this.color, this.emoji, this.icon, this.label);

  final Color color;
  final String emoji;
  final IconData icon;
  final String label;

  static const _styles = {
    'guest': PurposeStyle(AppColors.accentSky, '🧑‍🤝‍🧑', Icons.people_alt_rounded, 'Guest'),
    'delivery': PurposeStyle(AppColors.accentAmber, '📦', Icons.inventory_2_rounded, 'Delivery'),
    'cab': PurposeStyle(AppColors.accentViolet, '🚕', Icons.local_taxi_rounded, 'Cab'),
    'service': PurposeStyle(AppColors.accentTeal, '🛠️', Icons.home_repair_service_rounded, 'Service'),
    'other': PurposeStyle(AppColors.accentSlate, '❓', Icons.person_pin_circle_rounded, 'Other'),
  };

  static PurposeStyle of(String purpose) => _styles[purpose] ?? _styles['other']!;
}
