# FlatCare app – Play Store & iPhone (App Store) release guide

This is a step-by-step checklist for putting the FlatCare app on **Google Play**
and on **iPhone (App Store / TestFlight)**. Copy-paste text for the store forms
is included.

---

## 0. Must do first (before any store submission)

| # | What | Why |
|---|------|-----|
| 1 | **Turn on real SMS OTP.** Today every phone number logs in with the same fixed code (`0000`, set by `OTP_DEFAULT_CODE`). Wire an SMS provider (MSG91, Twilio, 2Factor…) into `app/Services/Api/OtpService.php`. | Anyone who knows a resident's number can log in as them. Google/Apple reviewers will also flag it. |
| 2 | **Deploy the server code** (this commit) to `flatcare.in`. | The store forms need the live pages **https://flatcare.in/privacy-policy** and **https://flatcare.in/delete-account**. |
| 3 | **Create a demo account for reviewers**: one demo society with a demo resident (owner) who has 1–2 bills, a notice and a visitor. | Google and Apple both log in to test the app. |
| 4 | **Keep a fixed OTP for the demo number only** (after step 1), e.g. demo number `9999999999` → OTP `1234`. | Reviewers can't receive your SMS. |

---

## 1. Why the APK shows "data 22.5 MB" on iPhone

The `flatcare-app.apk` file is an **Android-only** file. iPhones can't open
APKs, and an iPhone app can't be "downloaded" from a website. An iPhone app has
to be:

1. built on a **Mac** (or a cloud Mac service like Codemagic),
2. uploaded to **Apple App Store Connect**, then
3. installed through **TestFlight** (for testing) or the **App Store** (public).

The project is now ready for this – see section 3.

---

## 2. Google Play Store

### 2.1 Developer account
- Create one at https://play.google.com/console – one-time fee **US$25**.
- Choose **Organisation** if you have a registered business (needs a D-U-N-S
  number). With a **Personal** account created after Nov 2023, Google requires a
  **closed test with at least 12 testers for 14 days** before you can publish
  to everyone. Plan for this.

### 2.2 Build the file to upload (.aab)
Google Play only accepts an **App Bundle (.aab)**, not an APK. The GitHub
workflow now builds it:

1. Bump `version:` in `mobile/pubspec.yaml` (e.g. `1.0.6+7`).
2. Push a tag: `git tag app-v1.0.6 && git push upstream app-v1.0.6`
   (or run **Actions → Build & publish Android APK → Run workflow**).
3. When the run finishes, open the repo's **Releases** page → *FlatCare app 1.0.6*
   → download **flatcare-app.aab**.

The bundle is signed with the same upload keystore as the APK (GitHub secrets
`ANDROID_KEYSTORE_BASE64` etc.). **Keep a backup of that keystore and its
passwords** – you can't update the app without it.

### 2.3 Create the app in Play Console
- **Create app** → App name: `FlatCare` → Default language: English (India) →
  App → Free → accept declarations.
- **Package name** (fixed forever): `com.flatcare.flatcare_mobile`
- On the first upload, accept **Play App Signing** (Google keeps the final
  signing key; your keystore becomes the "upload key").

### 2.4 Store listing (Grow → Store presence → Main store listing)

**App name** (max 30): `FlatCare – Society Management`

**Short description** (max 80):
```
Pay maintenance, approve visitors & get society updates in one app.
```

**Full description** (max 4000):
```
FlatCare is the official app for housing societies, apartments and RWAs that use FlatCare.

For residents
• See your maintenance and water bills and pay online securely (UPI, cards, net banking via Razorpay)
• Download payment receipts
• Approve or reject visitors at the gate in real time
• Create gate passes with QR codes for guests
• Raise complaints with photos and track them
• Read notices, join events and vote in society polls
• Society directory – call or WhatsApp your neighbours
• Add family members and vehicles

For security guards
• Check visitors in with photo and vehicle number
• Ask residents for approval and scan gate pass QR codes

For society admins
• Enter water meter readings and generate bills
• See who has paid and who is pending

Your society must be registered on FlatCare to use the app. Your society office adds your mobile number; you then sign in with an OTP.
```

