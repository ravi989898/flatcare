import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/flat.dart';
import '../../auth/providers/auth_provider.dart';
import 'visitor_ui.dart';

/// The flats the signed-in resident lives in.
final residentFlatsProvider = Provider<List<Flat>>((ref) {
  return ref.watch(authControllerProvider).valueOrNull?.user.flats.map((r) => r.flat).toList() ?? [];
});

/// A "Flat" dropdown, shown only when the resident lives in more than one
/// flat — with a single flat there is nothing to choose, so it stays hidden
/// and the form just uses that flat.
class FlatPicker extends StatelessWidget {
  const FlatPicker({super.key, required this.flats, required this.value, required this.onChanged});

  final List<Flat> flats;
  final Flat? value;
  final ValueChanged<Flat?> onChanged;

  @override
  Widget build(BuildContext context) {
    if (flats.length < 2) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: VisitorDropdown<Flat>(
        label: 'Flat',
        hint: 'Select flat',
        icon: Icons.apartment_rounded,
        required: true,
        value: flats.contains(value) ? value : null,
        items: flats.map((flat) => DropdownMenuItem(value: flat, child: Text(flat.displayLabel))).toList(),
        onChanged: onChanged,
      ),
    );
  }
}
