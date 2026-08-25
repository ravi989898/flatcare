import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../data/visitor.dart';

/// Shows the shareable pass code for a visitor a resident just pre-approved
/// — the "Gate Pass" tile in the mockup's Community tab.
class GatePassScreen extends StatelessWidget {
  const GatePassScreen({super.key, required this.visitor});

  final Visitor visitor;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Gate Pass')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(28),
                decoration: BoxDecoration(gradient: AppTheme.brandGradient, borderRadius: BorderRadius.circular(24)),
                child: Column(
                  children: [
                    const Icon(Icons.verified_outlined, color: Colors.white, size: 40),
                    const SizedBox(height: 12),
                    const Text('Visitor Pre-Approved', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 18)),
                    const SizedBox(height: 20),
                    Text(
                      visitor.passCode ?? '——————',
                      style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w800, letterSpacing: 6),
                    ),
                    const SizedBox(height: 8),
                    const Text('Share this code with the visitor or the security desk', style: TextStyle(color: Colors.white70, fontSize: 12), textAlign: TextAlign.center),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              Card(
                child: Column(
                  children: [
                    ListTile(leading: const Icon(Icons.person_outline), title: Text(visitor.visitorName), subtitle: Text(visitor.purpose.replaceAll('_', ' '))),
                    if (visitor.visitorPhone != null && visitor.visitorPhone!.isNotEmpty)
                      ListTile(leading: const Icon(Icons.phone_outlined), title: Text(visitor.visitorPhone!)),
                    if (visitor.vehicleNumber != null && visitor.vehicleNumber!.isNotEmpty)
                      ListTile(leading: const Icon(Icons.directions_car_outlined), title: Text(visitor.vehicleNumber!)),
                    if (visitor.expectedAt != null)
                      ListTile(leading: const Icon(Icons.schedule_outlined), title: Text('Expected: ${visitor.expectedAt}')),
                  ],
                ),
              ),
              const Spacer(),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () => context.go('/visitors'),
                  child: const Text('Back to Visitors'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
