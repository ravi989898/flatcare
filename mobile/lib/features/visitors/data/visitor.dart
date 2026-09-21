import '../../../core/models/flat.dart';

class Visitor {
  Visitor({
    required this.id,
    required this.visitorName,
    this.visitorPhone,
    this.visitorEmail,
    required this.purpose,
    this.vehicleNumber,
    required this.status,
    this.checkInAt,
    this.checkOutAt,
    this.expectedAt,
    this.validUntil,
    this.passCode,
    this.notes,
    this.photoUrl,
    this.flat,
    this.awaitingApproval = false,
    this.entryKind = 'gate_pass',
    this.createdAt,
    this.statusLabel,
    this.canRespond = false,
    this.canEnter = false,
    this.canExit = false,
    this.gateKeeperName,
    this.approvedAt,
    this.rejectedAt,
  });

  factory Visitor.fromJson(Map<String, dynamic> json) {
    final flatJson = json['flat'] as Map<String, dynamic>?;

    return Visitor(
      id: json['id'] as int,
      visitorName: json['visitor_name'] as String,
      visitorPhone: json['visitor_phone'] as String?,
      visitorEmail: json['visitor_email'] as String?,
      purpose: json['purpose'] as String,
      vehicleNumber: json['vehicle_number'] as String?,
      status: json['status'] as String,
      checkInAt: json['check_in_at'] as String?,
      checkOutAt: json['check_out_at'] as String?,
      expectedAt: json['expected_at'] as String?,
      validUntil: json['valid_until'] as String?,
      passCode: json['pass_code'] as String?,
      notes: json['notes'] as String?,
      photoUrl: json['photo_url'] as String?,
      flat: flatJson != null ? Flat.fromJson(flatJson) : null,
      awaitingApproval: json['awaiting_approval'] as bool? ?? false,
      entryKind: json['entry_kind'] as String? ?? 'gate_pass',
      createdAt: json['created_at'] as String?,
      statusLabel: json['status_label'] as String?,
      canRespond: json['can_respond'] as bool? ?? (json['awaiting_approval'] as bool? ?? false),
      canEnter: json['can_enter'] as bool? ?? false,
      canExit: json['can_exit'] as bool? ?? false,
      gateKeeperName: (json['gate_keeper'] as Map<String, dynamic>?)?['name'] as String?,
      approvedAt: json['approved_at'] as String?,
      rejectedAt: json['rejected_at'] as String?,
    );
  }

  final int id;
  final String visitorName;
  final String? visitorPhone;
  final String? visitorEmail;
  final String purpose;
  final String? vehicleNumber;
  final String status;
  final String? checkInAt;
  final String? checkOutAt;
  final String? expectedAt;
  // The "To" end of a Gate Pass's validity window (expectedAt is the "From").
  final String? validUntil;
  final String? passCode;
  final String? notes;
  // A photo attached at the gate (guard walk-in) or by the resident when
  // creating a Gate Pass / Pre-Approval.
  final String? photoUrl;
  // Only present on the gate-security app's society-wide list and on a
  // resident's own list (used to show "Flat A-101" on each card).
  final Flat? flat;
  // True only for a guard-raised entry request still awaiting the
  // resident's decision (see VisitorResource::awaiting_approval on the
  // backend) — distinct from a resident's own self-invite, which is also
  // `pending` but shouldn't show Approve/Reject to the person who made it.
  final bool awaitingApproval;
  // 'gate_pass' (dated pass) or 'pre_approval' (quick standing approval).
  final String entryKind;
  final String? createdAt;
  // PENDING / APPROVED / ENTERED / EXITED / REJECTED, as named by the backend.
  final String? statusLabel;
  // What the server says is allowed next (the buttons follow these, never
  // the local status string): the resident may approve/reject, or the gate
  // may let the visitor in / mark them out.
  final bool canRespond;
  final bool canEnter;
  final bool canExit;
  // The guard who raised the request (null for a resident's own pass).
  final String? gateKeeperName;
  final String? approvedAt;
  final String? rejectedAt;

  static const purposes = ['guest', 'delivery', 'cab', 'service', 'other'];

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
  bool get isCheckedIn => status == 'checked_in';
  bool get isDenied => status == 'denied';

  /// A resident's own unused pass/pre-approval — the only rows they can cancel.
  bool get isCancellable => isPending && !awaitingApproval;

  /// The moment shown on a visitor card: when they came in, else when they
  /// are expected, else when the entry was created.
  String? get displayTime => checkInAt ?? expectedAt ?? createdAt;
}
