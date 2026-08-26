class Announcement {
  Announcement({
    required this.id,
    required this.title,
    required this.body,
    required this.category,
    required this.isPinned,
    this.postedBy,
    this.publishedAt,
    this.expiresAt,
  });

  factory Announcement.fromJson(Map<String, dynamic> json) {
    return Announcement(
      id: json['id'] as int,
      title: json['title'] as String,
      body: json['body'] as String,
      category: json['category'] as String,
      isPinned: json['is_pinned'] as bool? ?? false,
      postedBy: json['posted_by'] as String?,
      publishedAt: json['published_at'] as String?,
      expiresAt: json['expires_at'] as String?,
    );
  }

  final int id;
  final String title;
  final String body;
  final String category;
  final bool isPinned;
  final String? postedBy;
  final String? publishedAt;
  final String? expiresAt;
}
