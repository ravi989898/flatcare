# Push notifications (Firebase Cloud Messaging) — setup

The visitor approval flow pushes to phones through FCM. The **server** sends
every push; the app only receives them. Until the steps below are done nothing
breaks: requests, approvals and the in-app Notifications list all work, and
each notification is simply recorded with `push_status = skipped`.

## 1. Firebase project

1. <https://console.firebase.google.com> → create (or open) a project.
2. **Add an Android app** with package name `com.flatcare.flatcare_mobile`.
3. Download **`google-services.json`** and put it at
   `mobile/android/app/google-services.json`.
   The Gradle build applies the Google Services plugin only when this file
   exists, so a checkout without it still builds (with push off).
4. iOS (optional): add an iOS app, add `GoogleService-Info.plist` to
   `mobile/ios/Runner`, upload an APNs key under *Project settings → Cloud
   Messaging*, and enable Push Notifications + Background Modes → Remote
   notifications in Xcode. (Accept/Reject buttons on the notification itself
   are Android-only; on iOS the notification opens the request screen.)

## 2. Server credentials (HTTP v1 API)

1. Firebase console → *Project settings → Service accounts → Generate new
   private key*. This downloads a JSON file. **It is a secret** — never commit
   it (`service-account*.json` and `firebase-adminsdk*.json` are git-ignored).
2. Put it on the server outside the web root if you can, e.g.
   `storage/app/firebase/service-account.json`.
3. In `.env`:

   ```env
   # Path to the service-account JSON (absolute, or relative to the project root)
   FCM_CREDENTIALS=storage/app/firebase/service-account.json
   # Optional - defaults to project_id inside that file
   FCM_PROJECT_ID=your-firebase-project-id
   # Optional - seconds to wait for Google before giving up on a push (default 5)
   FCM_TIMEOUT=5
   ```
4. The service account needs the *Firebase Cloud Messaging API (V1)* enabled
   (Google Cloud console → APIs & Services) and the role *Firebase Cloud
   Messaging Admin* (the default *Firebase Admin SDK* account has it).

No Composer package is needed; the server signs its own OAuth token.

## 3. Database + scheduler

```bash
php artisan tenants:migrate        # applies the new tenant migrations to every society DB
```

Failed pushes are retried by `notifications:retry-push`, scheduled every minute
in `routes/console.php`. That needs the standard Laravel cron entry:

```cron
* * * * * cd /path/to/flatcare && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Build the app

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://<host-lan-ip>:8000/api/v1
```

Use a real device or an emulator image *with Google Play services*. On
Android 13+ the app asks for notification permission after login.

## How to check it works

1. Sign in on a phone as a resident (this registers its FCM token via
   `POST /api/v1/devices`).
2. Sign in as a gate keeper elsewhere and raise a walk-in request for that
   resident's flat.
3. The resident phone shows **Visitor Approval Request** with **Accept** /
   **Reject** buttons — also with the app closed.
4. After answering, the gate keeper phone shows **Visitor Approved / Rejected**
   and the gate register updates by itself.

If it doesn't arrive, look at the `resident_notifications` row for that
request: `push_status` (`sent`, `failed`, `skipped`) and `push_error` say why
(no registered device, FCM not configured, or Google's error message).