**Graphics needed**
| Item | Size | Where to get it |
|------|------|-----------------|
| App icon | 512 × 512 PNG | `mobile/assets/icon/app_icon.png` (1254×1254) – resize to 512×512 |
| Feature graphic | 1024 × 500 PNG/JPG | Make one (logo + "Smart society management") |
| Phone screenshots | 2–8, e.g. 1080 × 1920 | Take on a phone using the demo account: Home, My Bills, Bill detail/Pay, Visitors, Directory, Notices |

**Category**: House & Home (or Lifestyle) · **Email**: support@flatcare.in ·
**Website**: https://flatcare.in · **Phone**: +91 96646 53896

### 2.5 App content (Policy → App content) – answers

| Section | Answer |
|---------|--------|
| **Privacy policy** | `https://flatcare.in/privacy-policy` |
| **App access** | "All or some functionality is restricted" → add instructions: *Phone: `<demo number>`, OTP: `<demo OTP>`. Log in → you are a resident of "Demo Society", flat A-101.* |
| **Ads** | No, my app does not contain ads |
| **Content rating** | Category: *All other app types*. Violence, sexuality, bad language, drugs, gambling → **No**. "Users can interact or exchange information" → **Yes** (directory, complaints). "Shares user's location" → **No**. "Digital purchases" → **No**. Result is usually *Everyone / 3+*. |
| **Target audience** | 18 and over |
| **News app** | No |
| **Government app** | No |
| **Financial features** | "My app doesn't provide any financial features" (bill payment is done by Razorpay; FlatCare is not a bank/lender/wallet) |
| **Health** | No health features |
| **Data safety** | See 2.6 |
| **Full-screen intent** (Android 14+) | Declare: *"Visitor waiting at the gate" alert – the resident must approve or reject a visitor in real time, like an incoming call.* If Google refuses, the app still works; the alert shows as a normal high-priority notification. |
| **Account deletion** | Delete account URL: `https://flatcare.in/delete-account` |

### 2.6 Data safety form – answers

- Does your app collect or share user data? **Yes**
- Is all data encrypted in transit? **Yes**
- Can users request deletion? **Yes** → `https://flatcare.in/delete-account`

| Data type | Collected | Shared | Purpose | Optional? |
|-----------|-----------|--------|---------|-----------|
| Personal info → Name | Yes | No | App functionality, Account management | Required |
| Personal info → Phone number | Yes | No | App functionality, Account management | Required |
| Personal info → Email address | Yes | No | Account management | Optional |
| Personal info → Address (flat/block) | Yes | No | App functionality | Required |
| Financial info → Purchase history (bills/payments) | Yes | No | App functionality | Required |
| Photos | Yes | No | App functionality (profile, complaints, visitor photos) | Optional |
| App activity → Other user-generated content (complaints, poll votes) | Yes | No | App functionality | Optional |
| Device or other IDs (push notification token) | Yes | No | App functionality (notifications) | Required |

Notes: card/UPI details go to Razorpay directly – FlatCare does not collect
them. "Shared" means given to a third party for its own use; service providers
acting for you (Firebase, Razorpay, hosting) don't count as sharing.

### 2.7 Release
1. **Testing → Internal testing** → create release → upload `app-release.aab`
   → add testers' emails → share the link. Test on a few phones.
2. (Personal account) **Closed testing** with 12+ testers for 14 days.
3. **Production** → Countries: India → Create release → upload the same/newer
   `.aab` → Release notes → **Send for review** (usually 1–7 days).

Every new upload needs a **higher build number** – the workflow uses the
GitHub run number, so this happens automatically.

---

## 3. iPhone (App Store / TestFlight)

