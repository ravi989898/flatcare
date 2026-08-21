import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_logo.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    try {
      await ref.read(authControllerProvider.notifier).login(
            email: _emailController.text.trim(),
            password: _passwordController.text,
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
      body: SafeArea(
        child: SingleChildScrollView(
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: MediaQuery.of(context).size.height - MediaQuery.of(context).padding.top),
            child: Column(
              children: [
                // Brand banner — gradient header with the logo, so the
                // login screen carries the same identity as the app icon.
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(24, 40, 24, 56),
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [AppTheme.brandNavy, Color(0xFF14487A)],
                    ),
                    borderRadius: BorderRadius.only(bottomLeft: Radius.circular(36), bottomRight: Radius.circular(36)),
                  ),
                  child: Column(
                    children: [
                      const AppLogo(size: 84, halo: true),
                      const SizedBox(height: 16),
                      const Text(
                        'FlatCare',
                        style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, letterSpacing: 0.3),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Everything about your society, in one place',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 14),
                      ),
                    ],
                  ),
                ),

                // Form card, floated up over the banner's rounded bottom edge.
                Transform.translate(
                  offset: const Offset(0, -28),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 420),
                      child: Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(24),
                          boxShadow: [
                            BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 20, offset: const Offset(0, 8)),
                          ],
                        ),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Text(
                                'Welcome back 👋',
                                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 4),
                              const Text('Sign in to your society account', style: TextStyle(color: Colors.black54)),
                              const SizedBox(height: 24),
                              TextFormField(
                                controller: _emailController,
                                keyboardType: TextInputType.emailAddress,
                                autofillHints: const [AutofillHints.email],
                                decoration: InputDecoration(
                                  labelText: 'Email',
                                  prefixIcon: const Icon(Icons.email_outlined, color: AppTheme.brandBlue),
                                  filled: true,
                                  fillColor: const Color(0xFFF6F8FB),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                                ),
                                validator: (value) {
                                  if (value == null || value.trim().isEmpty) return 'Enter your email';
                                  if (!value.contains('@')) return 'Enter a valid email';
                                  return null;
                                },
                              ),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _passwordController,
                                obscureText: _obscurePassword,
                                autofillHints: const [AutofillHints.password],
                                decoration: InputDecoration(
                                  labelText: 'Password',
                                  prefixIcon: const Icon(Icons.lock_outline, color: AppTheme.brandBlue),
                                  filled: true,
                                  fillColor: const Color(0xFFF6F8FB),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                                  suffixIcon: IconButton(
                                    icon: Icon(_obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                                    onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                                  ),
                                ),
                                validator: (value) => (value == null || value.isEmpty) ? 'Enter your password' : null,
                                onFieldSubmitted: (_) => _submit(),
                              ),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () => context.push('/forgot-password'),
                                  child: const Text('Forgot password?'),
                                ),
                              ),
                              const SizedBox(height: 8),
                              DecoratedBox(
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(14),
                                  gradient: AppTheme.brandGradient,
                                ),
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
                                      : const Text('Sign In', style: TextStyle(fontWeight: FontWeight.w700)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
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
