import 'package:intl/intl.dart';

/// Shared money/date formatting for the bills feature — kept out of the
/// widgets so every card and row renders "₹4,500.00" / "01 May 2024" the
/// same way instead of each screen rolling its own toStringAsFixed calls.
final _currencyFormat = NumberFormat.currency(locale: 'en_IN', symbol: '₹');
final _dateFormat = DateFormat('dd MMM yyyy');
final _dateTimeFormat = DateFormat('dd MMM, hh:mm a');

String formatCurrency(num amount) => _currencyFormat.format(amount);

/// Renders an ISO `yyyy-MM-dd` string as e.g. "01 May 2024"; returns the
/// raw input unchanged if it isn't a date the app recognizes, and '-' for
/// null, so a formatting surprise never hides a real value.
String formatDate(String? isoDate) {
  if (isoDate == null || isoDate.isEmpty) return '-';

  final parsed = DateTime.tryParse(isoDate);
  if (parsed == null) return isoDate;

  return _dateFormat.format(parsed);
}

/// Same as formatDate but with a time-of-day, for timestamps where *when*
/// matters as much as which day (a visitor's check-in/check-out time on the
/// gate register).
String formatDateTime(String? isoDateTime) {
  if (isoDateTime == null || isoDateTime.isEmpty) return '-';

  final parsed = DateTime.tryParse(isoDateTime);
  if (parsed == null) return isoDateTime;

  return _dateTimeFormat.format(parsed.toLocal());
}

/// "Just now", "5 min ago", "3 h ago", "Yesterday", then a short date — for
/// notification and activity timestamps where recency matters more than
/// the exact time.
String formatRelative(String? isoDateTime) {
  if (isoDateTime == null || isoDateTime.isEmpty) return '';

  final parsed = DateTime.tryParse(isoDateTime)?.toLocal();
  if (parsed == null) return isoDateTime;

  final diff = DateTime.now().difference(parsed);
  if (diff.inMinutes < 1) return 'Just now';
  if (diff.inMinutes < 60) return '${diff.inMinutes} min ago';
  if (diff.inHours < 24) return '${diff.inHours} h ago';
  if (diff.inDays == 1) return 'Yesterday';
  if (diff.inDays < 7) return '${diff.inDays} days ago';

  return _dateFormat.format(parsed);
}