### 3.1 What you need
- **Apple Developer Program** membership – **US$99 / year**
  (https://developer.apple.com/programs/). Enrol as an Organisation if you can.
- **A Mac with Xcode** (latest), **or** a cloud build service such as
  **Codemagic** (https://codemagic.io – free tier, builds iOS from your GitHub
  repo without a Mac).
- An iPhone for testing (via TestFlight).

### 3.2 Already done in the project (this commit)
- Bundle ID: `com.flatcare.flatcareMobile`
- iPhone-only app (no iPad screenshots needed)
- Minimum iOS version raised to **15.0** (required by Firebase / QR scanner) – `ios/Podfile`, Xcode project
- Push-notification entitlement (`ios/Runner/Runner.entitlements`) and background push mode
- Camera and photo-library permission messages (`Info.plist`)
- Notification handling while the app is open (`AppDelegate.swift`)
- Export-compliance flag (`ITSAppUsesNonExemptEncryption = NO`) so you aren't asked on every upload

### 3.3 One-time setup in Apple Developer & Firebase
1. **Apple Developer → Certificates, IDs & Profiles → Identifiers → +**
   App ID `com.flatcare.flatcareMobile`, tick **Push Notifications**.
2. **Keys → +** → tick *Apple Push Notifications service (APNs)* → download the
   `.p8` file, note the **Key ID** and your **Team ID**.
3. **Firebase console** (project `flatcare-d6743`) → Project settings →
   **Add app → iOS** → bundle ID `com.flatcare.flatcareMobile` → download
   **GoogleService-Info.plist**.
4. Firebase → Project settings → **Cloud Messaging → Apple app configuration**
   → upload the `.p8` APNs key with Key ID and Team ID.
5. Put `GoogleService-Info.plist` in `mobile/ios/Runner/` and add it to the
   **Runner** target in Xcode (drag it into the Runner folder, tick "Copy items"
   and the Runner target). Without it the app works but gets **no push
   notifications** on iPhone.

### 3.4 Build & upload (on a Mac)
```bash
cd mobile
flutter pub get
cd ios && pod install && cd ..
open ios/Runner.xcworkspace      # Xcode: Runner → Signing & Capabilities → choose your Team
flutter build ipa --release \
  --dart-define=API_BASE_URL=https://flatcare.dineflowpro.com/api/v1
```
Then upload `build/ios/ipa/*.ipa` with the **Transporter** app (Mac App Store),
or Xcode → Product → Archive → Distribute App.

**With Codemagic instead of a Mac:** connect the GitHub repo → Flutter app →
iOS → *Automatic code signing* with an App Store Connect API key → build
arguments `--dart-define=API_BASE_URL=https://flatcare.dineflowpro.com/api/v1`
→ *Publish to App Store Connect*. Upload `GoogleService-Info.plist` as a secure
file (or commit it).

### 3.5 App Store Connect
1. https://appstoreconnect.apple.com → **My Apps → +** → New App →
   Platform iOS, Name `FlatCare – Society App`, Primary language English (India),
   Bundle ID `com.flatcare.flatcareMobile`, SKU `flatcare-ios`.
2. **TestFlight** → once the build finishes processing, add yourself as an
   internal tester → install **TestFlight** on the iPhone → install FlatCare.
   *This is how you get the app on your iPhone.*
3. **App Store listing**: use the same descriptions as Play.
   - Screenshots: 6.9" iPhone (1320 × 2868) – required. The app is set to
     iPhone-only, so no iPad screenshots are needed (iPads can still run it).
   - Privacy Policy URL: `https://flatcare.in/privacy-policy`
   - Support URL: `https://flatcare.in/contact`
   - Category: Lifestyle · Age rating: 4+
4. **App Privacy** questionnaire: same data as the Play table (2.6);
   "Data linked to the user: Yes", "Used for tracking: No".
5. **App Review Information** → Sign-in required → demo phone + OTP, and this note:

```
FlatCare is used by residents of housing societies registered on FlatCare.
Accounts are created by the society office, not inside the app; users sign in
with their mobile number and an OTP. Demo login: <demo number> / OTP <demo OTP>.
Maintenance bills are paid via Razorpay (UPI/card) - these are payments for
real-world society services (Guideline 3.1.3(e)/3.1.5), not digital content.
Account deletion requests: https://flatcare.in/delete-account
```

6. **Submit for Review** (usually 1–3 days).

---

## 4. Every future update

| Android | iPhone |
|---------|--------|
| Bump `version:` in `pubspec.yaml` → push tag `app-vX.Y.Z` → download `.aab` artifact → Play Console → Production → Create release → upload | Bump `version:` → `flutter build ipa` (or Codemagic) → upload → TestFlight → App Store → new version → Submit |

Server-only changes (API, website) never need a new store release.
