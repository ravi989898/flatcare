class Flat {
  Flat({
    required this.id,
    required this.flatNumber,
    this.floorNumber,
    this.flatType,
    required this.displayLabel,
    this.blockId,
    this.blockName,
  });

  factory Flat.fromJson(Map<String, dynamic> json) {
    final block = json['block'] as Map<String, dynamic>?;

    return Flat(
      id: json['id'] as int,
      flatNumber: json['flat_number'] as String,
      floorNumber: json['floor_number']?.toString(),
      flatType: json['flat_type'] as String?,
      displayLabel: json['display_label'] as String? ?? json['flat_number'] as String,
      blockId: block?['id'] as int?,
      blockName: block?['name'] as String?,
    );
  }

  final int id;
  final String flatNumber;
  final String? floorNumber;
  final String? flatType;
  final String displayLabel;
  final int? blockId;
  final String? blockName;
}
