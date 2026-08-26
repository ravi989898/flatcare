import 'package:intl/intl.dart';

/// Shared money/date formatting for the bills feature — kept out of the
/// widgets so every card and row renders "₹4,500.00" / "01 May 2024" the
/// same way instead of each screen rolling its own toStringAsFixed calls.
final _currencyFormat = NumberFormat.currency(locale: 'en_IN', symbol: '₹');
final _dateFormat = DateFormat('dd MMM yyyy');

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
