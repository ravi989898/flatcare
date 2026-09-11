import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_logo.dart';
import '../data/auth_repository.dart';

/// Step 1 of the resident app's primary login: mobile number only. A super
/// admin sets a flat's mobile_number (see Admin\SocietyStructureController)
/// and whoever verifies that number on the next screen is signed in as that
/// flat's resident — see OtpAuthController on the backend. Email/password
/// still works for accounts that have one (context.push('/login/email')
/// below) but isn't the default any more.
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _mobileController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _mobileController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final mobileNumber = _mobileController.text.trim();

    setState(() => _isSubmitting = true);
    try {
      await ref.read(authRepositoryProvider).requestOtp(mobileNumber);
      if (!mounted) return;
      context.push('/login/otp', extra: mobileNumber);
    } on ApiException catch (e) {
      if (!mounted) return;
      // A plain alert rather than a snackbar - this specific message ("not
      // registered, contact your admin") is easy to miss in a snackbar that
      // auto-dismisses, and it's the one error residents are expected to
      // actually hit before their flat has a number set.
      showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Mobile number not found'),
          content: Text(e.message),
          actions: [TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('OK'))],
        ),
      );
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showSignUpInfo() {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Need an account?'),
        content: const Text(
          'FlatCare accounts are set up by your society admin. Contact your society office to get your flat registered.',
        ),
        actions: [TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('OK'))],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 32, 24, 24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Column(
                    children: [
                      const AppLogo(size: 76),
                      const SizedBox(height: 14),
                      ShaderMask(
                        shaderCallback: (bounds) => AppTheme.brandGradient.createShader(bounds),
                        child: const Text(
                          'FlatCare',
                          style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800),
                        ),
                      ),
                      const SizedBox(height: 4),
                      const Text('Smart Living, Better Together', style: TextStyle(color: Colors.black45, fontSize: 13)),
                    ],
                  ),
                ),
                const SizedBox(height: 36),
                const Text('Welcome Back!', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                const Text('Enter your mobile number to continue', style: TextStyle(color: Colors.black54)),
                const SizedBox(height: 24),
                Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      TextFormField(
                        controller: _mobileController,
                        keyboardType: TextInputType.phone,
                        autofillHints: const [AutofillHints.telephoneNumber],
                        decoration: InputDecoration(
                          labelText: 'Mobile Number',
                          prefixIcon: const Icon(Icons.phone_android_outlined, color: AppTheme.brandBlue),
                          filled: true,
                          fillColor: const Color(0xFFF6F7FC),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                        ),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) return 'Enter your mobile number';
                          if (value.trim().length < 10) return 'Enter a valid mobile number';
                          return null;
                        },
                        onFieldSubmitted: (_) => _submit(),
                      ),
                      const SizedBox(height: 20),
                      DecoratedBox(
                        decoration: BoxDecoration(borderRadius: BorderRadius.circular(14), gradient: AppTheme.brandGradient),
                        child: ElevatedButton(
                          onPressed: _isSubmitting ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.transparent,
                            shadowColor: Colors.transparent,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 15),
                          ),
                          child: _isSubmitting
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Text('Send OTP', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Center(
                        child: TextButton(
                          onPressed: () => context.push('/login/email'),
                          child: const Text('Login with email instead'),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                Center(
                  child: RichText(
                    text: TextSpan(
                      style: const TextStyle(color: Colors.black54, fontSize: 13),
                      children: [
                        const TextSpan(text: "Don't have an account? "),
                        TextSpan(
                          text: 'Sign Up',
                          style: const TextStyle(color: AppTheme.brandBlue, fontWeight: FontWeight.w700),
                          recognizer: TapGestureRecognizer()..onTap = _showSignUpInfo,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
