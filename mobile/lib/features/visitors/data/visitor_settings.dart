class VisitorSettings {
  VisitorSettings({required this.flatId, required this.guestApprovalRequired, required this.houseClosed});

  factory VisitorSettings.fromJson(Map<String, dynamic> json) {
    return VisitorSettings(
      flatId: json['flat_id'] as int,
      guestApprovalRequired: json['guest_approval_required'] as bool? ?? false,
      houseClosed: json['house_closed'] as bool? ?? false,
    );
  }

  final int flatId;
  final bool guestApprovalRequired;
  final bool houseClosed;
}
