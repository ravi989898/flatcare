import 'package:flutter/material.dart';

import '../../theme/app_colors.dart';

/// A colored rounded-square icon container — the leading visual on every
/// FlatCare list card, menu row and empty state, so icons always sit on the
/// same shape and tint.
class FcIconBox extends StatelessWidget {
  const FcIconBox({
    super.key,
    required this.icon,
    this.color = AppColors.primary,
    this.size = AppSpacing.iconBox,
    this.circle = false,
  });

  final IconData icon;
  final Color color;
  final double size;
  final bool circle;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: AppColors.soft(color),
        shape: circle ? BoxShape.circle : BoxShape.rectangle,
        borderRadius: circle ? null : BorderRadius.circular(size * 0.3),
      ),
      child: Icon(icon, color: color, size: size * 0.5),
    );
  }
}

/// A circular initials avatar tinted with [color] — for people without a
/// photo (family members, directory entries).
class FcInitialsAvatar extends StatelessWidget {
  const FcInitialsAvatar({super.key, required this.name, this.color = AppColors.primary, this.size = AppSpacing.iconBox});

  final String name;
  final Color color;
  final double size;

  static String initialsOf(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(color: AppColors.soft(color), shape: BoxShape.circle),
      child: Text(
        initialsOf(name),
        style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: size * 0.36),
      ),
    );
  }
}
