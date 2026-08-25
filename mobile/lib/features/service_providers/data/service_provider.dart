class ServiceProvider {
  ServiceProvider({
    required this.id,
    required this.name,
    required this.serviceType,
    required this.phone,
    this.notes,
  });

  factory ServiceProvider.fromJson(Map<String, dynamic> json) {
    return ServiceProvider(
      id: json['id'] as int,
      name: json['name'] as String,
      serviceType: json['service_type'] as String,
      phone: json['phone'] as String,
      notes: json['notes'] as String?,
    );
  }

  final int id;
  final String name;
  final String serviceType;
  final String phone;
  final String? notes;
}
