import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// A circular avatar that shows a cached network photo when a URL is
/// given, falling back to the person's initial on a brand-colored
/// background otherwise — used everywhere a resident/committee-member
/// photo shows up (Profile, Committee Members, Directory, the drawer).
/// Before this widget every avatar in the app was a bare text-initial
/// `CircleAvatar` since nothing loaded a network image yet.
class PhotoAvatar extends StatelessWidget {
  const PhotoAvatar({super.key, this.url, required this.name, this.radius = 24});

  final String? url;
  final String name;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : '?';

    if (url == null || url!.isEmpty) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: AppTheme.brandBlue.withValues(alpha: 0.15),
        child: Text(
          initial,
          style: TextStyle(color: AppTheme.brandBlue, fontWeight: FontWeight.w700, fontSize: radius * 0.7),
        ),
      );
    }

    return ClipOval(
      child: CachedNetworkImage(
        imageUrl: url!,
        width: radius * 2,
        height: radius * 2,
        fit: BoxFit.cover,
        placeholder: (context, url) => CircleAvatar(
          radius: radius,
          backgroundColor: AppTheme.brandBlue.withValues(alpha: 0.15),
          child: SizedBox(
            width: radius * 0.7,
            height: radius * 0.7,
            child: const CircularProgressIndicator(strokeWidth: 2),
          ),
        ),
        errorWidget: (context, url, error) => CircleAvatar(
          radius: radius,
          backgroundColor: AppTheme.brandBlue.withValues(alpha: 0.15),
          child: Text(
            initial,
            style: TextStyle(color: AppTheme.brandBlue, fontWeight: FontWeight.w700, fontSize: radius * 0.7),
          ),
        ),
      ),
    );
  }
}
