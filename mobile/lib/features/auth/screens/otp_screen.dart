import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';

/// Step 2 of OTP login: the code the backend's OtpService sent - on
/// WhatsApp (default) or by SMS through 2Factor, per [channel].
class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({super.key, required this.mobileNumber, this.channel = 'whatsapp'});

  final String mobileNumber;

  /// 'whatsapp' or 'sms', as returned by /auth/otp/request.
  final String channel;

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final _formKey = GlobalKey<FormState>();
  final _otpController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    try {
      await ref.read(authControllerProvider.notifier).loginWithOtp(
            mobileNumber: widget.mobileNumber,
            otp: _otpController.text.trim(),
          );
      // On success, go_router's redirect (watching authControllerProvider)
      // takes over and navigates to /home — nothing to do here.
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
        title: const Text('Verify OTP'),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 400),
                child: Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 16, offset: const Offset(0, 6)),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (widget.channel == 'whatsapp') ...[
                        _WhatsAppNotice(mobileNumber: widget.mobileNumber),
                      ] else ...[
                        const Text('🔐', style: TextStyle(fontSize: 36)),
                        const SizedBox(height: 12),
                        Text(
                          'Enter the OTP sent to ${widget.mobileNumber}.',
                          style: const TextStyle(color: Colors.black54),
                        ),
                      ],
                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _otpController,
                        keyboardType: TextInputType.number,
                        autofocus: true,
                        autofillHints: const [AutofillHints.oneTimeCode],
                        inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(6)],
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700, letterSpacing: 8),
                        decoration: InputDecoration(
                          labelText: 'OTP',
                          filled: true,
                          fillColor: const Color(0xFFF6F8FB),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                        ),
                        validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter the OTP' : null,
                        onFieldSubmitted: (_) => _submit(),
                      ),
                      const SizedBox(height: 8),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton(
                          onPressed: () => context.pop(),
                          child: const Text('Change number'),
                        ),
                      ),
                      const SizedBox(height: 16),
                      DecoratedBox(
                        decoration: BoxDecoration(borderRadius: BorderRadius.circular(14), gradient: AppTheme.brandGradient),
                        child: ElevatedButton(
                          onPressed: _isSubmitting ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.transparent,
                            shadowColor: Colors.transparent,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          child: _isSubmitting
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Text('Verify & Login', style: TextStyle(fontWeight: FontWeight.w700)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Tells the user the code went to WhatsApp, not SMS, so they don't sit
/// waiting for a text message.
class _WhatsAppNotice extends StatelessWidget {
  const _WhatsAppNotice({required this.mobileNumber});

  final String mobileNumber;

  static const _whatsAppGreen = Color(0xFF25D366);

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Center(
          child: Container(
            width: 64,
            height: 64,
            decoration: const BoxDecoration(color: _whatsAppGreen, shape: BoxShape.circle),
            alignment: Alignment.center,
            child: const FaIcon(FontAwesomeIcons.whatsapp, color: Colors.white, size: 36),
          ),
        ),
        const SizedBox(height: 16),
        const Text(
          'Check your WhatsApp',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: _whatsAppGreen.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: _whatsAppGreen.withValues(alpha: 0.4)),
          ),
          child: Row(
            children: [
              const FaIcon(FontAwesomeIcons.whatsapp, color: Color(0xFF128C7E), size: 22),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Your OTP has been sent on WhatsApp to $mobileNumber - not by SMS. '
                  "Open WhatsApp, copy the code from FlatCare's message and enter it below.",
                  style: const TextStyle(color: Colors.black87, height: 1.35),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
