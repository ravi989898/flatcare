import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../data/visitor.dart';
import 'visitor_ui.dart';

/// The QR code on a resident's gate pass — the gate scanner reads it
/// (Api\V1\Guard\VisitorController::verifyPass). Encodes the server's
/// `pass_qr`, falling back to the bare pass code, which the server also
/// accepts.
class PassQrCode extends StatelessWidget {
  const PassQrCode({super.key, required this.visitor, this.size = 200});

  final Visitor visitor;
  final double size;

  @override
  Widget build(BuildContext context) {
    final data = visitor.passQr ?? visitor.passCode ?? '';

    return Container(
      padding: EdgeInsets.all(size / 20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(size / 10)),
      child: QrImageView(
        data: data,
        size: size,
        padding: EdgeInsets.zero,
        backgroundColor: Colors.white,
        eyeStyle: const QrEyeStyle(eyeShape: QrEyeShape.square, color: VisitorColors.text),
        dataModuleStyle: const QrDataModuleStyle(dataModuleShape: QrDataModuleShape.square, color: VisitorColors.text),
      ),
    );
  }
}

/// Label + colour for a resident's pass status (see Visitor.passStatus).
(String, Color) passStatusStyle(String? status) => switch (status) {
      'valid' => ('ACTIVE', VisitorColors.success),
      'upcoming' => ('UPCOMING', const Color(0xFFB7791F)),
      'inside' => ('INSIDE', VisitorColors.green),
      'expired' => ('EXPIRED', VisitorColors.error),
      'used' => ('USED', VisitorColors.muted),
      'cancelled' => ('CANCELLED', VisitorColors.error),
      _ => ('INVALID', VisitorColors.error),
    };

/// "19 Sep 2026 → 23 Sep 2026", or just the From day for a one-day pass.
String passValidityLabel(Visitor visitor) {
  final from = formatVisitorMoment(visitor.expectedAt).split(',').first;
  if (visitor.validUntil == null) return from;
  final to = formatVisitorMoment(visitor.validUntil).split(',').first;
  return from == to ? from : '$from  →  $to';
}
