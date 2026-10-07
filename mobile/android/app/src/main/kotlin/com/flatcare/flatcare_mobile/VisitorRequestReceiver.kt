package com.flatcare.flatcare_mobile

import android.app.ActivityManager
import android.app.KeyguardManager
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.media.AudioAttributes
import android.net.Uri
import android.os.Build
import android.util.Log
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import kotlin.concurrent.thread

/**
 * Shows a "visitor at the gate" push the moment it arrives while the app is
 * closed or in the background.
 *
 * The backend sends these to Android as data-only FCM messages (so the
 * notification can carry Deny / Approve buttons). firebase_messaging hands a
 * data message to Dart by scheduling a JobScheduler job that boots a whole
 * Flutter engine first - with the app closed, battery optimisation / OEM
 * task killers defer or kill that job, so the request never showed up.
 * This receiver gets the same FCM broadcast directly and builds the
 * notification natively, with no engine and no job, inside the window FCM's
 * high priority grants. (push_service.dart's background handler skips these
 * messages so they aren't shown twice; with the app open, its onMessage
 * listener shows them as before.)
 *
 * The notification matches what push_service.dart's _showNotification
 * builds: the same channel, id (the visitor id, so the decision replaces
 * it) and payload, and its intents use flutter_local_notifications' own
 * format, so a tap opens the gate-approval screen and Deny / Approve run
 * onBackgroundNotificationResponse exactly as for a Dart-built one.
 */
class VisitorRequestReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val extras = intent.extras ?: return
        // FCM puts each `data` field into the broadcast as a string extra.
        val data = extras.keySet()
            .filterNot { it.startsWith("google.") || it.startsWith("gcm.") || it == "from" || it == "collapse_key" }
            .mapNotNull { key -> extras.getString(key)?.let { key to it } }
            .toMap()

        if (data["actions"]?.contains(ACTION_APPROVE) != true) return
        // App open and unlocked: Dart's onMessage shows it (and opens the approval screen).
        if (isAppInForeground(context)) return

        val pending = goAsync()
        thread {
            try {
                show(context.applicationContext, data)
            } catch (e: Exception) {
                Log.e(TAG, "Could not show the visitor request", e)
            } finally {
                pending.finish()
            }
        }
    }

    private fun show(context: Context, data: Map<String, String>) {
        val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        val sound = Uri.parse("android.resource://${context.packageName}/${R.raw.visitor_ring}")
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            // The old channel rang with plectron; a channel's sound can't be changed.
            manager.deleteNotificationChannel(OLD_CHANNEL_ID)
            // Same settings as push_service.dart creates it with; a no-op if it exists.
            manager.createNotificationChannel(
                NotificationChannel(CHANNEL_ID, "Visitor requests", NotificationManager.IMPORTANCE_MAX).apply {
                    description = "Visitors waiting at the gate for your approval"
                    setSound(sound, AudioAttributes.Builder().setUsage(AudioAttributes.USAGE_NOTIFICATION).build())
                }
            )
        }

        val id = data["visitor_id"]?.toIntOrNull() ?: (System.currentTimeMillis() / 1000).toInt()
        val title = data["title"] ?: "FlatCare"
        val body = data["body"]
        val payload = JSONObject(data).toString()
        val flags = PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE

        val launch = context.packageManager.getLaunchIntentForPackage(context.packageName)!!.apply {
            action = SELECT_NOTIFICATION
            putExtra(EXTRA_NOTIFICATION_ID, id)
            putExtra(EXTRA_PAYLOAD, payload)
        }
        val open = PendingIntent.getActivity(context, id, launch, flags)

        fun button(requestCode: Int, actionId: String, label: String): Notification.Action {
            val intent = Intent().apply {
                setClassName(context, ACTION_RECEIVER)
                action = ACTION_TAPPED
                putExtra(EXTRA_NOTIFICATION_ID, id)
                putExtra(EXTRA_ACTION_ID, actionId)
                putExtra(EXTRA_CANCEL_NOTIFICATION, true)
                putExtra(EXTRA_PAYLOAD, payload)
            }
            val pendingIntent = PendingIntent.getBroadcast(context, requestCode, intent, flags)
            @Suppress("DEPRECATION")
            return Notification.Action.Builder(0, label, pendingIntent).build()
        }

        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            // Rings until answered/opened/dismissed, like a call; never longer than RING_FOR_MS.
            Notification.Builder(context, CHANNEL_ID).setTimeoutAfter(RING_FOR_MS)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(context).setPriority(Notification.PRIORITY_MAX).setSound(sound)
        }

        builder
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .setCategory(Notification.CATEGORY_CALL)
            .setContentIntent(open)
            // A locked phone opens the gate-approval screen like an incoming call (see MainActivity).
            .setFullScreenIntent(open, true)
            // Request codes spaced by 16 like flutter_local_notifications', so they never clash.
            .addAction(button(id * 16, ACTION_REJECT, "Deny"))
            .addAction(button(id * 16 + 1, ACTION_APPROVE, "Approve"))
        data["flat_label"]?.let { builder.setSubText(it) }
        body?.let { builder.setStyle(Notification.BigTextStyle().bigText(it)) }

        // FLAG_INSISTENT repeats the channel's tone until the notification
        // is cancelled - the gate-approval screen cancels it once it closes.
        fun ringing(): Notification = builder.build().apply { this.flags = this.flags or Notification.FLAG_INSISTENT }

        // Alert straight away, then add the visitor's photo once it has
        // loaded. No setOnlyAlertOnce on that update: it would stop the
        // ringing, whereas an update to an insistent notification keeps it going.
        manager.notify(id, ringing())
        downloadPhoto(data["photo_url"])?.let {
            builder.setLargeIcon(it)
            manager.notify(id, ringing())
        }
    }

    /** The visitor's gate photo; left out if it can't be fetched quickly. */
    private fun downloadPhoto(url: String?): Bitmap? {
        if (url.isNullOrEmpty()) return null

        return try {
            val connection = URL(url).openConnection() as HttpURLConnection
            connection.connectTimeout = 4000
            connection.readTimeout = 4000
            try {
                connection.inputStream.use { BitmapFactory.decodeStream(it) }
            } finally {
                connection.disconnect()
            }
        } catch (e: Exception) {
            null
        }
    }

    /** The same test firebase_messaging uses to choose onMessage over the background handler. */
    private fun isAppInForeground(context: Context): Boolean {
        val keyguard = context.getSystemService(Context.KEYGUARD_SERVICE) as? KeyguardManager
        if (keyguard?.isKeyguardLocked == true) return false

        val activityManager = context.getSystemService(Context.ACTIVITY_SERVICE) as? ActivityManager ?: return false
        return activityManager.runningAppProcesses.orEmpty().any {
            it.importance == ActivityManager.RunningAppProcessInfo.IMPORTANCE_FOREGROUND && it.processName == context.packageName
        }
    }

    companion object {
        private const val TAG = "VisitorRequestReceiver"
        private const val CHANNEL_ID = "visitor_requests_v3"
        private const val OLD_CHANNEL_ID = "visitor_requests_v2"
        private const val RING_FOR_MS = 60_000L
        private const val ACTION_APPROVE = "approve"
        private const val ACTION_REJECT = "reject"

        // flutter_local_notifications' intent contract (FlutterLocalNotificationsPlugin / ActionBroadcastReceiver).
        private const val SELECT_NOTIFICATION = "SELECT_NOTIFICATION"
        private const val ACTION_RECEIVER = "com.dexterous.flutterlocalnotifications.ActionBroadcastReceiver"
        private const val ACTION_TAPPED = "com.dexterous.flutterlocalnotifications.ActionBroadcastReceiver.ACTION_TAPPED"
        private const val EXTRA_NOTIFICATION_ID = "notificationId"
        private const val EXTRA_ACTION_ID = "actionId"
        private const val EXTRA_CANCEL_NOTIFICATION = "cancelNotification"
        private const val EXTRA_PAYLOAD = "payload"
    }
}
