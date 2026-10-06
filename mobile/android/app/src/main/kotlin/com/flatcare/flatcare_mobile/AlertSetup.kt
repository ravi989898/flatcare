package com.flatcare.flatcare_mobile

import android.annotation.SuppressLint
import android.app.Activity
import android.app.NotificationManager
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.provider.Settings
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel

/**
 * The phone settings a visitor request needs to get through with the app
 * closed, for the Alert Setup screen (alert_setup_screen.dart):
 *
 *  - notifications allowed, and the visitor channel not switched off;
 *  - battery optimisation off, so Android doesn't hold back the push;
 *  - on Xiaomi, Oppo, Realme, Vivo, OnePlus, Huawei, Honor, Asus... the
 *    maker's own "Auto-start" / "Background launch" switch. Without it these
 *    phones force-stop the app when it's swiped away, and Android delivers
 *    nothing to a force-stopped app. There's no API to read that switch, so
 *    it counts as done once the resident has opened the screen;
 *  - Android 14+: full-screen alerts (the incoming-call style popup).
 */
class AlertSetup(private val activity: Activity) : MethodChannel.MethodCallHandler {
    private val prefs = activity.getSharedPreferences("flatcare_alert_setup", Context.MODE_PRIVATE)

    override fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        when (call.method) {
            "status" -> result.success(status())
            "openNotifications" -> result.success(openNotifications())
            "openBattery" -> result.success(openBattery())
            "openAutoStart" -> result.success(openAutoStart())
            "openFullScreen" -> result.success(openFullScreen())
            "lastPrompted" -> result.success(prefs.getLong(KEY_LAST_PROMPTED, 0))
            "markPrompted" -> {
                prefs.edit().putLong(KEY_LAST_PROMPTED, System.currentTimeMillis()).apply()
                result.success(null)
            }
            else -> result.notImplemented()
        }
    }

    private fun status(): Map<String, Any> = mapOf(
        "notifications" to notificationsEnabled(),
        "battery" to ignoringBatteryOptimizations(),
        "autoStartNeeded" to (autoStartIntents().isNotEmpty()),
        "autoStartDone" to prefs.getBoolean(KEY_AUTO_START_OPENED, false),
        "fullScreenNeeded" to (Build.VERSION.SDK_INT >= 34),
        "fullScreen" to fullScreenAllowed(),
        "brand" to Build.MANUFACTURER.replaceFirstChar { it.uppercase() },
    )

    private fun notificationsEnabled(): Boolean {
        val manager = activity.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        if (!manager.areNotificationsEnabled()) return false
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = manager.getNotificationChannel(VISITOR_CHANNEL)
            if (channel != null && channel.importance == NotificationManager.IMPORTANCE_NONE) return false
        }
        return true
    }

    private fun ignoringBatteryOptimizations(): Boolean {
        val power = activity.getSystemService(Context.POWER_SERVICE) as PowerManager
        return power.isIgnoringBatteryOptimizations(activity.packageName)
    }

    private fun fullScreenAllowed(): Boolean {
        if (Build.VERSION.SDK_INT < 34) return true
        val manager = activity.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        return manager.canUseFullScreenIntent()
    }

    private fun openNotifications(): Boolean {
        val intent = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS).putExtra(Settings.EXTRA_APP_PACKAGE, activity.packageName)
        } else {
            appDetails()
        }
        return start(intent) || start(appDetails())
    }

    @SuppressLint("BatteryLife")
    private fun openBattery(): Boolean {
        // The one-tap "Allow" dialog; the full list as a fallback.
        val request = Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:${activity.packageName}"))
        return start(request) || start(Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)) || start(appDetails())
    }

    private fun openAutoStart(): Boolean {
        val opened = autoStartIntents().any { start(it) } || start(appDetails())
        if (opened) prefs.edit().putBoolean(KEY_AUTO_START_OPENED, true).apply()
        return opened
    }

    private fun openFullScreen(): Boolean {
        if (Build.VERSION.SDK_INT < 34) return false
        val intent = Intent(Settings.ACTION_MANAGE_APP_USE_FULL_SCREEN_INTENT, Uri.parse("package:${activity.packageName}"))
        return start(intent) || start(appDetails())
    }

    /** The maker's auto-start screens, most recent OS version first; empty on phones without one. */
    private fun autoStartIntents(): List<Intent> {
        val components = when (Build.MANUFACTURER.lowercase()) {
            "xiaomi", "redmi", "poco" -> listOf(
                "com.miui.securitycenter" to "com.miui.permcenter.autostart.AutoStartManagementActivity",
            )
            "oppo", "realme", "oneplus" -> listOf(
                "com.coloros.safecenter" to "com.coloros.safecenter.startupapp.StartupAppListActivity",
                "com.coloros.safecenter" to "com.coloros.safecenter.permission.startup.StartupAppListActivity",
                "com.oppo.safe" to "com.oppo.safe.permission.startup.StartupAppListActivity",
                "com.oneplus.security" to "com.oneplus.security.chainlaunch.view.ChainLaunchAppListActivity",
                "com.coloros.oppoguardelf" to "com.coloros.powermanager.fuelgaue.PowerUsageModelActivity",
            )
            "vivo", "iqoo" -> listOf(
                "com.vivo.permissionmanager" to "com.vivo.permissionmanager.activity.BgStartUpManagerActivity",
                "com.iqoo.secure" to "com.iqoo.secure.ui.phoneoptimize.BgStartUpManager",
                "com.iqoo.secure" to "com.iqoo.secure.ui.phoneoptimize.AddWhiteListActivity",
            )
            "huawei" -> listOf(
                "com.huawei.systemmanager" to "com.huawei.systemmanager.startupmgr.ui.StartupNormalAppListActivity",
                "com.huawei.systemmanager" to "com.huawei.systemmanager.optimize.process.ProtectActivity",
            )
            "honor" -> listOf(
                "com.hihonor.systemmanager" to "com.hihonor.systemmanager.startupmgr.ui.StartupNormalAppListActivity",
                "com.huawei.systemmanager" to "com.huawei.systemmanager.startupmgr.ui.StartupNormalAppListActivity",
            )
            "asus" -> listOf(
                "com.asus.mobilemanager" to "com.asus.mobilemanager.autostart.AutoStartActivity",
                "com.asus.mobilemanager" to "com.asus.mobilemanager.powersaver.PowerSaverSettings",
            )
            "letv" -> listOf(
                "com.letv.android.letvsafe" to "com.letv.android.letvsafe.AutobootManageActivity",
            )
            "nokia", "hmd global" -> listOf(
                "com.evenwell.powersaving.g3" to "com.evenwell.powersaving.g3.exception.PowerSaverExceptionActivity",
            )
            else -> emptyList()
        }
        return components.map { (pkg, cls) -> Intent().setComponent(ComponentName(pkg, cls)) }
    }

    private fun appDetails() = Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS, Uri.parse("package:${activity.packageName}"))

    /** Starts [intent] if this phone has it; makers rename and lock these screens between OS versions. */
    private fun start(intent: Intent): Boolean = try {
        activity.startActivity(intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        true
    } catch (e: Exception) {
        false
    }

    companion object {
        const val CHANNEL = "flatcare/alert_setup"
        private const val VISITOR_CHANNEL = "visitor_requests_v2"
        private const val KEY_AUTO_START_OPENED = "auto_start_opened"
        private const val KEY_LAST_PROMPTED = "last_prompted"
    }
}
