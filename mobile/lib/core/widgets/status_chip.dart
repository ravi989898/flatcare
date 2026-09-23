import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// A small colored pill for a status/priority string (e.g. "open",
/// "resolved", "unpaid") — used across maintenance requests, complaints,
/// bills and visitors so every list reads the same way.
class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.label});

  final String label;

  static const _colors = {
    'open': AppColors.warning,
    'in_progress': AppColors.info,
    'in_review': AppColors.info,
    'pending': AppColors.warning,
    'resolved': AppColors.success,
    'paid': AppColors.success,
    'checked_in': AppColors.success,
    'approved': AppColors.success,
    'entered': AppColors.accentTeal,
    'exited': AppColors.accentSlate,
    'closed': AppColors.accentSlate,
    'checked_out': AppColors.accentSlate,
    'cancelled': AppColors.accentSlate,
    'rejected': AppColors.danger,
    'denied': AppColors.danger,
    'overdue': AppColors.danger,
    'unpaid': AppColors.danger,
    'partially_paid': AppColors.warning,
    'urgent': AppColors.danger,
    'high': AppColors.accentAmber,
    'medium': AppColors.warning,
    'low': AppColors.accentSlate,
    'draft': AppColors.accentSlate,
    'expired': AppColors.accentSlate,
  };

  /// "checked_in" -> "Checked in"
  static String _pretty(String value) {
    final text = value.replaceAll('_', ' ');
    return text.isEmpty ? text : text[0].toUpperCase() + text.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    final color = _colors[label.toLowerCase()] ?? AppColors.accentSlate;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _pretty(label),
        style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 12),
      ),
    );
  }
}
