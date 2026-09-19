import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../data/visitor.dart';
import '../widgets/visitor_ui.dart';

/// Shows the shareable pass code for a visitor a resident just pre-approved
/// — reached right after saving a Gate Pass, and from a visitor's "View pass".
class GatePassScreen extends StatelessWidget {
  const GatePassScreen({super.key, required this.visitor});

  final Visitor visitor;

  String get _shareText =>
      'FlatCare Gate Pass for ${visitor.visitorName}\n'
      'Pass code: ${visitor.passCode ?? '-'}\n'
      '${visitor.expectedAt != null ? 'Valid: ${formatVisitorMoment(visitor.expectedAt)}${visitor.validUntil != null ? ' to ${formatVisitorMoment(visitor.validUntil)}' : ''}\n' : ''}'
      'Show this code at the society gate.';

  @override
  Widget build(BuildContext context) {
    return VisitorScaffold(
      title: 'Gate Pass',
      bottom: Row(
        children: [
          Expanded(
            child: OutlinedButton(
              onPressed: () => context.go('/visitors'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(52),
                foregroundColor: VisitorColors.primary,
                side: const BorderSide(color: VisitorColors.primary),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: const Text('My Visitors', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: SizedBox(
              height: 52,
              child: FilledButton.icon(
                onPressed: () => Share.share(_shareText),
                style: FilledButton.styleFrom(
                  backgroundColor: VisitorColors.primary,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                icon: const Icon(Icons.share_rounded, size: 20),
                label: const Text('Share', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 20),
              decoration: BoxDecoration(
                gradient: VisitorColors.headerGradient,
                borderRadius: BorderRadius.circular(24),
                boxShadow: const [BoxShadow(color: Color(0x3311A9E8), blurRadius: 22, offset: Offset(0, 10))],
              ),
              child: Column(
                children: [
                  const CircleAvatar(radius: 28, backgroundColor: Colors.white24, child: Icon(Icons.verified_rounded, color: Colors.white, size: 32)),
                  const SizedBox(height: 12),
                  const Text('Visitor Pre-Approved', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
                  const SizedBox(height: 18),
                  Text(
                    visitor.passCode ?? '——————',
                    style: const TextStyle(color: Colors.white, fontSize: 38, fontWeight: FontWeight.w800, letterSpacing: 6),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Share this code with the visitor or the security desk',
                    style: TextStyle(color: Colors.white70, fontSize: 12.5),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            VisitorCardShell(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Column(
                children: [
                  _InfoRow(icon: Icons.person_rounded, color: VisitorColors.primary, title: visitor.visitorName, subtitle: purposeLabel(visitor.purpose)),
                  if (visitor.visitorPhone != null && visitor.visitorPhone!.isNotEmpty)
                    _InfoRow(icon: Icons.call_rounded, color: VisitorColors.success, title: visitor.visitorPhone!, subtitle: 'Mobile'),
                  if (visitor.visitorEmail != null && visitor.visitorEmail!.isNotEmpty)
                    _InfoRow(icon: Icons.email_rounded, color: const Color(0xFF8E5FE0), title: visitor.visitorEmail!, subtitle: 'Email'),
                  if (visitor.expectedAt != null)
                    _InfoRow(
                      icon: Icons.calendar_month_rounded,
                      color: const Color(0xFFF5A623),
                      title: visitor.validUntil != null
                          ? '${formatVisitorMoment(visitor.expectedAt).split(',').first}  →  ${formatVisitorMoment(visitor.validUntil).split(',').first}'
                          : formatVisitorMoment(visitor.expectedAt),
                      subtitle: 'Valid for',
                    ),
                  if (visitor.notes != null && visitor.notes!.isNotEmpty)
                    _InfoRow(icon: Icons.assignment_rounded, color: const Color(0xFFF5A623), title: visitor.notes!, subtitle: 'Purpose'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.color, required this.title, required this.subtitle});

  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(backgroundColor: color.withValues(alpha: 0.12), child: Icon(icon, color: color, size: 20)),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5)),
      subtitle: Text(subtitle, style: const TextStyle(color: VisitorColors.muted, fontSize: 12.5)),
    );
  }
}
