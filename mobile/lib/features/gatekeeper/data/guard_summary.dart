/// Counts for the Gate Duty tiles on the guard home screen
/// (GET /guard/summary).
class GuardSummary {
  const GuardSummary({
    this.pendingRequests = 0,
    this.visitorsToday = 0,
    this.insideNow = 0,
    this.activePasses = 0,
    this.closedHouses = 0,
  });

  factory GuardSummary.fromJson(Map<String, dynamic> json) => GuardSummary(
        pendingRequests: json['pending_requests'] as int? ?? 0,
        visitorsToday: json['visitors_today'] as int? ?? 0,
        insideNow: json['inside_now'] as int? ?? 0,
        activePasses: json['active_passes'] as int? ?? 0,
        closedHouses: json['closed_houses'] as int? ?? 0,
      );

  final int pendingRequests;
  final int visitorsToday;
  final int insideNow;
  final int activePasses;
  final int closedHouses;
}
