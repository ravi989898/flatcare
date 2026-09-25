import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/fc/fc.dart';
import '../../visitors/data/visitor.dart';
import '../data/guard_visitor_repository.dart';
import '../widgets/gate_visitor_card.dart';

/// Scan a resident's gate pass QR (or type its pass number) and see at once
/// whether it is valid, not valid yet, expired, cancelled or unknown. A valid
/// pass shows the visitor with Allow Entry; a multi-day pass can be scanned
/// again on each visit until it expires.
class GuardPassScannerScreen extends ConsumerStatefulWidget {
  const GuardPassScannerScreen({super.key});

  @override
  ConsumerState<GuardPassScannerScreen> createState() => _GuardPassScannerScreenState();
}

class _GuardPassScannerScreenState extends ConsumerState<GuardPassScannerScreen> {
  final _scanner = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
    formats: const [BarcodeFormat.qrCode],
  );

  PassCheck? _result;
  bool _checking = false;

  @override
  void dispose() {
    _scanner.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_checking || _result != null) return;
    final code = capture.barcodes.map((b) => b.rawValue).whereType<String>().firstOrNull;
    if (code != null && code.trim().isNotEmpty) _verify(code);
  }

  Future<void> _verify(String code) async {
    setState(() => _checking = true);
    try {
      final result = await ref.read(guardVisitorRepositoryProvider).verifyPass(code);
      await _scanner.stop();
      if (mounted) setState(() => _result = result);
    } on ApiException catch (e) {
      if (mounted) showFcSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  Future<void> _scanNext() async {
    setState(() => _result = null);
    await _scanner.start();
  }

  Future<void> _enterCode() async {
    final controller = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Enter pass number'),
        content: TextField(
          controller: controller,
          autofocus: true,
          textCapitalization: TextCapitalization.characters,
          textInputAction: TextInputAction.done,
          decoration: const InputDecoration(hintText: 'e.g. K7Q2XD', prefixIcon: Icon(Icons.pin_rounded)),
          onSubmitted: (value) => Navigator.pop(context, value),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, controller.text), child: const Text('Check')),
        ],
      ),
    );
    controller.dispose();
    if (code != null && code.trim().isNotEmpty) await _verify(code);
  }

  @override
  Widget build(BuildContext context) {
    final result = _result;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan Gate Pass'),
        actions: [
          if (result == null)
            IconButton(
              tooltip: 'Torch',
              onPressed: () => _scanner.toggleTorch(),
              icon: const Icon(Icons.flashlight_on_rounded),
            ),
        ],
      ),
      body: result == null ? _buildScanner() : _buildResult(result),
    );
  }

  Widget _buildScanner() {
    return Column(
      children: [
        Expanded(
          child: Stack(
            fit: StackFit.expand,
            children: [
              MobileScanner(
                controller: _scanner,
                onDetect: _onDetect,
                errorBuilder: (context, error) => ColoredBox(
                  color: Colors.black,
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        error.errorCode == MobileScannerErrorCode.permissionDenied
                            ? 'Camera permission is needed to scan passes. Allow it in Settings, or enter the pass number below.'
                            : 'The camera could not be started. Enter the pass number below instead.',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.white, fontSize: 15),
                      ),
                    ),
                  ),
                ),
              ),
              IgnorePointer(
                child: Center(
                  child: Container(
                    width: 250,
                    height: 250,
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.white, width: 3),
                      borderRadius: BorderRadius.circular(24),
                    ),
                  ),
                ),
              ),
              const Positioned(
                left: 24,
                right: 24,
                bottom: 28,
                child: Text(
                  'Point the camera at the QR code on the visitor\'s gate pass',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, shadows: [Shadow(blurRadius: 6)]),
                ),
              ),
              if (_checking)
                const ColoredBox(
                  color: Colors.black45,
                  child: Center(child: CircularProgressIndicator(color: Colors.white)),
                ),
            ],
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: OutlinedButton.icon(
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(50)),
              onPressed: _checking ? null : _enterCode,
              icon: const Icon(Icons.keyboard_rounded),
              label: const Text('Enter pass number'),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildResult(PassCheck result) {
    final (title, color, icon) = _verdictStyle(result.result);
    final visitor = result.visitor;

    return SafeArea(
      child: Column(
        children: [
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 20),
                  decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(22)),
                  child: Column(
                    children: [
                      Icon(icon, color: Colors.white, size: 64),
                      const SizedBox(height: 10),
                      Text(title, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: 0.5)),
                      const SizedBox(height: 8),
                      Text(
                        result.message,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.white, fontSize: 14.5, fontWeight: FontWeight.w500),
                      ),
                      if (visitor?.passCode != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          'Pass No. ${visitor!.passCode}',
                          style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.w700, letterSpacing: 1.2),
                        ),
                      ],
                    ],
                  ),
                ),
                if (visitor != null) ...[
                  const SizedBox(height: 16),
                  GateVisitorCard(
                    visitor: visitor,
                    onUpdated: (updated) {
                      if (mounted) {
                        setState(() => _result = PassCheck(result: updated.passStatus ?? result.result, message: _afterAction(updated), visitor: updated));
                      }
                    },
                  ),
                ],
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: FilledButton.icon(
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
              onPressed: _scanNext,
              icon: const Icon(Icons.qr_code_scanner_rounded),
              label: const Text('Scan Next Pass'),
            ),
          ),
        ],
      ),
    );
  }

  String _afterAction(Visitor v) => switch (v.status) {
        'checked_in' => '${v.visitorName} has entered.',
        'checked_out' => '${v.visitorName} has exited.',
        _ => '',
      };

  static (String, Color, IconData) _verdictStyle(String result) => switch (result) {
        'valid' => ('VALID PASS', AppColors.success, Icons.verified_rounded),
        'inside' => ('ALREADY INSIDE', AppColors.accentTeal, Icons.login_rounded),
        'upcoming' => ('NOT VALID YET', AppColors.warning, Icons.schedule_rounded),
        'expired' => ('PASS EXPIRED', AppColors.danger, Icons.timer_off_rounded),
        'used' => ('ALREADY USED', AppColors.danger, Icons.history_rounded),
        'cancelled' => ('PASS CANCELLED', AppColors.danger, Icons.cancel_rounded),
        _ => ('INVALID PASS', AppColors.danger, Icons.gpp_bad_rounded),
      };
}
