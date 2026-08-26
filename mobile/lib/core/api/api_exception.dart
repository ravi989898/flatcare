/// A normalized error surfaced from the backend's {success, message, errors}
/// envelope (see ARCHITECTURE.md §7.2), so screens can show `message`
/// without knowing anything about Dio/HTTP.
class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.fieldErrors,
  });

  final String message;
  final int? statusCode;

  /// Laravel validation errors, when the backend responded 422 with an
  /// `errors: {field: [messages]}` map.
  final Map<String, List<String>>? fieldErrors;

  bool get isUnauthorized => statusCode == 401;

  @override
  String toString() => message;
}
