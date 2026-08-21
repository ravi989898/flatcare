class Society {
  Society({
    required this.id,
    required this.name,
    required this.slug,
    this.city,
    this.state,
    this.logoUrl,
  });

  factory Society.fromJson(Map<String, dynamic> json) {
    return Society(
      id: json['id'] as int,
      name: json['name'] as String,
      slug: json['slug'] as String,
      city: json['city'] as String?,
      state: json['state'] as String?,
      logoUrl: json['logo_url'] as String?,
    );
  }

  final int id;
  final String name;
  final String slug;
  final String? city;
  final String? state;
  final String? logoUrl;
}
