import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../core/widgets/app_logo.dart';
import '../../../core/widgets/fc/fc.dart';
import '../widgets/legal_page.dart';

class _Feature {
  const _Feature(this.icon, this.color, this.title, this.body);

  final IconData icon;
  final Color color;
  final String title;
  final String body;
}

const _features = [
  _Feature(Icons.shield_rounded, AppColors.accentTeal, 'Safer gates', 'Approve visitors from your phone and know who is at the gate.'),
  _Feature(Icons.receipt_long_rounded, AppColors.accentAmber, 'Clear bills', 'See maintenance dues and pay online with instant receipts.'),
  _Feature(Icons.campaign_rounded, AppColors.accentSky, 'Stay informed', 'Notices, events and polls from your committee in one place.'),
  _Feature(Icons.build_circle_rounded, AppColors.accentIndigo, 'Quick fixes', 'Raise complaints and track them until they are resolved.'),
  _Feature(Icons.contacts_rounded, AppColors.accentRose, 'Directory', 'Find neighbours and important contacts in seconds.'),
  _Feature(Icons.family_restroom_rounded, AppColors.accentViolet, 'Your household', 'Keep family members and vehicles up to date.'),
];

class _Value {
  const _Value(this.icon, this.color, this.title, this.body);

  final IconData icon;
  final Color color;
  final String title;
  final String body;
}

const _values = [
  _Value(Icons.touch_app_rounded, AppColors.accentSky, 'Simple for everyone',
      'Big buttons, clear words and no training needed — easy enough for grandparents and first-time smartphone users.'),
  _Value(Icons.lock_rounded, AppColors.accentTeal, 'Safe by design',
      'OTP sign-in, role-based access and separate data for every society, so your information stays where it belongs.'),
  _Value(Icons.visibility_rounded, AppColors.accentAmber, 'Open & transparent',
      'Bills, payments, notices and complaint status are visible to the right people, building trust between residents and committee.'),
  _Value(Icons.favorite_rounded, AppColors.accentRose, 'Built around community',
      'FlatCare is designed for Indian housing societies — the way neighbours, committees and security really work together.'),
];

class _Role {
  const _Role(this.icon, this.color, this.title, this.points);

  final IconData icon;
  final Color color;
  final String title;
  final List<String> points;
}

const _roles = [
  _Role(Icons.home_rounded, AppColors.accentSky, 'For residents', [
    'Approve visitors and share gate passes',
    'Pay maintenance and download receipts',
    'Raise complaints and follow their progress',
  ]),
  _Role(Icons.groups_rounded, AppColors.accentIndigo, 'For the committee', [
    'Manage residents, flats and bills',
    'Publish notices, polls and events',
    'Track complaints and collections at a glance',
  ]),
  _Role(Icons.security_rounded, AppColors.accentTeal, 'For security', [
    'Check in walk-in and expected visitors',
    'Ask residents for approval in one tap',
    'Keep a searchable visitor and vehicle log',
  ]),
];

const _faqs = [
  (
    'How do I get a FlatCare account?',
    'Your society admin adds you using your mobile number. Once added, simply sign in with the OTP sent to that number — no password to remember.',
  ),
  (
    'Is my personal information safe?',
    'Yes. Only people in your own society can see your details, and each role sees only what it needs. We never sell your data. Read our Privacy Policy to learn more.',
  ),
  (
    'What happens if I move to another flat or society?',
    'Ask your society office to update your residency. Your old society will no longer see your new details, and your new society can add you to their FlatCare.',
  ),
  (
    'Who do I contact for help?',
    'For app questions, use Help Line in the menu. For bills, rules or complaints about your society, please contact your society office.',
  ),
];

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('About FlatCare')),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            const _Hero(),
            const SizedBox(height: 16),
            const _WhoWeAre(),
            const _Heading(icon: Icons.apps_rounded, title: 'What FlatCare does'),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.95,
              children: [for (final f in _features) _FeatureTile(feature: f)],
            ),
            const _Heading(icon: Icons.star_rounded, title: 'What drives us'),
            for (final v in _values) ...[_ValueCard(value: v), const SizedBox(height: 10)],
            const _Heading(icon: Icons.diversity_3_rounded, title: 'One app for the whole society'),
            for (final r in _roles) ...[_RoleCard(role: r), const SizedBox(height: 10)],
            const _Heading(icon: Icons.help_rounded, title: 'Common questions'),
            const _Faqs(),
            const SizedBox(height: 20),
            _LinkTile(
              icon: Icons.gavel_rounded,
              color: AppColors.accentSlate,
              title: 'Terms & Conditions',
              onTap: () => context.push('/profile/terms'),
            ),
            const SizedBox(height: 10),
            _LinkTile(
              icon: Icons.privacy_tip_rounded,
              color: AppColors.accentRose,
              title: 'Privacy Policy',
              onTap: () => context.push('/profile/privacy-policy'),
            ),
            const SizedBox(height: 10),
            const LegalContactCard(),
            const SizedBox(height: 20),
            const Center(
              child: Text(
                'Made with care in India  •  © 2026 FlatCare',
                style: TextStyle(color: AppColors.textMuted, fontSize: 12.5, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Hero extends StatelessWidget {
  const _Hero();

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.28), blurRadius: 16, offset: const Offset(0, 6))],
      ),
      child: Stack(
        children: [
          Positioned(top: -40, right: -30, child: _circle(150, 0.12)),
          Positioned(bottom: -60, left: -30, child: _circle(140, 0.08)),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 22),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(22),
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 14, offset: const Offset(0, 6))],
                  ),
                  child: const AppLogo(size: 64),
                ),
                const SizedBox(height: 14),
                const Text('FlatCare', style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                const Text(
                  'Smart Living, Better Together',
                  style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 14),
                const Text(
                  'We build simple tools that make society life safer, clearer and more connected.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.white, fontSize: 17, height: 1.4, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 16),
                FutureBuilder<PackageInfo>(
                  future: PackageInfo.fromPlatform(),
                  builder: (context, snapshot) {
                    final version = snapshot.data?.version ?? '1.0.0';
                    final buildNumber = snapshot.data?.buildNumber ?? '1';
                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.info_outline_rounded, size: 15, color: AppColors.primary),
                          const SizedBox(width: 6),
                          Text(
                            'Version $version ($buildNumber)',
                            style: const TextStyle(color: AppColors.primary, fontSize: 12.5, fontWeight: FontWeight.w700),
                          ),
                        ],
                      ),
                    );
                  },
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

