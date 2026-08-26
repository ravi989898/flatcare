import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/announcements/screens/announcement_detail_screen.dart';
import '../../features/announcements/screens/announcement_list_screen.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/auth/screens/forgot_password_screen.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/auth/screens/reset_password_screen.dart';
import '../../features/auth/screens/splash_screen.dart';
import '../../features/bills/screens/bill_detail_screen.dart';
import '../../features/bills/screens/bill_list_screen.dart';
import '../../features/committee/screens/committee_members_screen.dart';
import '../../features/complaints/screens/complaint_detail_screen.dart';
import '../../features/complaints/screens/complaint_form_screen.dart';
import '../../features/complaints/screens/complaint_list_screen.dart';
import '../../features/directory/screens/directory_list_screen.dart';
import '../../features/documents/screens/document_list_screen.dart';
import '../../features/emergency_contacts/screens/emergency_contacts_screen.dart';
import '../../features/events/screens/event_detail_screen.dart';
import '../../features/events/screens/event_list_screen.dart';
import '../../features/home/screens/home_screen.dart';
import '../../features/home/screens/my_properties_screen.dart';
import '../../features/maintenance_requests/screens/maintenance_request_detail_screen.dart';
import '../../features/maintenance_requests/screens/maintenance_request_form_screen.dart';
import '../../features/maintenance_requests/screens/maintenance_request_list_screen.dart';
import '../../features/notifications/screens/notification_list_screen.dart';
import '../../features/polls/screens/poll_list_screen.dart';
import '../../features/profile/screens/about_screen.dart';
import '../../features/profile/screens/change_password_screen.dart';
import '../../features/profile/screens/family_members_screen.dart';
import '../../features/profile/screens/help_support_screen.dart';
import '../../features/profile/screens/language_screen.dart';
import '../../features/profile/screens/notification_settings_screen.dart';
import '../../features/profile/screens/privacy_policy_screen.dart';
import '../../features/profile/screens/profile_edit_screen.dart';
import '../../features/profile/screens/profile_screen.dart';
import '../../features/profile/screens/terms_screen.dart';
import '../../features/profile/screens/theme_screen.dart';
import '../../features/profile/screens/vehicles_screen.dart';
import '../../features/service_providers/screens/service_provider_list_screen.dart';
import '../../features/visitors/data/visitor.dart';
import '../../features/visitors/screens/gate_pass_screen.dart';
import '../../features/visitors/screens/invite_visitor_screen.dart';
import '../../features/visitors/screens/visitor_list_screen.dart';

/// A ChangeNotifier that pings go_router's `refreshListenable` whenever the
/// auth session changes, so login/logout are reflected in navigation
/// immediately instead of only on the next route push.
class _AuthRefreshNotifier extends ChangeNotifier {
  _AuthRefreshNotifier(Ref ref) {
    ref.listen(authControllerProvider, (_, __) => notifyListeners());
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final refreshNotifier = _AuthRefreshNotifier(ref);

  return GoRouter(
    initialLocation: '/',
    refreshListenable: refreshNotifier,
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      final isLoggedIn = auth.valueOrNull != null;
      final isLoading = auth.isLoading && !auth.hasValue;

      final path = state.matchedLocation;
      final isAuthRoute = path == '/login' || path == '/forgot-password' || path == '/reset-password';

      if (isLoading) return null; // stay on splash until session resolves

      if (!isLoggedIn && !isAuthRoute) return '/login';
      if (isLoggedIn && (isAuthRoute || path == '/')) return '/home';
      if (!isLoggedIn && path == '/') return '/login';

      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const SplashScreen()),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/forgot-password', builder: (context, state) => const ForgotPasswordScreen()),
      GoRoute(
        path: '/reset-password',
        builder: (context, state) => ResetPasswordScreen(email: state.extra as String? ?? ''),
      ),
      GoRoute(path: '/home', builder: (context, state) => const HomeScreen()),
      GoRoute(path: '/my-properties', builder: (context, state) => const MyPropertiesScreen()),

      GoRoute(
        path: '/maintenance-requests',
        builder: (context, state) => const MaintenanceRequestListScreen(),
        routes: [
          GoRoute(path: 'new', builder: (context, state) => const MaintenanceRequestFormScreen()),
          GoRoute(
            path: ':id',
            builder: (context, state) => MaintenanceRequestDetailScreen(id: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),

      GoRoute(
        path: '/bills',
        builder: (context, state) => const BillListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (context, state) => BillDetailScreen(id: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),

      GoRoute(
        path: '/complaints',
        builder: (context, state) => const ComplaintListScreen(),
        routes: [
          GoRoute(path: 'new', builder: (context, state) => const ComplaintFormScreen()),
          GoRoute(
            path: ':id',
            builder: (context, state) => ComplaintDetailScreen(id: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),

      GoRoute(
        path: '/visitors',
        builder: (context, state) => const VisitorListScreen(),
        routes: [
          GoRoute(path: 'invite', builder: (context, state) => const InviteVisitorScreen()),
          GoRoute(
            path: 'gate-pass',
            builder: (context, state) => GatePassScreen(visitor: state.extra as Visitor),
          ),
        ],
      ),

      GoRoute(
        path: '/announcements',
        builder: (context, state) => const AnnouncementListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (context, state) => AnnouncementDetailScreen(id: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),

      GoRoute(
        path: '/events',
        builder: (context, state) => const EventListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (context, state) => EventDetailScreen(id: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),

      GoRoute(path: '/directory', builder: (context, state) => const DirectoryListScreen()),
      GoRoute(path: '/documents', builder: (context, state) => const DocumentListScreen()),
      GoRoute(path: '/emergency-contacts', builder: (context, state) => const EmergencyContactsScreen()),
      GoRoute(path: '/committee-members', builder: (context, state) => const CommitteeMembersScreen()),
      GoRoute(path: '/service-providers', builder: (context, state) => const ServiceProviderListScreen()),
      GoRoute(path: '/polls', builder: (context, state) => const PollListScreen()),
      GoRoute(path: '/notifications', builder: (context, state) => const NotificationListScreen()),

      GoRoute(
        path: '/profile',
        builder: (context, state) => const ProfileScreen(),
        routes: [
          GoRoute(path: 'edit', builder: (context, state) => const ProfileEditScreen()),
          GoRoute(path: 'family-members', builder: (context, state) => const FamilyMembersScreen()),
          GoRoute(path: 'vehicles', builder: (context, state) => const VehiclesScreen()),
          GoRoute(path: 'change-password', builder: (context, state) => const ChangePasswordScreen()),
          GoRoute(path: 'notification-settings', builder: (context, state) => const NotificationSettingsScreen()),
          GoRoute(path: 'language', builder: (context, state) => const LanguageScreen()),
          GoRoute(path: 'help-support', builder: (context, state) => const HelpSupportScreen()),
          GoRoute(path: 'about', builder: (context, state) => const AboutScreen()),
          GoRoute(path: 'theme', builder: (context, state) => const ThemeScreen()),
          GoRoute(path: 'privacy-policy', builder: (context, state) => const PrivacyPolicyScreen()),
          GoRoute(path: 'terms', builder: (context, state) => const TermsScreen()),
        ],
      ),
    ],
  );
});
