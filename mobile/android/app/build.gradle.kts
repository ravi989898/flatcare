import java.util.Properties
import java.io.FileInputStream

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

// Release signing config is read from android/key.properties (git-ignored,
// created locally) or, on CI, from a key.properties the workflow writes from
// GitHub secrets. If it is absent, the release build falls back to the debug
// key so `flutter run --release` still works on a fresh checkout.
val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("key.properties")
val hasReleaseKeystore = keystorePropertiesFile.exists()
if (hasReleaseKeystore) {
    keystoreProperties.load(FileInputStream(keystorePropertiesFile))
}

android {
    namespace = "com.flatcare.flatcare_mobile"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        applicationId = "com.flatcare.flatcare_mobile"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        if (hasReleaseKeystore) {
            create("release") {
                keyAlias = keystoreProperties["keyAlias"] as String
                keyPassword = keystoreProperties["keyPassword"] as String
                storeFile = file(keystoreProperties["storeFile"] as String)
                storePassword = keystoreProperties["storePassword"] as String
            }
        }
    }

    buildTypes {
        release {
            signingConfig = if (hasReleaseKeystore) {
                signingConfigs.getByName("release")
            } else {
                // No release keystore on this machine — sign with the debug key
                // so a plain `flutter run --release` still works. CI always has
                // the real keystore, so published APKs are properly signed.
                signingConfigs.getByName("debug")
            }
            // Code shrinking (R8/minify) is left off deliberately: Dart
            // tree-shaking already brings the release APK to ~19 MB, and
            // razorpay_flutter / flutter_secure_storage need extra keep rules
            // to survive minification. Not worth the runtime risk on a
            // payments app for a few more MB.
        }
    }
}

flutter {
    source = "../.."
}
