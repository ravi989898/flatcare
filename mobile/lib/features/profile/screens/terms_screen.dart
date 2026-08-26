import 'package:flutter/material.dart';

class TermsScreen extends StatelessWidget {
  const TermsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Terms & Conditions')),
      body: const SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: Text(
          'By using the FlatCare app you agree to use it only for legitimate society-related '
          'purposes — raising service requests, viewing notices and bills, and managing your '
          'own household\'s information. Misuse of visitor pre-approvals, false complaints, or '
          'attempts to access another resident\'s data may result in your account being '
          'suspended by your society admin.',
          style: TextStyle(height: 1.5),
        ),
      ),
    );
  }
}
