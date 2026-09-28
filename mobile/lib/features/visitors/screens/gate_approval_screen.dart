import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/push/push_service.dart';
import '../../../core/widgets/app_logo.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';

/// "Your guest is at the gate": the full-screen card a resident sees when a
/// guard raises an entry request - flat and society, the visitor's name,
/// type and gate photo (with a call button), and Deny / Approve.
///
/// Opened from the visitor-request push: by tapping the notification, by the
/// notification's full-screen intent on a locked phone (MainActivity lets
/// only this screen show over the lock screen and it hands that back in
/// [dispose]), or straight away when the push arrives with the app open
/// (see PushService). The full details stay on VisitorRequestScreen.
class GateApprovalScreen extends ConsumerStatefulWidget {
  const GateApprovalScreen({super.key, required this.id});

  final int id;

  @override
  ConsumerState<GateApprovalScreen> createState() => _GateApprovalScreenState();
}

const _lockScreen = MethodChannel('flatcare/lock_screen');

const _bgTop = Color(0xFF1C3F2B);
const _bgBottom = Color(0xFF0B1A12);
const _deny = Color(0xFFC62828);
const _approve = Color(0xFF3E9A48);
const _ink = Color(0xFF2B2F33);
const _muted = Color(0xFF7C838A);

class _GateApprovalScreenState extends ConsumerState<GateApprovalScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pulse = AnimationController(vsync: this, duration: const Duration(seconds: 2))..repeat();
  bool _busy = false;
  // Set once this device has answered: shows the outcome, then closes.
  bool? _approved;
  Timer? _closeTimer;

  @override
  void dispose() {
    _pulse.dispose();
    _closeTimer?.cancel();
    // Back to normal: the rest of the app needs the phone unlocked.
    _lockScreen.invokeMethod('setShowWhenLocked', false).catchError((_) {});
    super.dispose();
  }

  void _close() {
    if (!mounted) return;
    context.canPop() ? context.pop() : context.go('/');
  }

  Future<void> _respond(bool approve) async {
    HapticFeedback.mediumImpact();
    setState(() => _busy = true);

    try {
      final repository = ref.read(visitorRepositoryProvider);
      approve ? await repository.approve(widget.id) : await repository.reject(widget.id);
      await cancelVisitorNotification(widget.id);
      if (!mounted) return;
      setState(() => _approved = approve);
      _closeTimer = Timer(const Duration(milliseconds: 1600), _close);
    } on ApiException catch (e) {
      // Typically a 409: another resident of the flat answered first.
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message), behavior: SnackBarBehavior.floating));
      }
    } finally {
      ref.invalidate(visitorRequestProvider(widget.id));
      ref.invalidate(visitorListProvider);
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final request = ref.watch(visitorRequestProvider(widget.id));
    final societyName = ref.watch(authControllerProvider).valueOrNull?.society.name;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: _bgBottom,
        body: Stack(
          children: [
            const Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [_bgTop, _bgBottom]),
                ),
              ),
            ),
            Positioned.fill(
              child: AnimatedBuilder(
                animation: _pulse,
                builder: (context, _) => CustomPaint(painter: _RipplePainter(_pulse.value)),
              ),
            ),
            const Positioned(left: 0, right: 0, bottom: 0, height: 170, child: CustomPaint(painter: _SkylinePainter())),
            SafeArea(
              child: Column(
                children: [
                  const SizedBox(height: 12),
                  Material(
                    color: Colors.white.withValues(alpha: 0.14),
                    shape: const CircleBorder(),
                    child: IconButton(
                      tooltip: 'Close',
                      icon: const Icon(Icons.close_rounded, color: Colors.white70),
                      onPressed: _close,
                    ),
                  ),
                  Expanded(
                    child: Center(
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 16),
                        child: _Card(
                          child: request.when(
                            loading: () => const Padding(
                              padding: EdgeInsets.symmetric(vertical: 80),
                              child: Center(child: CircularProgressIndicator()),
                            ),
                            error: (error, _) => _buildError(error),
                            data: (visitor) => _buildRequest(visitor, societyName),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildError(Object error) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.wifi_off_rounded, color: _muted, size: 40),
          const SizedBox(height: 12),
          Text(
            error is ApiException ? error.message : "Couldn't load this visitor request.",
            textAlign: TextAlign.center,
            style: const TextStyle(color: _ink, fontSize: 15),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () => ref.invalidate(visitorRequestProvider(widget.id)),
            child: const Text('Try again'),
          ),
        ],
      ),
    );
  }

  Widget _buildRequest(Visitor visitor, String? societyName) {
    final kind = _VisitorKind.of(visitor.purpose);
    final phone = visitor.visitorPhone;

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(24, 22, 20, 0),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(visitor.flat?.displayLabel ?? 'Your flat', style: const TextStyle(color: _muted, fontSize: 15)),
                    if (societyName != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        societyName.toUpperCase(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: _ink, fontSize: 17, fontWeight: FontWeight.w800, letterSpacing: 0.3),
                      ),
                    ],
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                child: const AppLogo(size: 36),
              ),
            ],
          ),
        ),
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 20, vertical: 18),
          child: CustomPaint(size: Size(double.infinity, 1), painter: _DottedLinePainter()),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(24, 0, 20, 20),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      visitor.visitorName,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: _ink, fontSize: 26, fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 6),
                    Text(kind.yourLabel, style: const TextStyle(color: _muted, fontSize: 16)),
                    if (visitor.notes != null && visitor.notes!.trim().isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(visitor.notes!, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: _muted, fontSize: 13)),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.18), blurRadius: 10, offset: const Offset(0, 4))],
                    ),
                    child: PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName, radius: 46),
                  ),
                  if (phone != null && phone.isNotEmpty)
                    Positioned(
                      right: -6,
                      bottom: -2,
                      child: Material(
                        color: const Color(0xFF1E8E5A),
                        shape: const CircleBorder(side: BorderSide(color: Colors.white, width: 3)),
                        child: InkWell(
                          customBorder: const CircleBorder(),
                          onTap: () => launchUrl(Uri.parse('tel:$phone')),
                          child: const Padding(
                            padding: EdgeInsets.all(9),
                            child: Icon(Icons.call_rounded, color: Colors.white, size: 20, semanticLabel: 'Call visitor'),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ],
          ),
        ),
        Container(
          margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
          padding: const EdgeInsets.fromLTRB(16, 22, 16, 16),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(22)),
          child: _buildPanel(visitor, kind),
        ),
      ],
    );
  }

  /// The white bottom panel: the question and Deny / Approve while the
  /// request is still open, otherwise where it stands now.
  Widget _buildPanel(Visitor visitor, _VisitorKind kind) {
    if (_approved != null) {
      return _Outcome(
        approved: _approved!,
        text: _approved! ? '${visitor.visitorName} has been let in.\nThe gate has been told.' : 'Entry denied.\nThe gate has been told.',
      );
    }

    if (!visitor.canRespond) {
      final approved = visitor.isApproved || visitor.isCheckedIn || visitor.status == 'checked_out';
      return Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _Outcome(
            approved: approved,
            text: approved ? 'This visitor has already been approved.' : 'This visitor has already been denied.',
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              style: OutlinedButton.styleFrom(foregroundColor: _ink, minimumSize: const Size.fromHeight(50), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
              onPressed: _close,
              child: const Text('Close', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            ),
          ),
        ],
      );
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text.rich(
          TextSpan(
            style: const TextStyle(color: _muted, fontSize: 18, height: 1.35),
            children: [
              TextSpan(text: '${kind.subject} is '),
              const TextSpan(text: 'waiting at the gate', style: TextStyle(color: _deny, fontWeight: FontWeight.w600)),
              const TextSpan(text: ' for approval'),
            ],
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 24),
        Row(
          children: [
            Expanded(
              child: _DecisionButton(
                label: 'Deny',
                icon: Icons.close_rounded,
                color: _deny,
                onPressed: _busy ? null : () => _respond(false),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _DecisionButton(
                label: 'Approve',
                icon: Icons.check_rounded,
                color: _approve,
                busy: _busy,
                onPressed: _busy ? null : () => _respond(true),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

/// How the visitor type reads on the card: "Your Guest" / "Guest is waiting…".
class _VisitorKind {
  const _VisitorKind(this.yourLabel, this.subject);

  final String yourLabel;
  final String subject;

  static _VisitorKind of(String purpose) => switch (purpose) {
    'guest' => const _VisitorKind('Your Guest', 'Guest'),
    'delivery' => const _VisitorKind('Your Delivery', 'Delivery person'),
    'cab' => const _VisitorKind('Your Cab', 'Cab'),
    'service' => const _VisitorKind('Your Service Provider', 'Service provider'),
    _ => const _VisitorKind('Visitor', 'Visitor'),
  };
}

/// The raised, softly shaded card the whole request sits on.
class _Card extends StatelessWidget {
  const _Card({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 440),
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(38),
        color: Colors.white.withValues(alpha: 0.18),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.35), blurRadius: 30, offset: const Offset(0, 16))],
      ),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(32),
          gradient: const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFF4F5F6), Color(0xFFDCDFE2)],
          ),
        ),
        child: child,
      ),
    );
  }
}

