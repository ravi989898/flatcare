import '../../../core/models/flat.dart';

class MaintenanceRequest {
  MaintenanceRequest({
    required this.id,
    required this.category,
    required this.title,
    required this.description,
    required this.priority,
    required this.status,
    this.flat,
    this.raisedByName,
    this.resolutionNotes,
    this.resolvedAt,
    this.closedAt,
    this.createdAt,
  });

  factory MaintenanceRequest.fromJson(Map<String, dynamic> json) {
    return MaintenanceRequest(
      id: json['id'] as int,
      category: json['category'] as String,
      title: json['title'] as String,
      description: json['description'] as String? ?? '',
      priority: json['priority'] as String,
      status: json['status'] as String,
      flat: json['flat'] != null ? Flat.fromJson(json['flat'] as Map<String, dynamic>) : null,
      raisedByName: json['raised_by_name'] as String?,
      resolutionNotes: json['resolution_notes'] as String?,
      resolvedAt: json['resolved_at'] as String?,
      closedAt: json['closed_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  final int id;
  final String category;
  final String title;
  final String description;
  final String priority;
  final String status;
  final Flat? flat;
  final String? raisedByName;
  final String? resolutionNotes;
  final String? resolvedAt;
  final String? closedAt;
  final String? createdAt;

  static const categories = [
    'plumbing', 'electrical', 'carpentry', 'painting',
    'cleaning', 'security', 'lift', 'common_area', 'other',
  ];

  static const priorities = ['low', 'medium', 'high', 'urgent'];
}
