class DailyHelper {
  DailyHelper({
    required this.id,
    required this.name,
    required this.phone,
    required this.helperType,
    this.photoUrl,
  });

  factory DailyHelper.fromJson(Map<String, dynamic> json) {
    return DailyHelper(
      id: json['id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String,
      helperType: json['helper_type'] as String? ?? 'other',
      photoUrl: json['photo_url'] as String?,
    );
  }

  final int id;
  final String name;
  final String phone;
  final String helperType;
  final String? photoUrl;

  static const types = ['maid', 'cook', 'driver', 'nanny', 'gardener', 'other'];
}
