import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/fc/fc.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../visitors/data/visitor.dart';
import '../data/guard_visitor_repository.dart';
import '../providers/gatekeeper_providers.dart';
import 'purpose_style.dart';

/// Status → (label, color, icon) as the gate sees it. A resident's unused
/// pass reads "Gate Pass" rather than Pending.
(String, Color, IconData) gateStatusStyle(Visitor v) {
  if (v.isPending && !v.awaitingApproval) return ('Gate Pass', AppColors.accentIndigo, Icons.confirmation_number_rounded);
  return switch (v.status) {
    'pending' => ('Waiting', AppColors.warning, Icons.hourglass_top_rounded),
    'approved' => ('Approved', AppColors.success, Icons.verified_rounded),
    'checked_in' => ('Inside', AppColors.accentTeal, Icons.login_rounded),
    'checked_out' => ('Exited', AppColors.accentSlate, Icons.logout_rounded),
    'denied' => ('Rejected', AppColors.danger, Icons.block_rounded),
    _ => (v.status, AppColors.accentSlate, Icons.info_outline_rounded),
  };
}

/// "5 min" / "2 h 10 min" since [iso].
String waitingFor(String? iso) {
  final t = iso == null ? null : DateTime.tryParse(iso)?.toLocal();
  if (t == null) return '';
  final d = DateTime.now().difference(t);
  if (d.inMinutes < 1) return 'just now';
  if (d.inHours < 1) return '${d.inMinutes} min';
  return '${d.inHours} h ${d.inMinutes % 60} min';
}

/// The one visitor card used by every gate list (Pending Requests, Visitor
/// Log, Gate Passes): purpose-colored avatar, name, flat, purpose, time
/// line, status badge, and the next gate action (Allow Entry / Mark Exit)
/// straight from the server's can_enter / can_exit.
class GateVisitorCard extends ConsumerStatefulWidget {
  const GateVisitorCard({super.key, required this.visitor, this.showWaiting = false});

  final Visitor visitor;

  /// Pending Requests shows a live "waiting 5 min" line instead of the time.
  final bool showWaiting;

  @override
  ConsumerState<GateVisitorCard> createState() => _GateVisitorCardState();
}

class _GateVisitorCardState extends ConsumerState<GateVisitorCard> {
  bool _busy = false;

  Future<void> _act(Future<Visitor> Function(GuardVisitorRepository repo) action, String done) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      await action(ref.read(guardVisitorRepositoryProvider));
      if (mounted) showFcSnack(context, done);
    } on ApiException catch (e) {
      // e.g. 409 — the request changed under us; the refresh shows the current state.
      if (mounted) showFcSnack(context, e.message, error: true);
    } finally {
      invalidateGateLists(ref.invalidate);
      if (mounted) setState(() => _busy = false);
    }
  }

  String _timeLine(Visitor v) {
    if (widget.showWaiting) return 'Waiting ${waitingFor(v.createdAt)} for resident';
    return switch (v.status) {
      'checked_in' => 'Entered ${formatDateTime(v.checkInAt)}',
      'checked_out' => 'In ${formatDateTime(v.checkInAt)} · Out ${formatDateTime(v.checkOutAt)}',
      'approved' => 'Approved ${formatDateTime(v.approvedAt)}',
      'denied' => v.rejectedAt == null ? 'Rejected by resident' : 'Rejected ${formatDateTime(v.rejectedAt)}',
      _ when v.expectedAt != null => 'Expected ${formatDateTime(v.expectedAt)}',
      _ => 'Requested ${formatDateTime(v.createdAt)}',
    };
  }

  @override
  Widget build(BuildContext context) {
    final v = widget.visitor;
    final purpose = PurposeStyle.of(v.purpose);
    final (statusLabel, statusColor, statusIcon) = gateStatusStyle(v);
    final flat = v.flat;

    return FcListCard(
      leading: v.photoUrl != null
          ? PhotoAvatar(url: v.photoUrl, name: v.visitorName, radius: 23)
          : FcIconBox(icon: purpose.icon, color: purpose.color),
      title: v.visitorName,
      meta: [
        if (flat != null) FcMeta(Icons.apartment_rounded, flat.displayLabel),
        FcMeta(widget.showWaiting ? Icons.hourglass_bottom_rounded : Icons.schedule_rounded, _timeLine(v)),
        if (v.validUntil != null && (v.isPending || v.isApproved))
          FcMeta(Icons.timer_outlined, 'Valid till ${formatDateTime(v.validUntil)}'),
        if (v.invitedBy != null) FcMeta(Icons.badge_outlined, 'Pass by ${v.invitedBy}'),
        if (v.vehicleNumber != null && v.vehicleNumber!.isNotEmpty) FcMeta(Icons.directions_car_outlined, v.vehicleNumber!),
      ],
      badges: [
        FcBadge(label: statusLabel, color: statusColor, icon: statusIcon),
        FcBadge(label: purpose.label, color: purpose.color),
        if (v.passCode != null && v.passCode!.isNotEmpty)
          FcBadge(label: 'Code ${v.passCode}', color: AppColors.accentIndigo, icon: Icons.qr_code_2_rounded),
      ],
      trailing: v.visitorPhone != null && v.visitorPhone!.isNotEmpty
          ? Padding(
              padding: const EdgeInsets.only(right: 6),
              child: FcCardAction(
                icon: Icons.call_rounded,
                color: AppColors.success,
                tooltip: 'Call visitor',
                onPressed: () => launchUrl(Uri.parse('tel:${v.visitorPhone}')),
              ),
            )
          : null,
      footer: _busy
          ? const Center(child: SizedBox(height: 24, width: 24, child: CircularProgressIndicator(strokeWidth: 2.4)))
          : v.canEnter
              ? Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(backgroundColor: AppColors.success, minimumSize: const Size.fromHeight(46)),
                    onPressed: () => _act((r) => r.checkIn(v.id), '${v.visitorName} entered.'),
                    icon: const Icon(Icons.login_rounded),
                    label: const Text('Allow Entry'),
                  ),
                )
              : v.canExit
                  ? Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.danger,
                          side: const BorderSide(color: AppColors.danger),
                          minimumSize: const Size.fromHeight(46),
                        ),
                        onPressed: () => _act((r) => r.checkOut(v.id), '${v.visitorName} marked as exited.'),
                        icon: const Icon(Icons.logout_rounded),
                        label: const Text('Mark Exit'),
                      ),
                    )
                  : null,
    );
  }
}

/// A gradient banner at the top of each gate list: icon, big count, caption.
class GateListBanner extends StatelessWidget {
  const GateListBanner({super.key, required this.icon, required this.count, required this.caption, required this.colors});

  final IconData icon;
  final int count;
  final String caption;
  final List<Color> colors;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: colors),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.22), borderRadius: BorderRadius.circular(15)),
            child: Icon(icon, color: Colors.white, size: 27),
          ),
          const SizedBox(width: 14),
          Text('$count', style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800)),
          const SizedBox(width: 10),
          Expanded(
            child: Text(caption, style: const TextStyle(color: Colors.white, fontSize: 14.5, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}
