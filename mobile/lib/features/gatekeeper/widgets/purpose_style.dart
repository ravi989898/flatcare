import 'package:flutter/material.dart';

/// One accent color + emoji per Visitor.purposes value, shared by the
/// visitor list and the walk-in check-in form so a "delivery" entry looks
/// the same everywhere in the gate app instead of each screen picking its
/// own colors.
class PurposeStyle {
  const PurposeStyle(this.color, this.emoji);

  final Color color;
  final String emoji;

  static const _styles = {
    'guest': PurposeStyle(Color(0xFF1E9CE0), '🧑‍🤝‍🧑'),
    'delivery': PurposeStyle(Color(0xFFF5A623), '📦'),
    'cab': PurposeStyle(Color(0xFF8E5FE0), '🚕'),
    'service': PurposeStyle(Color(0xFF2AB98A), '🛠️'),
    'other': PurposeStyle(Color(0xFF6B7280), '❓'),
  };

  static PurposeStyle of(String purpose) => _styles[purpose] ?? _styles['other']!;
}
