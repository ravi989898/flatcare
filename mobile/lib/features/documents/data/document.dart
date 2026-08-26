class AppDocument {
  AppDocument({
    required this.id,
    required this.title,
    required this.category,
    required this.fileName,
    required this.fileSize,
    this.mimeType,
    required this.url,
    this.uploadedAt,
  });

  factory AppDocument.fromJson(Map<String, dynamic> json) {
    return AppDocument(
      id: json['id'] as int,
      title: json['title'] as String,
      category: json['category'] as String,
      fileName: json['file_name'] as String,
      fileSize: json['file_size'] as int? ?? 0,
      mimeType: json['mime_type'] as String?,
      url: json['url'] as String,
      uploadedAt: json['uploaded_at'] as String?,
    );
  }

  final int id;
  final String title;
  final String category;
  final String fileName;
  final int fileSize;
  final String? mimeType;
  final String url;
  final String? uploadedAt;

  /// category -> display label, matching the mockup's Documents tiles.
  static const categoryLabels = {
    'society': 'Society Documents',
    'maintenance_bill': 'Maintenance Bills',
    'notice': 'Notices & Circulars',
    'legal': 'Legal Documents',
    'bylaw': 'By-Laws',
  };

  String get readableSize {
    if (fileSize < 1024) return '$fileSize B';
    if (fileSize < 1024 * 1024) return '${(fileSize / 1024).toStringAsFixed(0)} KB';
    return '${(fileSize / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
}
