import '../../../core/models/flat.dart';

class Complaint {
  Complaint({
    required this.id,
    required this.category,
    required this.subject,
    required this.description,
    this.against,
    required this.priority,
    required this.status,
    this.flat,
    this.resolutionNotes,
    this.resolvedAt,
    this.closedAt,
    this.createdAt,
  });

  factory Complaint.fromJson(Map<String, dynamic> json) {
    return Complaint(
      id: json['id'] as int,
      category: json['category'] as String,
      subject: json['subject'] as String,
      description: json['description'] as String? ?? '',
      against: json['against'] as String?,
      priority: json['priority'] as String,
      status: json['status'] as String,
      flat: json['flat'] != null ? Flat.fromJson(json['flat'] as Map<String, dynamic>) : null,
      resolutionNotes: json['resolution_notes'] as String?,
      resolvedAt: json['resolved_at'] as String?,
      closedAt: json['closed_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  final int id;
  final String category;
  final String subject;
  final String description;
  final String? against;
  final String priority;
  final String status;
  final Flat? flat;
  final String? resolutionNotes;
  final String? resolvedAt;
  final String? closedAt;
  final String? createdAt;

  static const categories = [
    'noise', 'parking', 'security', 'staff_behavior',
    'cleanliness', 'rule_violation', 'other',
  ];

  static const priorities = ['low', 'medium', 'high', 'urgent'];
}