class _WhoWeAre extends StatelessWidget {
  const _WhoWeAre();

  @override
  Widget build(BuildContext context) {
    const body = TextStyle(fontSize: 14.5, height: 1.6, color: AppColors.textSecondary);
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FcIconBox(icon: Icons.apartment_rounded, color: AppColors.primary, size: 40),
              SizedBox(width: 12),
              Text('Who we are', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
            ],
          ),
          SizedBox(height: 12),
          Text(
            'FlatCare is a society management app that brings residents, the managing committee and security staff '
            'together in one place. From the moment a visitor arrives at the gate to the day the maintenance bill is '
            'paid, FlatCare keeps everyone informed.',
            style: body,
          ),
          SizedBox(height: 10),
          Text(
            'We believe community living should feel effortless. No more paper registers at the gate, lost receipts '
            'or notices that nobody reads — just one simple app that works for every member of the society.',
            style: body,
          ),
        ],
      ),
    );
  }
}

class _Heading extends StatelessWidget {
  const _Heading({required this.icon, required this.title});

  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 24, 4, 12),
      child: Row(
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
          ),
        ],
      ),
    );
  }
}

class _GradientIcon extends StatelessWidget {
  const _GradientIcon({required this.icon, required this.color, this.size = 42});

  final IconData icon;
  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [color.withValues(alpha: 0.75), color],
        ),
        borderRadius: BorderRadius.circular(size * 0.3),
        boxShadow: [BoxShadow(color: color.withValues(alpha: 0.30), blurRadius: 8, offset: const Offset(0, 3))],
      ),
      child: Icon(icon, color: Colors.white, size: size * 0.52),
    );
  }
}

class _FeatureTile extends StatelessWidget {
  const _FeatureTile({required this.feature});

  final _Feature feature;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _GradientIcon(icon: feature.icon, color: feature.color),
          const SizedBox(height: 10),
          Text(feature.title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: AppColors.textPrimary)),
          const SizedBox(height: 4),
          Expanded(
            child: Text(
              feature.body,
              overflow: TextOverflow.fade,
              style: const TextStyle(color: AppColors.textSecondary, fontSize: 13, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}

class _ValueCard extends StatelessWidget {
  const _ValueCard({required this.value});

  final _Value value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _GradientIcon(icon: value.icon, color: value.color, size: 46),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(value.title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
                const SizedBox(height: 4),
                Text(value.body, style: const TextStyle(fontSize: 13.5, height: 1.5, color: AppColors.textSecondary)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RoleCard extends StatelessWidget {
  const _RoleCard({required this.role});

  final _Role role;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: AppColors.soft(role.color),
            child: Row(
              children: [
                _GradientIcon(icon: role.icon, color: role.color, size: 38),
                const SizedBox(width: 12),
                Text(role.title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 14),
            child: Column(
              children: [
                for (final p in role.points)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.check_circle_rounded, size: 18, color: role.color),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(p, style: const TextStyle(fontSize: 14, height: 1.4, color: AppColors.textSecondary)),
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
}

class _Faqs extends StatelessWidget {
  const _Faqs();

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: kCardShadow),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: Column(
          children: [
            for (var i = 0; i < _faqs.length; i++) ...[
              if (i > 0) const Divider(height: 1, indent: 16, endIndent: 16),
              ExpansionTile(
                tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
                childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
                iconColor: AppColors.primary,
                collapsedIconColor: AppColors.primary,
                expandedCrossAxisAlignment: CrossAxisAlignment.start,
                leading: Container(
                  width: 28,
                  height: 28,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(color: AppColors.soft(AppColors.primary), shape: BoxShape.circle),
                  child: const Text('?', style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w800)),
                ),
                title: Text(
                  _faqs[i].$1,
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                ),
                children: [
                  Text(_faqs[i].$2, style: const TextStyle(fontSize: 14, height: 1.55, color: AppColors.textSecondary)),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _LinkTile extends StatelessWidget {
  const _LinkTile({required this.icon, required this.color, required this.title, required this.onTap});

  final IconData icon;
  final Color color;
  final String title;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            children: [
              _GradientIcon(icon: icon, color: color, size: 40),
              const SizedBox(width: 14),
              Expanded(
                child: Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
              ),
              Icon(Icons.chevron_right_rounded, color: color.withValues(alpha: 0.7)),
            ],
          ),
        ),
      ),
    );
  }
}
