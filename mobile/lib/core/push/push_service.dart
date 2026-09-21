import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/providers/auth_provider.dart';
import '../../features/gatekeeper/providers/gatekeeper_providers.dart';
import '../../features/notifications/providers/notification_providers.dart';
import '../../features/visitors/data/visitor_repository.dart';
import '../../features/visitors/providers/visitor_providers.dart';
import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../providers/core_providers.dart';
import '../router/app_router.dart';
import '../storage/token_storage.dart';

/// Push notifications for the visitor approval flow.
///
/// The backend (App\Services\NotificationService / FcmService) sends every
/// push; this file only receives them:
///
///  * Android, visitor request: a high-priority *data-only* FCM message. The
///    system can't add buttons to a message it draws itself, so it wakes
///    [firebaseMessagingBackgroundHandler] — even with the app closed —
///    which builds the notification with Accept / Reject buttons. Tapping a
///    button calls the approve/reject API from a background isolate
///    ([onBackgroundNotificationResponse]) without opening the app.
///  * Other messages (and iOS): a normal notification the system displays;
///    tapping it opens the relevant screen.
///  * App open: [PushService] listens to onMessage, shows the same local
///    notification, and invalidates the visitor/notification providers so
///    open screens reload immediately.
///
/// Everything here is a no-op until Firebase is configured for the build
/// (android/app/google-services.json — see mobile/FCM_SETUP.md), so the app
/// still runs and works over plain API calls without it.
bool firebaseReady = false;

const _channelId = 'visitor_requests';
const _actionApprove = 'approve';
const _actionReject = 'reject';

final _local = FlutterLocalNotificationsPlugin();

/// Called from main() before runApp.
Future<void> initFirebase() async {
  if (kIsWeb) return;

  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
    firebaseReady = true;
  } catch (error) {
    debugPrint('Firebase is not configured - push notifications are disabled ($error).');
  }
}

/// Runs in a background isolate when a data message arrives with the app
/// backgrounded or terminated.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // A message with a `notification` block was already drawn by the system.
  if (message.notification != null) return;

  await _initLocalNotifications();
  await _showNotification(message.data);
}

/// A notification button (Accept / Reject) was tapped while the app isn't in
/// the foreground. Runs in a background isolate.
@pragma('vm:entry-point')
void onBackgroundNotificationResponse(NotificationResponse response) {
  unawaited(_handleResponse(response));
}

Future<void> _initLocalNotifications({DidReceiveNotificationResponseCallback? onTap}) async {
  await _local.initialize(
    settings: const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    ),
    onDidReceiveNotificationResponse: onTap,
    onDidReceiveBackgroundNotificationResponse: onBackgroundNotificationResponse,
  );

  await _local
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(const AndroidNotificationChannel(
        _channelId,
        'Visitor requests',
        description: 'Visitors waiting at the gate and gate decisions',
        importance: Importance.max,
      ));
}

Future<void> _showNotification(Map<String, dynamic> data, {String? title, String? body}) async {
  final visitorId = int.tryParse('${data['visitor_id'] ?? ''}');
  final actionable = '${data['actions'] ?? ''}'.contains(_actionApprove);

  await _local.show(
    // One notification per visitor: a decision replaces the original request.
    id: visitorId ?? DateTime.now().millisecondsSinceEpoch ~/ 1000,
    title: title ?? data['title']?.toString() ?? 'FlatCare',
    body: body ?? data['body']?.toString(),
    payload: jsonEncode(data),
    notificationDetails: NotificationDetails(
      android: AndroidNotificationDetails(
        _channelId,
        'Visitor requests',
        importance: Importance.max,
        priority: Priority.high,
        category: AndroidNotificationCategory.message,
        actions: actionable
            ? const [
                AndroidNotificationAction(_actionApprove, 'Accept', cancelNotification: true),
                AndroidNotificationAction(_actionReject, 'Reject', cancelNotification: true),
              ]
            : null,
      ),
      iOS: const DarwinNotificationDetails(),
    ),
  );
}

Map<String, dynamic> _decode(String? payload) {
  if (payload == null || payload.isEmpty) return {};

  try {
    return Map<String, dynamic>.from(jsonDecode(payload) as Map);
  } catch (_) {
    return {};
  }
}

/// Handles a tap on the notification body (opens a screen via [onOpen]) or on
/// one of its buttons (answers the request through the API, in place).
Future<void> _handleResponse(NotificationResponse response, {void Function(Map<String, dynamic> data)? onOpen}) async {
  final data = _decode(response.payload);
  final action = response.actionId;

  if (action != _actionApprove && action != _actionReject) {
    onOpen?.call(data);
    return;
  }

  final visitorId = int.tryParse('${data['visitor_id'] ?? ''}');
  if (visitorId == null) return;

  // No Riverpod here (possibly a background isolate) - a bare client that
  // reads the same stored token. The backend re-checks that this user really
  // is a resident of the request's flat, exactly as for an in-app tap.
  final repository = VisitorRepository(ApiClient(TokenStorage()));
  final approve = action == _actionApprove;

  try {
    approve ? await repository.approve(visitorId) : await repository.reject(visitorId);
    await _showNotification({'visitor_id': visitorId}, title: approve ? 'Visitor approved' : 'Visitor rejected');
  } on ApiException catch (error) {
    // e.g. "This visitor request has already been approved." (another device answered first)
    await _showNotification({'visitor_id': visitorId}, title: "Couldn't update the request", body: error.message);
  } catch (_) {
    await _showNotification({'visitor_id': visitorId}, title: "Couldn't update the request", body: 'Please open the app and try again.');
  }
}

