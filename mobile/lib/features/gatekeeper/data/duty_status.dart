/// The acting guard's own duty state (Api\V1\Guard\DutyController::show) —
/// backs the on-duty/off-duty card at the top of the Gatekeeper home
/// screen.
class DutyStatus {
  DutyStatus({
    required this.name,
    required this.phone,
    required this.assignedShift,
    required this.onDuty,
    this.currentShift,
    this.startedAt,
  });

  factory DutyStatus.fromJson(Map<String, dynamic> json) {
    return DutyStatus(
      name: json['name'] as String,
      phone: json['phone'] as String,
      assignedShift: json['assigned_shift'] as String,
      onDuty: json['on_duty'] as bool,
      currentShift: json['current_shift'] as String?,
      startedAt: json['started_at'] as String?,
    );
  }

  final String name;
  final String phone;
  final String assignedShift;
  final bool onDuty;
  final String? currentShift;
  final String? startedAt;
}
