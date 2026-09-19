import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../data/visitor_settings.dart';
import '../data/visitor_settings_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// Visitor settings — two household switches the gate respects:
/// guests only with the resident's approval, and "house closed".
class VisitorSettingsScreen extends ConsumerStatefulWidget {
  const VisitorSettingsScreen({super.key});

  @override
  ConsumerState<VisitorSettingsScreen> createState() => _VisitorSettingsScreenState();
}

class _VisitorSettingsScreenState extends ConsumerState<VisitorSettingsScreen> {
  bool? _guestApproval;
  bool? _houseClosed;
  bool _isSaving = false;

  bool _changed(VisitorSettings saved) =>
      (_guestApproval ?? saved.guestApprovalRequired) != saved.guestApprovalRequired ||
      (_houseClosed ?? saved.houseClosed) != saved.houseClosed;

  Future<void> _save(VisitorSettings saved) async {
    setState(() => _isSaving = true);
    try {
      await ref.read(visitorSettingsRepositoryProvider).save(
            flatId: saved.flatId,
            guestApprovalRequired: _guestApproval ?? saved.guestApprovalRequired,
            houseClosed: _houseClosed ?? saved.houseClosed,
          );
      ref.invalidate(visitorSettingsProvider);
      if (!mounted) return;
      showVisitorSnack(context, 'Settings saved');
      context.pop();
    } on ApiException catch (e) {
      if (mounted) showVisitorSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final settings = ref.watch(visitorSettingsProvider);
    final saved = settings.valueOrNull;

    return VisitorScaffold(
      title: 'Settings',
      bottom: saved == null
          ? null
          : VisitorPrimaryButton(
              label: 'Save',
              loading: _isSaving,
              onPressed: _changed(saved) ? () => _save(saved) : null,
            ),
      body: AsyncView<VisitorSettings>(
        value: settings,
        onRetry: () => ref.invalidate(visitorSettingsProvider),
        builder: (context, data) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _SettingCard(
              icon: Icons.verified_user_rounded,
              iconColor: VisitorColors.success,
              iconBackground: VisitorColors.mint,
              title: 'Allow guest only if you approve?',
              description: 'The gate will ask for your approval before letting any guest in.',
              value: _guestApproval ?? data.guestApprovalRequired,
              onChanged: (value) => setState(() => _guestApproval = value),
            ),
            const SizedBox(height: 12),
            _SettingCard(
              icon: Icons.home_rounded,
              iconColor: const Color(0xFFF5A623),
              iconBackground: const Color(0xFFFFF3E0),
              title: 'Mark your house closed?',
              description: 'The gate will not let any walk-in visitor through while you are away.',
              value: _houseClosed ?? data.houseClosed,
              onChanged: (value) => setState(() => _houseClosed = value),
            ),
          ],
        ),
      ),
    );
  }
}

class _SettingCard extends StatelessWidget {
  const _SettingCard({
    required this.icon,
    required this.iconColor,
    required this.iconBackground,
    required this.title,
    required this.description,
    required this.value,
    required this.onChanged,
  });

  final IconData icon;
  final Color iconColor;
  final Color iconBackground;
  final String title;
  final String description;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return VisitorCardShell(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(color: iconBackground, borderRadius: BorderRadius.circular(14)),
            child: Icon(icon, color: iconColor, size: 26),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15, color: VisitorColors.text)),
                const SizedBox(height: 3),
                Text(description, style: const TextStyle(color: VisitorColors.muted, fontSize: 12.5, height: 1.3)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Switch(
            value: value,
            onChanged: onChanged,
            activeThumbColor: Colors.white,
            activeTrackColor: VisitorColors.primary,
          ),
        ],
      ),
    );
  }
}
