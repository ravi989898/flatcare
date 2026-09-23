import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/fc/fc.dart';
import '../../help/screens/help_line_screen.dart' show SupportContacts;

/// One collapsible section of a legal page. [points] render as a bulleted
/// list under the optional [intro] paragraph.
class LegalSection {
  const LegalSection({required this.title, required this.icon, this.intro, this.points = const []});

  final String title;
  final IconData icon;
  final String? intro;
  final List<String> points;
}

/// Shared layout for Terms & Conditions and Privacy Policy: a summary card,
/// accordion sections (the first opens by default) so long legal text stays
/// scannable on small screens, and a contact card at the end.
class LegalPage extends StatelessWidget {
  const LegalPage({
    super.key,
    required this.title,
    required this.icon,
    required this.summary,
    required this.effectiveDate,
    required this.sections,
  });

  final String title;
  final IconData icon;
  final String summary;
  final String effectiveDate;
  final List<LegalSection> sections;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(20)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(14)),
                    child: Icon(icon, color: Colors.white),
                  ),
                  const SizedBox(height: 14),
                  Text(title, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  Text(summary, style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.45)),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(999)),
                    child: Text(
                      'Effective $effectiveDate',
                      style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            for (var i = 0; i < sections.length; i++) ...[
              _SectionTile(index: i + 1, section: sections[i], initiallyExpanded: i == 0),
              const SizedBox(height: 10),
            ],
            const SizedBox(height: 6),
            const LegalContactCard(),
          ],
        ),
      ),
    );
  }
}

class _SectionTile extends StatelessWidget {
  const _SectionTile({required this.index, required this.section, required this.initiallyExpanded});

  final int index;
  final LegalSection section;
  final bool initiallyExpanded;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final body = TextStyle(fontSize: 14.5, height: 1.55, color: scheme.onSurface.withValues(alpha: 0.85));

    return Container(
      decoration: BoxDecoration(
        color: scheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: scheme.outlineVariant.withValues(alpha: 0.6)),
        boxShadow: kCardShadow,
      ),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: initiallyExpanded,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          collapsedShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 16),
          iconColor: AppColors.primary,
          leading: FcIconBox(icon: section.icon, size: 40),
          title: Text(
            '$index. ${section.title}',
            style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w700),
          ),
          expandedCrossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (section.intro != null) Text(section.intro!, style: body),
            for (final point in section.points)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      margin: const EdgeInsets.only(top: 8, right: 10),
                      width: 6,
                      height: 6,
                      decoration: const BoxDecoration(color: AppColors.secondary, shape: BoxShape.circle),
                    ),
                    Expanded(child: Text(point, style: body)),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// "Questions? Contact FlatCare" — email and phone, one tap each.
class LegalContactCard extends StatelessWidget {
  const LegalContactCard({super.key});

  @override
  Widget build(BuildContext context) {
    final phone = SupportContacts.phones.first;

    return FcListCard(
      leading: const FcIconBox(icon: Icons.support_agent_rounded, color: AppColors.accentTeal),
      title: 'Questions? We\'re here to help',
      subtitle: 'Reach the FlatCare team or your society office.',
      meta: [
        const FcMeta(Icons.email_outlined, SupportContacts.email),
        FcMeta(Icons.phone_outlined, SupportContacts.pretty(phone)),
      ],
      footer: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: () => launchUrl(Uri.parse('mailto:${SupportContacts.email}')),
              icon: const Icon(Icons.email_outlined, size: 18),
              label: const Text('Email'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: FilledButton.icon(
              onPressed: () => launchUrl(Uri.parse('tel:+91$phone')),
              icon: const Icon(Icons.call_rounded, size: 18),
              label: const Text('Call'),
            ),
          ),
          const SizedBox(width: 6),
        ],
      ),
    );
  }
}
