class CommitteeMember {
  CommitteeMember({
    required this.userId,
    required this.name,
    this.phone,
    this.position,
    this.photoUrl,
  });

  factory CommitteeMember.fromJson(Map<String, dynamic> json) {
    return CommitteeMember(
      userId: json['user_id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String?,
      position: json['position'] as String?,
      photoUrl: json['photo_url'] as String?,
    );
  }

  final int userId;
  final String name;
  final String? phone;
  final String? position;
  final String? photoUrl;
}
