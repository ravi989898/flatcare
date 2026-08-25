class AppNotification {
  AppNotification({
    required this.id,
    required this.type,
    required this.title,
    this.body,
    this.data,
    required this.isRead,
    this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as int,
      type: json['type'] as String,
      title: json['title'] as String,
      body: json['body'] as String?,
      data: json['data'] as Map<String, dynamic>?,
      isRead: json['is_read'] as bool? ?? false,
      createdAt: json['created_at'] as String?,
    );
  }

  final int id;
  final String type;
  final String title;
  final String? body;
  final Map<String, dynamic>? data;
  final bool isRead;
  final String? createdAt;

  static const _icons = {
    'maintenance_due': '🧾',
    'request_status': '🔧',
    'new_notice': '📣',
    'visitor_arrived': '🚪',
    'event_reminder': '📅',
  };

  String get emoji => _icons[type] ?? '🔔';
}
