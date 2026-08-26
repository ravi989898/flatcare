class Event {
  Event({
    required this.id,
    required this.title,
    this.description,
    required this.category,
    this.location,
    this.startAt,
    this.endAt,
    required this.status,
    this.postedBy,
  });

  factory Event.fromJson(Map<String, dynamic> json) {
    return Event(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      category: json['category'] as String,
      location: json['location'] as String?,
      startAt: json['start_at'] as String?,
      endAt: json['end_at'] as String?,
      status: json['status'] as String,
      postedBy: json['posted_by'] as String?,
    );
  }

  final int id;
  final String title;
  final String? description;
  final String category;
  final String? location;
  final String? startAt;
  final String? endAt;
  final String status;
  final String? postedBy;
}
