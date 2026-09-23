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
];

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('About FlatCare')),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            Container(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
              decoration: BoxDecoration(
                color: scheme.surface,
                borderRadius: BorderRadius.circular(20),
                boxShadow: kCardShadow,
              ),
              child: Column(
                children: [
                  const AppLogo(size: 72),
                  const SizedBox(height: 12),
                  const Text('FlatCare', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 4),
                  const Text(
                    'Smart Living, Better Together',
                    style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'FlatCare brings residents, committees and security staff together in one simple app — so '
                    'everyday society life is safer, clearer and easier for everyone, from first-time smartphone '
                    'users to busy committee members.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: scheme.onSurfaceVariant, height: 1.5, fontSize: 14.5),
                  ),
                  const SizedBox(height: 14),
                  FutureBuilder<PackageInfo>(
                    future: PackageInfo.fromPlatform(),
                    builder: (context, snapshot) {
                      final version = snapshot.data?.version ?? '1.0.0';
                      final buildNumber = snapshot.data?.buildNumber ?? '1';
                      return FcBadge(label: 'Version $version ($buildNumber)', icon: Icons.info_outline_rounded);
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const Padding(
              padding: EdgeInsets.only(left: 4, bottom: 10),
              child: Text('What FlatCare does', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
            ),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.95,
              children: [
                for (final f in _features)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: scheme.surface,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: kCardShadow,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        FcIconBox(icon: f.icon, color: f.color, size: 42),
                        const SizedBox(height: 10),
                        Text(f.title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                        const SizedBox(height: 4),
                        Expanded(
                          child: Text(
                            f.body,
                            overflow: TextOverflow.fade,
                            style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13, height: 1.4),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 20),
            FcListCard(
              leading: const FcIconBox(icon: Icons.gavel_rounded, color: AppColors.accentSlate),
              title: 'Terms & Conditions',
              trailing: const Padding(
                padding: EdgeInsets.only(top: 12, right: 4),
                child: Icon(Icons.chevron_right_rounded),
              ),
              onTap: () => context.push('/profile/terms'),
            ),
            const SizedBox(height: 10),
            FcListCard(
              leading: const FcIconBox(icon: Icons.privacy_tip_rounded, color: AppColors.accentSlate),
              title: 'Privacy Policy',
              trailing: const Padding(
                padding: EdgeInsets.only(top: 12, right: 4),
                child: Icon(Icons.chevron_right_rounded),
              ),
              onTap: () => context.push('/profile/privacy-policy'),
            ),
            const SizedBox(height: 10),
            const LegalContactCard(),
          ],
        ),
      ),
    );
  }
}