class _DecisionButton extends StatelessWidget {
  const _DecisionButton({required this.label, required this.icon, required this.color, required this.onPressed, this.busy = false});

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback? onPressed;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    return FilledButton(
      style: FilledButton.styleFrom(
        backgroundColor: color,
        disabledBackgroundColor: color.withValues(alpha: 0.55),
        foregroundColor: Colors.white,
        disabledForegroundColor: Colors.white70,
        minimumSize: const Size.fromHeight(58),
        // The default 24px side padding wraps "Approve" on a 360dp phone.
        padding: const EdgeInsets.symmetric(horizontal: 8),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      onPressed: onPressed,
      child: busy
          ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
          : Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 22),
                const SizedBox(width: 6),
                Flexible(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    child: Text(label, maxLines: 1, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  ),
                ),
              ],
            ),
    );
  }
}

class _Outcome extends StatelessWidget {
  const _Outcome({required this.approved, required this.text});

  final bool approved;
  final String text;

  @override
  Widget build(BuildContext context) {
    final color = approved ? _approve : _deny;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        CircleAvatar(
          radius: 28,
          backgroundColor: color.withValues(alpha: 0.12),
          child: Icon(approved ? Icons.check_rounded : Icons.close_rounded, color: color, size: 34),
        ),
        const SizedBox(height: 12),
        Text(text, textAlign: TextAlign.center, style: const TextStyle(color: _ink, fontSize: 16, height: 1.35)),
      ],
    );
  }
}

