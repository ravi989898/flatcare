import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/fc/fc.dart';
import '../../help/data/support_contacts.dart';

/// One piece of content inside a [LegalSection]. Mixing these lets a section
/// read like a real document — paragraphs, sub-headings, bullet and numbered
/// lists, and a highlighted note — rather than a single bullet list.
sealed class LegalBlock {
  const LegalBlock();

  const factory LegalBlock.p(String text) = LegalParagraph;
  const factory LegalBlock.h(String text) = LegalSubheading;
  const factory LegalBlock.bullets(List<String> items) = LegalBullets;
  const factory LegalBlock.numbered(List<String> items) = LegalNumbered;
  const factory LegalBlock.note(String text) = LegalNote;
}

class LegalParagraph extends LegalBlock {
  const LegalParagraph(this.text);
  final String text;
}

class LegalSubheading extends LegalBlock {
  const LegalSubheading(this.text);
  final String text;
}

class LegalBullets extends LegalBlock {
  const LegalBullets(this.items);
  final List<String> items;
}

class LegalNumbered extends LegalBlock {
  const LegalNumbered(this.items);
  final List<String> items;
}

class LegalNote extends LegalBlock {
  const LegalNote(this.text);
  final String text;
}

class LegalSection {
  const LegalSection({required this.title, required this.icon, required this.blocks, this.color});

  final String title;
  final IconData icon;
  final List<LegalBlock> blocks;

  /// Accent for this section's icon, numbers and bullets. When null the page
  /// cycles through [_palette] so neighbouring sections never share a color.
  final Color? color;
}

const _palette = [
  AppColors.accentSky,
  AppColors.accentTeal,
  AppColors.accentIndigo,
  AppColors.accentAmber,
  AppColors.accentRose,
  AppColors.accentViolet,
  AppColors.success,
  AppColors.info,
];

/// Shared layout for Terms & Conditions and Privacy Policy: a gradient hero,
/// a tappable table of contents, every section as its own colored card (all
/// open, so the whole document reads top to bottom), and a contact card.
class LegalPage extends StatefulWidget {
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
  State<LegalPage> createState() => _LegalPageState();
}

class _LegalPageState extends State<LegalPage> {
  late final _keys = List.generate(widget.sections.length, (_) => GlobalKey());

  Color _colorOf(int i) => widget.sections[i].color ?? _palette[i % _palette.length];

  void _jumpTo(int i) {
    final ctx = _keys[i].currentContext;
    if (ctx != null) {
      Scrollable.ensureVisible(ctx, duration: const Duration(milliseconds: 350), curve: Curves.easeOut, alignment: 0.02);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: Text(widget.title)),
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _Hero(title: widget.title, icon: widget.icon, summary: widget.summary, effectiveDate: widget.effectiveDate),
              const SizedBox(height: 16),
              _Contents(
                titles: [for (final s in widget.sections) s.title],
                colorOf: _colorOf,
                onTap: _jumpTo,
              ),
              const SizedBox(height: 16),
              for (var i = 0; i < widget.sections.length; i++) ...[
                _SectionCard(key: _keys[i], index: i + 1, section: widget.sections[i], color: _colorOf(i)),
                const SizedBox(height: 12),
              ],
              const SizedBox(height: 4),
              const LegalContactCard(),
            ],
          ),
        ),
      ),
    );
  }
}

class _Hero extends StatelessWidget {
  const _Hero({required this.title, required this.icon, required this.summary, required this.effectiveDate});

  final String title;
  final IconData icon;
  final String summary;
  final String effectiveDate;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.28), blurRadius: 16, offset: const Offset(0, 6))],
      ),
      child: Stack(
        children: [
          Positioned(top: -40, right: -30, child: _circle(140, 0.12)),
          Positioned(bottom: -50, right: 60, child: _circle(110, 0.08)),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15)),
                  child: Icon(icon, color: AppColors.primary, size: 26),
                ),
                const SizedBox(height: 14),
                Text(title, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                Text(summary, style: const TextStyle(color: Colors.white, fontSize: 14.5, height: 1.5)),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.event_available_rounded, size: 15, color: AppColors.primary),
                      const SizedBox(width: 6),
                      Text(
                        'Effective Date: $effectiveDate',
                        style: const TextStyle(color: AppColors.primary, fontSize: 12.5, fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _circle(double size, double alpha) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: alpha), shape: BoxShape.circle),
      );
}

