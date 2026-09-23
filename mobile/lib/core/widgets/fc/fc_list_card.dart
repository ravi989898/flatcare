import 'package:flutter/material.dart';

import '../../theme/app_colors.dart';

/// One line of secondary information on a [FcListCard] — an icon plus text,
/// e.g. a phone number or flat label.
class FcMeta {
  const FcMeta(this.icon, this.text);

  final IconData icon;
  final String text;
}

/// The reusable FlatCare list card, used by every listing (Family Members,
/// Vehicles, Directory, Notifications, ...) so records scan the same way
/// everywhere. Hierarchy, top to bottom: [title] → [subtitle]/[meta] →
/// [badges] → actions ([trailing]). Extra rows (e.g. a future parking slot
/// or verification status) go in [footer] without disturbing the layout.
class FcListCard extends StatelessWidget {
  const FcListCard({
    super.key,
    this.leading,
    required this.title,
    this.titleStyle,
    this.subtitle,
    this.meta = const [],
    this.badges = const [],
    this.trailing,
    this.footer,
    this.onTap,
    this.highlight = false,
  });

  final Widget? leading;
  final String title;
  final TextStyle? titleStyle;
  final String? subtitle;
  final List<FcMeta> meta;
  final List<Widget> badges;
  final Widget? trailing;
  final Widget? footer;
  final VoidCallback? onTap;

  /// Draws a primary-tinted edge and fill — e.g. an unread notification.
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final radius = BorderRadius.circular(AppSpacing.radiusMd + 2);

    return Container(
      decoration: BoxDecoration(
        color: highlight ? Color.alphaBlend(AppColors.primary.withValues(alpha: 0.05), scheme.surface) : scheme.surface,
        borderRadius: radius,
        border: Border.all(
          color: highlight ? AppColors.primary.withValues(alpha: 0.35) : scheme.outlineVariant.withValues(alpha: 0.6),
        ),
        boxShadow: kCardShadow,
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: radius,
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 8, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (leading != null) ...[leading!, const SizedBox(width: 14)],
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: titleStyle ??
                                TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: scheme.onSurface, height: 1.25),
                          ),
                          if (subtitle != null) ...[
                            const SizedBox(height: 3),
                            Text(subtitle!, style: TextStyle(fontSize: 13.5, color: scheme.onSurfaceVariant, height: 1.35)),
                          ],
                          for (final line in meta) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Icon(line.icon, size: 15, color: scheme.onSurfaceVariant),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    line.text,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(fontSize: 13.5, color: scheme.onSurfaceVariant),
                                  ),
                                ),
                              ],
                            ),
                          ],
                          if (badges.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Wrap(spacing: 6, runSpacing: 6, children: badges),
                          ],
                        ],
                      ),
                    ),
                    if (trailing != null) trailing! else const SizedBox(width: 6),
                  ],
                ),
                if (footer != null) ...[const SizedBox(height: 10), footer!],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// A 40px round tinted icon button for card actions (call, delete, ...).
class FcCardAction extends StatelessWidget {
  const FcCardAction({super.key, required this.icon, required this.onPressed, this.color = AppColors.primary, this.tooltip});

  final IconData icon;
  final VoidCallback? onPressed;
  final Color color;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: tooltip,
      onPressed: onPressed,
      style: IconButton.styleFrom(
        backgroundColor: AppColors.soft(color),
        foregroundColor: color,
        fixedSize: const Size(40, 40),
      ),
      icon: Icon(icon, size: 20),
    );
  }
}

/// A "⋮" menu of card actions (Edit / Delete ...). Danger entries render red.
class FcCardMenuItem {
  const FcCardMenuItem({required this.label, required this.icon, required this.onSelected, this.danger = false});

  final String label;
  final IconData icon;
  final VoidCallback onSelected;
  final bool danger;
}

class FcCardMenu extends StatelessWidget {
  const FcCardMenu({super.key, required this.items});

  final List<FcCardMenuItem> items;

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<int>(
      tooltip: 'More actions',
      icon: Icon(Icons.more_vert, color: Theme.of(context).colorScheme.onSurfaceVariant),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      onSelected: (index) => items[index].onSelected(),
      itemBuilder: (context) => [
        for (var i = 0; i < items.length; i++)
          PopupMenuItem<int>(
            value: i,
            child: Row(
              children: [
                Icon(items[i].icon, size: 20, color: items[i].danger ? AppColors.danger : AppColors.textSecondary),
                const SizedBox(width: 12),
                Text(
                  items[i].label,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: items[i].danger ? AppColors.danger : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}