class _DottedLinePainter extends CustomPainter {
  const _DottedLinePainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFFB9BEC3)
      ..strokeWidth = 1.2
      ..strokeCap = StrokeCap.round;

    for (double x = 0; x < size.width; x += 6) {
      canvas.drawLine(Offset(x, 0), Offset(x + 1.5, 0), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Soft rings spreading out from the top of the screen, like a ringing call.
class _RipplePainter extends CustomPainter {
  _RipplePainter(this.progress);

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, -size.width * 0.15);
    final maxRadius = size.height * 0.75;

    for (var i = 0; i < 4; i++) {
      final t = (progress + i / 4) % 1;
      final paint = Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.4
        ..color = const Color(0xFFBFE3C8).withValues(alpha: (1 - t) * 0.22);
      canvas.drawCircle(center, maxRadius * t, paint);
    }
  }

  @override
  bool shouldRepaint(_RipplePainter oldDelegate) => oldDelegate.progress != progress;
}

/// A faint row of apartment blocks along the bottom edge.
class _SkylinePainter extends CustomPainter {
  const _SkylinePainter();

  @override
  void paint(Canvas canvas, Size size) {
    final building = Paint()..color = Colors.black.withValues(alpha: 0.28);
    final window = Paint()..color = const Color(0xFFBFE3C8).withValues(alpha: 0.10);
    final random = math.Random(7);

    var x = -10.0;
    while (x < size.width) {
      final width = 44.0 + random.nextInt(40);
      final height = size.height * (0.35 + random.nextDouble() * 0.6);
      final rect = Rect.fromLTWH(x, size.height - height, width, height);
      canvas.drawRect(rect, building);

      for (var wy = rect.top + 12; wy < size.height - 14; wy += 16) {
        for (var wx = rect.left + 8; wx < rect.right - 12; wx += 13) {
          canvas.drawRect(Rect.fromLTWH(wx, wy, 6, 8), window);
        }
      }
      x += width + 6 + random.nextInt(14);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
