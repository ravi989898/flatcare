class Flat {
  Flat({
    required this.id,
    required this.flatNumber,
    this.floorNumber,
    this.flatType,
    this.areaSqft,
    this.ownerName,
    required this.displayLabel,
    this.blockId,
    this.blockName,
    this.houseClosed = false,
    this.residents = const [],
  });

  factory Flat.fromJson(Map<String, dynamic> json) {
    final block = json['block'] as Map<String, dynamic>?;

    return Flat(
      id: json['id'] as int,
      flatNumber: json['flat_number'] as String,
      floorNumber: json['floor_number']?.toString(),
      flatType: json['flat_type'] as String?,
      areaSqft: (json['area_sqft'] as num?)?.toDouble(),
      ownerName: json['owner_name'] as String?,
      displayLabel: json['display_label'] as String? ?? json['flat_number'] as String,
      blockId: block?['id'] as int?,
      blockName: block?['name'] as String?,
      houseClosed: json['house_closed'] as bool? ?? false,
      residents: [
        for (final r in (json['residents'] as List? ?? const []))
          FlatResidentBrief.fromJson(r as Map<String, dynamic>),
      ],
    );
  }

  final int id;
  final String flatNumber;
  final String? floorNumber;
  final String? flatType;
  final double? areaSqft;
  final String? ownerName;
  final String displayLabel;
  final int? blockId;
  final String? blockName;

  /// The resident switched on House Closed (Visitor Settings) — the gate
  /// must not let anyone in.
  final bool houseClosed;

  /// Only sent on the guard's closed-houses list.
  final List<FlatResidentBrief> residents;
}

/// A resident's name/phone as sent alongside a flat on the guard app.
class FlatResidentBrief {
  FlatResidentBrief({this.name, this.phone, this.residentType});

  factory FlatResidentBrief.fromJson(Map<String, dynamic> json) => FlatResidentBrief(
        name: json['name'] as String?,
        phone: json['phone'] as String?,
        residentType: json['resident_type'] as String?,
      );

  final String? name;
  final String? phone;
  final String? residentType;
}