class _Contents extends StatelessWidget {
  const _Contents({required this.titles, required this.colorOf, required this.onTap});

  final List<String> titles;
  final Color Function(int) colorOf;
  final void Function(int) onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.format_list_numbered_rounded, size: 20, color: AppColors.primary),
              SizedBox(width: 8),
              Text('Contents', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
            ],
          ),
          const SizedBox(height: 6),
          for (var i = 0; i < titles.length; i++)
            InkWell(
              borderRadius: BorderRadius.circular(10),
              onTap: () => onTap(i),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 7),
                child: Row(
                  children: [
                    _NumberDot(number: i + 1, color: colorOf(i)),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        titles[i],
                        style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                      ),
                    ),
                    Icon(Icons.chevron_right_rounded, size: 20, color: colorOf(i).withValues(alpha: 0.7)),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _NumberDot extends StatelessWidget {
  const _NumberDot({required this.number, required this.color, this.size = 26});

  final int number;
  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(color: AppColors.soft(color), shape: BoxShape.circle),
      child: Text(
        '$number',
        style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: size * 0.48),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({super.key, required this.index, required this.section, required this.color});

  final int index;
  final LegalSection section;
  final Color color;

  static const _body = TextStyle(fontSize: 14.5, height: 1.6, color: AppColors.textSecondary);

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            color: AppColors.soft(color),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [color.withValues(alpha: 0.75), color],
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(section.icon, color: Colors.white, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'SECTION ${index.toString().padLeft(2, '0')}',
                        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.8),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        section.title,
                        style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.textPrimary),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [for (final block in section.blocks) _block(block)],
            ),
          ),
        ],
      ),
    );
  }

  Widget _block(LegalBlock block) {
    return switch (block) {
      LegalParagraph(:final text) => Padding(
          padding: const EdgeInsets.only(top: 10),
          child: Text(text, style: _body),
        ),
      LegalSubheading(:final text) => Padding(
          padding: const EdgeInsets.only(top: 16),
          child: Text(text, style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
        ),
      LegalBullets(:final items) => Column(
          children: [
            for (final item in items)
              Padding(
                padding: const EdgeInsets.only(top: 9),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      margin: const EdgeInsets.only(top: 8, right: 10),
                      width: 12,
                      height: 5,
                      decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(3)),
                    ),
                    Expanded(child: Text(item, style: _body)),
                  ],
                ),
              ),
          ],
        ),
      LegalNumbered(:final items) => Column(
          children: [
            for (var i = 0; i < items.length; i++)
              Padding(
                padding: const EdgeInsets.only(top: 10),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(top: 1, right: 10),
                      child: _NumberDot(number: i + 1, color: color, size: 24),
                    ),
                    Expanded(child: Text(items[i], style: _body)),
                  ],
                ),
              ),
          ],
        ),
      LegalNote(:final text) => Container(
          margin: const EdgeInsets.only(top: 12),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.soft(color),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: color.withValues(alpha: 0.25)),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.lightbulb_outline_rounded, size: 20, color: color),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  text,
                  style: const TextStyle(fontSize: 14, height: 1.5, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                ),
              ),
            ],
          ),
        ),
    };
  }
}

/// "Questions? Contact FlatCare" — email and phone, one tap each. The
/// details come from the super admin's settings (see [supportContactsProvider]).
class LegalContactCard extends ConsumerWidget {
  const LegalContactCard({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final contacts = ref.watch(supportContactsProvider).valueOrNull;
    final phone = (contacts?.phones.isNotEmpty ?? false) ? contacts!.phones.first : null;

    return FcListCard(
      leading: const FcIconBox(icon: Icons.support_agent_rounded, color: AppColors.accentTeal),
      title: "Questions? We're here to help",
      subtitle: 'Reach the FlatCare team or your society office.',
      meta: [
        if (contacts != null) FcMeta(Icons.email_outlined, contacts.email),
        if (phone != null) FcMeta(Icons.phone_outlined, phone.display),
      ],
      footer: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: contacts == null ? null : () => launchUrl(Uri.parse('mailto:${contacts.email}')),
              icon: const Icon(Icons.email_outlined, size: 18),
              label: const Text('Email'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: FilledButton.icon(
              onPressed: phone == null ? null : () => launchUrl(Uri.parse('tel:${phone.dial}')),
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
