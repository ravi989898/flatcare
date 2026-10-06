import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/announcements/providers/announcement_providers.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/bills/providers/bill_providers.dart';
import '../../features/events/providers/event_providers.dart';
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
///    system can't add buttons to a message it draws itself, so the native
///    VisitorRequestReceiver (android/app/.../VisitorRequestReceiver.kt) — even with the app
///    closed — builds the notification with Deny / Approve buttons, the
///    visitor's photo and a full-screen intent (so a locked phone shows the
///    gate-approval screen like an incoming call). Tapping a button calls
///    the approve/reject API from a background isolate
///    ([onBackgroundNotificationResponse]) without opening the app; tapping
///    the notification opens the gate-approval screen.
///  * Other messages (and iOS): a normal notification the system displays;
///    tapping it opens the relevant screen.
///  * App open: [PushService] listens to onMessage, shows the same local
///    notification (and, for a visitor request, opens the gate-approval
///    screen straight away), and invalidates the visitor/notification
///    providers so open screens reload immediately.
///
/// Everything here is a no-op until Firebase is configured for the build
/// (android/app/google-services.json — see mobile/FCM_SETUP.md), so the app
/// still runs and works over plain API calls without it.
bool firebaseReady = false;

const _channelId = 'visitor_requests_v2';
const _sound = RawResourceAndroidNotificationSound('plectron');
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
  // A visitor request was already drawn natively by VisitorRequestReceiver
  // (android/app/.../VisitorRequestReceiver.kt) - this isolate can start too
  // late, or not at all, with the app closed.
  if ('${message.data['actions'] ?? ''}'.contains(_actionApprove)) return;

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
        sound: _sound,
      ));
}

Future<void> _showNotification(Map<String, dynamic> data, {String? title, String? body}) async {
  final visitorId = int.tryParse('${data['visitor_id'] ?? ''}');
  final actionable = '${data['actions'] ?? ''}'.contains(_actionApprove);
  final text = body ?? data['body']?.toString();
  final flatLabel = data['flat_label']?.toString();
  final photo = actionable ? await _downloadPhoto(data['photo_url']?.toString()) : null;

  await _local.show(
    // One notification per visitor: a decision replaces the original request.
    id: visitorId ?? DateTime.now().millisecondsSinceEpoch ~/ 1000,
    title: title ?? data['title']?.toString() ?? 'FlatCare',
    body: text,
    payload: jsonEncode(data),
    notificationDetails: NotificationDetails(
      android: AndroidNotificationDetails(
        _channelId,
        'Visitor requests',
        importance: Importance.max,
        priority: Priority.high,
        sound: _sound,
        // A visitor waiting at the gate: rings through like a call and, on a
        // locked phone, opens the gate-approval screen full screen (see
        // MainActivity). Android may still show it as a heads-up banner.
        category: actionable ? AndroidNotificationCategory.call : AndroidNotificationCategory.message,
        fullScreenIntent: actionable,
        subText: flatLabel,
        largeIcon: photo == null ? null : ByteArrayAndroidBitmap(photo),
        styleInformation: text == null ? null : BigTextStyleInformation(text),
        actions: actionable
            ? const [
                AndroidNotificationAction(_actionReject, 'Deny', cancelNotification: true),
                AndroidNotificationAction(_actionApprove, 'Approve', cancelNotification: true),
              ]
            : null,
      ),
      iOS: const DarwinNotificationDetails(sound: 'plectron.wav'),
    ),
  );
}

/// The visitor's gate photo for the notification's large icon. Kept short
/// so a slow network never delays the alert itself - it is simply shown
/// without the photo.
Future<Uint8List?> _downloadPhoto(String? url) async {
  if (url == null || url.isEmpty) return null;

  try {
    final response = await Dio().get<List<int>>(
      url,
      options: Options(
        responseType: ResponseType.bytes,
        sendTimeout: const Duration(seconds: 4),
        receiveTimeout: const Duration(seconds: 4),
      ),
    );
    final bytes = response.data;
    return bytes == null || bytes.isEmpty ? null : Uint8List.fromList(bytes);
  } catch (_) {
    return null;
  }
}

/// Removes a visitor's request notification once it has been answered in the app.
Future<void> cancelVisitorNotification(int visitorId) async {
  if (!firebaseReady) return;

  try {
    await _local.cancel(id: visitorId);
  } catch (_) {
    // Nothing to remove.
  }
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
      // Someone is at the gate while the app is open: pop the approval screen up at once.
      if (message.data['type'] == 'visitor_request') unawaited(_open(router, message.data));
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
    _ref.invalidate(visitorRequestProvider);
    // Gate register, pending requests, passes, log and tile counts.
    invalidateGateLists(_ref.invalidate);
    _ref.invalidate(closedHouseListProvider);
    _ref.invalidate(notificationListProvider);
    _ref.invalidate(unreadNotificationCountProvider);
    // New bill / payment received, new announcement or event.
    _ref.invalidate(billListProvider);
    _ref.invalidate(announcementListProvider);
    _ref.invalidate(eventListProvider);
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

    int? id(String key) => int.tryParse('${data[key] ?? ''}');
    final visitorId = id('visitor_id');

    if (data['type'] == 'visitor_request' && visitorId != null) {
      final target = '/visitors/approve/$visitorId';
      // Already showing it (e.g. the push and a tap on its notification both arrived).
      if (router.routerDelegate.currentConfiguration.uri.path != target) router.push(target);
    } else if ('${data['type'] ?? ''}'.startsWith('visitor_request_')) {
      router.push('/gatekeeper/visitors');
    } else if (id('bill_id') case final billId?) {
      // A new bill, or "payment received" for one.
      router.push('/bills/$billId');
    } else if (id('announcement_id') case final announcementId?) {
      router.push('/announcements/$announcementId');
    } else if (id('event_id') case final eventId?) {
      router.push('/events/$eventId');
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