class PushService with WidgetsBindingObserver {
  PushService(this._ref);

  final Ref _ref;
  String? _token;
  bool _started = false;

  bool get _loggedIn => _ref.read(authControllerProvider).valueOrNull != null;

  Future<void> start(GoRouter router) async {
    if (_started || !firebaseReady) return;
    _started = true;

    await _initLocalNotifications(onTap: (response) => _handleResponse(response, onOpen: (data) => _open(router, data)));
    WidgetsBinding.instance.addObserver(this);

    FirebaseMessaging.onMessage.listen((message) {
      _showNotification(
        message.data,
        title: message.notification?.title,
        body: message.notification?.body,
      );
      refreshData();
    });
    FirebaseMessaging.onMessageOpenedApp.listen((message) => _open(router, message.data));
    FirebaseMessaging.instance.onTokenRefresh.listen((token) {
      _token = token;
      if (_loggedIn) _send(token);
    });

    // Cold start from a tapped notification (system-drawn or local).
    final initial = await FirebaseMessaging.instance.getInitialMessage();
    if (initial != null) unawaited(_open(router, initial.data));

    final launch = await _local.getNotificationAppLaunchDetails();
    final launchResponse = launch?.notificationResponse;
    if ((launch?.didNotificationLaunchApp ?? false) && launchResponse != null) {
      unawaited(_handleResponse(launchResponse, onOpen: (data) => _open(router, data)));
    }
  }

  /// Registers this device's FCM token for the logged-in user (called at
  /// login / session restore and on token rotation). Safe to call repeatedly.
  Future<void> registerDevice() async {
    if (!firebaseReady) return;

    try {
      await FirebaseMessaging.instance.requestPermission();
      await _local
          .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();

      final token = await FirebaseMessaging.instance.getToken();
      if (token == null) return;

      _token = token;
      await _send(token);
    } catch (error) {
      debugPrint('Could not register for push notifications: $error');
    }
  }

  /// Stops pushes to this device; called on logout while the API token is
  /// still valid.
  Future<void> unregisterDevice() async {
    final token = _token;
    if (!firebaseReady || token == null) return;

    try {
      await _ref.read(apiClientProvider).delete('/devices', data: {'token': token});
    } catch (_) {
      // Best effort - the backend also deactivates a token FCM reports as dead.
    }
  }

  Future<void> _send(String token) async {
    await _ref.read(apiClientProvider).post('/devices', data: {
      'token': token,
      'platform': defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android',
    });
  }

  /// Reload whatever the user may be looking at: called when a push arrives
  /// while the app is open and when it returns to the foreground.
  void refreshData() {
    _ref.invalidate(visitorListProvider);
    _ref.invalidate(preApprovedListProvider);
    _ref.invalidate(visitorRequestProvider);
    _ref.invalidate(guardVisitorListProvider);
    _ref.invalidate(notificationListProvider);
    _ref.invalidate(unreadNotificationCountProvider);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Covers a request answered from a notification button (which ran in a
    // background isolate) and pushes that arrived while the app was away.
    if (state == AppLifecycleState.resumed && _loggedIn) refreshData();
  }

  Future<void> _open(GoRouter router, Map<String, dynamic> data) async {
    // Wait for the session to be restored so the redirect doesn't bounce us to /login.
    final auth = await _ref.read(authControllerProvider.future);
    if (auth == null) return;

    final visitorId = int.tryParse('${data['visitor_id'] ?? ''}');

    if (data['type'] == 'visitor_request' && visitorId != null) {
      router.push('/visitors/request/$visitorId');
    } else if ('${data['type'] ?? ''}'.startsWith('visitor_request_')) {
      router.push('/gatekeeper/visitors');
    } else {
      router.push('/notifications');
    }
  }
}

final pushServiceProvider = Provider<PushService>((ref) => PushService(ref));

/// Watched once from FlatCareApp: starts the listeners and registers the
/// device whenever a session starts (login or restored on launch).
final pushBootstrapProvider = Provider<void>((ref) {
  final service = ref.watch(pushServiceProvider);
  final router = ref.watch(routerProvider);

  service.start(router);

  ref.listen(authControllerProvider, (previous, next) {
    if (next.valueOrNull != null && previous?.valueOrNull == null) service.registerDevice();
  }, fireImmediately: true);
});
