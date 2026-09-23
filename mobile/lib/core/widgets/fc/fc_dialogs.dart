import 'package:flutter/material.dart';

import '../../theme/app_colors.dart';

/// The FlatCare confirmation popup — icon, title, plain-language message and
/// two full-width buttons. Use it for every destructive or important action
/// (delete, remove, sign out) instead of a bare AlertDialog. Returns `true`
/// only when the user confirms.
Future<bool> showFcConfirmDialog(
  BuildContext context, {
  required String title,
  required String message,
  String confirmLabel = 'Confirm',
  String cancelLabel = 'Cancel',
  bool danger = false,
  IconData? icon,
}) async {
  final color = danger ? AppColors.danger : AppColors.primary;

  final result = await showDialog<bool>(
    context: context,
    builder: (context) => Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 28),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(color: AppColors.soft(color), shape: BoxShape.circle),
              child: Icon(icon ?? (danger ? Icons.delete_outline_rounded : Icons.help_outline_rounded), color: color, size: 30),
            ),
            const SizedBox(height: 16),
            Text(title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 14.5, color: Theme.of(context).colorScheme.onSurfaceVariant, height: 1.4),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(onPressed: () => Navigator.of(context).pop(false), child: Text(cancelLabel)),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    style: FilledButton.styleFrom(backgroundColor: color),
                    onPressed: () => Navigator.of(context).pop(true),
                    child: Text(confirmLabel),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    ),
  );

  return result == true;
}

/// A colored floating snackbar for success / error feedback.
void showFcSnack(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        backgroundColor: error ? AppColors.danger : AppColors.success,
        content: Row(
          children: [
            Icon(error ? Icons.error_outline_rounded : Icons.check_circle_rounded, color: Colors.white),
            const SizedBox(width: 10),
            Expanded(child: Text(message, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600))),
          ],
        ),
      ),
    );
}
