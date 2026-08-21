import 'package:flutter/material.dart';

/// A small colored pill for a status/priority string (e.g. "open",
/// "resolved", "unpaid") — used across maintenance requests, complaints,
/// bills and visitors so every list reads the same way.
class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.label});

  final String label;

  static const _colors = {
    'open': Colors.orange,
    'in_progress': Colors.blue,
    'in_review': Colors.blue,
    'pending': Colors.orange,
    'resolved': Colors.green,
    'paid': Colors.green,
    'checked_in': Colors.green,
    'closed': Colors.grey,
    'checked_out': Colors.grey,
    'cancelled': Colors.grey,
    'rejected': Colors.red,
    'denied': Colors.red,
    'overdue': Colors.red,
    'unpaid': Colors.red,
    'partially_paid': Colors.orange,
    'urgent': Colors.red,
    'high': Colors.deepOrange,
    'medium': Colors.orange,
    'low': Colors.blueGrey,
  };

  @override
  Widget build(BuildContext context) {
    final color = _colors[label.toLowerCase()] ?? Colors.blueGrey;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.replaceAll('_', ' '),
        style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12),
      ),
    );
  }
}
