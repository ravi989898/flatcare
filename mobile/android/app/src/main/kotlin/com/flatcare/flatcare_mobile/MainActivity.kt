package com.flatcare.flatcare_mobile

import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.view.WindowManager
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

/**
 * A visitor-at-the-gate notification is posted as a full-screen intent, so
 * on a locked phone it launches this activity like an incoming call. Only
 * that launch may show over the lock screen and wake the display: the flag
 * is set here when the intent comes from a notification, and the Flutter
 * gate-approval screen clears it through [CHANNEL] once it closes, so the
 * rest of the app is never usable without unlocking the phone. (Setting
 * showWhenLocked in the manifest instead would expose the whole app.)
 */
class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        showOverLockScreenIfFromNotification(intent)
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        showOverLockScreenIfFromNotification(intent)
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "setShowWhenLocked" -> {
                    setShowOverLockScreen(call.arguments as? Boolean ?: false)
                    result.success(null)
                }
                else -> result.notImplemented()
            }
        }
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, AlertSetup.CHANNEL).setMethodCallHandler(AlertSetup(this))
    }

    private fun showOverLockScreenIfFromNotification(intent: Intent?) {
        // The action flutter_local_notifications puts on a notification's content intent.
        if (intent?.action == "SELECT_NOTIFICATION") setShowOverLockScreen(true)
    }

    private fun setShowOverLockScreen(show: Boolean) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O_MR1) {
            setShowWhenLocked(show)
            setTurnScreenOn(show)
        } else {
            @Suppress("DEPRECATION")
            val flags = WindowManager.LayoutParams.FLAG_SHOW_WHEN_LOCKED or WindowManager.LayoutParams.FLAG_TURN_SCREEN_ON
            if (show) window.addFlags(flags) else window.clearFlags(flags)
        }
    }

    companion object {
        private const val CHANNEL = "flatcare/lock_screen"
    }
}
