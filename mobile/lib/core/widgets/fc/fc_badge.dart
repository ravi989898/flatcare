import 'package:flutter/material.dart';

import '../../theme/app_colors.dart';

/// A small tinted pill for a relation, type, role or status label.
class FcBadge extends StatelessWidget {
  const FcBadge({super.key, required this.label, this.color = AppColors.primary, this.icon});

  final String label;
  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: AppColors.soft(color), borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: 13, color: color), const SizedBox(width: 4)],
          Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 12)),
        ],
      ),
    );
  }
}
