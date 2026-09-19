import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../data/visitor.dart';

/// Colour system from the "FlatCare Visitor — New UI" design doc. Kept local
/// to the Visitor module so the rest of the app keeps its own look.
class VisitorColors {
  VisitorColors._();

  static const primary = Color(0xFF11A9E8);
  static const primaryDark = Color(0xFF0B8FCB);
  static const green = Color(0xFF176B4D);
  static const mint = Color(0xFFEAF7F0);
  static const gold = Color(0xFFF4C542);
  static const background = Color(0xFFF5F7FC);
  static const text = Color(0xFF1F2937);
  static const muted = Color(0xFF6B7280);
  static const success = Color(0xFF2E9B62);
  static const error = Color(0xFFD64545);
  static const border = Color(0xFFE3E8F2);
  static const primarySoft = Color(0xFFE6F6FD);

  static const headerGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF2BB9F0), primary, Color(0xFF0E96D6)],
  );
}

/// Page shell used by every inner Visitor screen: a blue gradient app bar
/// with a back arrow and centred title, and a rounded light sheet below it.
class VisitorScaffold extends StatelessWidget {
  const VisitorScaffold({super.key, required this.title, required this.body, this.fab, this.actions = const [], this.bottom});

  final String title;
  final Widget body;
  final Widget? fab;
  final List<Widget> actions;

  /// Sticky area under the body (e.g. a full-width Save button).
  final Widget? bottom;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: VisitorColors.primary,
      floatingActionButton: fab,
      body: DecoratedBox(
        decoration: const BoxDecoration(gradient: VisitorColors.headerGradient),
        child: Column(
          children: [
            SafeArea(
              bottom: false,
              child: SizedBox(
                height: 64,
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
                      tooltip: 'Back',
                      onPressed: () => Navigator.of(context).maybePop(),
                    ),
                    Expanded(
                      child: Text(
                        title,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700),
                      ),
                    ),
                    // Mirrors the back button's width so the title stays centred.
                    if (actions.isEmpty) const SizedBox(width: 48) else ...actions,
                  ],
                ),
              ),
            ),
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                child: ColoredBox(
                  color: VisitorColors.background,
                  child: Column(
                    children: [
                      Expanded(child: body),
                      if (bottom != null)
                        SafeArea(
                          top: false,
                          child: Padding(padding: const EdgeInsets.fromLTRB(20, 8, 20, 16), child: bottom),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// The round "+" action shown on list screens.
class VisitorFab extends StatelessWidget {
  const VisitorFab({super.key, required this.onPressed, this.tooltip = 'Add'});

  final VoidCallback onPressed;
  final String tooltip;

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton(
      onPressed: onPressed,
      tooltip: tooltip,
      backgroundColor: VisitorColors.primary,
      foregroundColor: Colors.white,
      elevation: 4,
      shape: const CircleBorder(),
      child: const Icon(Icons.add_rounded, size: 32),
    );
  }
}

/// Full-width blue call-to-action button with a built-in loading state.
class VisitorPrimaryButton extends StatelessWidget {
  const VisitorPrimaryButton({super.key, required this.label, required this.onPressed, this.loading = false});

  final String label;
  final VoidCallback? onPressed;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 52,
      child: FilledButton(
        onPressed: loading ? null : onPressed,
        style: FilledButton.styleFrom(
          backgroundColor: VisitorColors.primary,
          disabledBackgroundColor: VisitorColors.primary.withValues(alpha: 0.5),
          foregroundColor: Colors.white,
          disabledForegroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
        child: loading
            ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
            : Text(label),
      ),
    );
  }
}

InputDecoration _fieldDecoration({required String hint, required IconData icon, Color iconColor = VisitorColors.primary, Widget? suffix}) {
  OutlineInputBorder border(Color color) =>
      OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: color));

  return InputDecoration(
    hintText: hint,
    hintStyle: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 14),
    filled: true,
    fillColor: Colors.white,
    prefixIcon: Icon(icon, color: iconColor, size: 22),
    suffixIcon: suffix,
    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
    border: border(VisitorColors.border),
    enabledBorder: border(VisitorColors.border),
    focusedBorder: border(VisitorColors.primary),
    errorBorder: border(VisitorColors.error),
    focusedErrorBorder: border(VisitorColors.error),
  );
}

/// Label above a form control, with an optional red required asterisk.
class VisitorFieldLabel extends StatelessWidget {
  const VisitorFieldLabel(this.label, {super.key, this.required = false});

  final String label;
  final bool required;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text.rich(
        TextSpan(
          text: label,
          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: VisitorColors.text),
          children: [
            if (required) const TextSpan(text: ' *', style: TextStyle(color: VisitorColors.error)),
          ],
        ),
      ),
    );
  }
}

class VisitorTextField extends StatelessWidget {
  const VisitorTextField({
    super.key,
    required this.label,
    required this.hint,
    required this.icon,
    required this.controller,
    this.required = false,
    this.keyboardType,
    this.validator,
    this.maxLength,
  });

  final String label;
  final String hint;
  final IconData icon;
  final TextEditingController controller;
  final bool required;
  final TextInputType? keyboardType;
  final String? Function(String?)? validator;
  final int? maxLength;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        VisitorFieldLabel(label, required: required),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          maxLength: maxLength,
          textInputAction: TextInputAction.next,
          decoration: _fieldDecoration(hint: hint, icon: icon).copyWith(counterText: ''),
          validator: validator ??
              (required ? (value) => (value == null || value.trim().isEmpty) ? 'This field is required' : null : null),
        ),
      ],
    );
  }
}

class VisitorDropdown<T> extends StatelessWidget {
  const VisitorDropdown({
    super.key,
    required this.label,
    required this.hint,
    required this.icon,
    required this.items,
    required this.value,
    required this.onChanged,
    this.required = false,
    this.iconColor = VisitorColors.primary,
  });

  final String label;
  final String hint;
  final IconData icon;
  final Color iconColor;
  final List<DropdownMenuItem<T>> items;
  final T? value;
  final ValueChanged<T?> onChanged;
  final bool required;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        VisitorFieldLabel(label, required: required),
        DropdownButtonFormField<T>(
          initialValue: value,
          isExpanded: true,
          icon: const Icon(Icons.keyboard_arrow_down_rounded, color: VisitorColors.muted),
          decoration: _fieldDecoration(hint: hint, icon: icon, iconColor: iconColor),
          items: items,
          onChanged: onChanged,
          validator: required ? (v) => v == null ? 'Please select' : null : null,
        ),
      ],
    );
  }
}

/// A read-only field that opens a date picker when tapped.
class VisitorDateField extends StatelessWidget {
  const VisitorDateField({
    super.key,
    required this.label,
    required this.value,
    required this.onPick,
    this.required = false,
  });

  final String label;
  final DateTime? value;
  final Future<DateTime?> Function() onPick;
  final bool required;

  @override
  Widget build(BuildContext context) {
    return FormField<DateTime>(
      initialValue: value,
      validator: required ? (_) => value == null ? 'Select a date' : null : null,
      builder: (state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            VisitorFieldLabel(label, required: required),
            InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: () async {
                final picked = await onPick();
                if (picked != null) state.didChange(picked);
              },
              child: InputDecorator(
                decoration: _fieldDecoration(hint: '', icon: Icons.calendar_month_rounded).copyWith(
                  errorText: state.errorText,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 15),
                ),
                child: Text(
                  value == null ? 'Select Date' : formatVisitorDay(value!),
                  style: TextStyle(fontSize: 14, color: value == null ? const Color(0xFF9CA3AF) : VisitorColors.text),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

String formatVisitorDay(DateTime d) => '${d.day.toString().padLeft(2, '0')} ${_months[d.month - 1]} ${d.year}';

/// "19 Sep 2026, 10:30 AM" for an ISO timestamp from the API.
String formatVisitorMoment(String? iso) {
  final parsed = iso == null ? null : DateTime.tryParse(iso)?.toLocal();
  if (parsed == null) return '—';

  final hour12 = parsed.hour % 12 == 0 ? 12 : parsed.hour % 12;
  final minute = parsed.minute.toString().padLeft(2, '0');
  final suffix = parsed.hour >= 12 ? 'PM' : 'AM';

  return '${formatVisitorDay(parsed)}, ${hour12.toString().padLeft(2, '0')}:$minute $suffix';
}

String purposeLabel(String purpose) => purpose.isEmpty ? '' : '${purpose[0].toUpperCase()}${purpose.substring(1).replaceAll('_', ' ')}';

/// Icon + colour per visitor purpose, used for avatars without a photo.
({IconData icon, Color color}) purposeVisual(String purpose) {
  return switch (purpose) {
    'delivery' => (icon: Icons.local_shipping_rounded, color: const Color(0xFFF5A623)),
    'cab' => (icon: Icons.local_taxi_rounded, color: const Color(0xFF8E5FE0)),
    'service' => (icon: Icons.build_rounded, color: const Color(0xFF2AB98A)),
    'other' => (icon: Icons.help_outline_rounded, color: const Color(0xFF6B7280)),
    _ => (icon: Icons.person_rounded, color: VisitorColors.primary),
  };
}

/// IN / OUT / PENDING / DENIED pill for a visitor's status.
class VisitorStatusPill extends StatelessWidget {
  const VisitorStatusPill({super.key, required this.visitor});

  final Visitor visitor;

  @override
  Widget build(BuildContext context) {
    final (label, color) = switch (visitor.status) {
      'checked_in' => ('IN', VisitorColors.success),
      'checked_out' => ('OUT', VisitorColors.error),
      'denied' => ('DENIED', VisitorColors.error),
      _ => (visitor.awaitingApproval ? 'REQUEST' : 'EXPECTED', const Color(0xFFB7791F)),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
      child: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 11.5, letterSpacing: 0.3)),
    );
  }
}

/// White rounded card container used for list rows.
class VisitorCardShell extends StatelessWidget {
  const VisitorCardShell({super.key, required this.child, this.padding = const EdgeInsets.all(14)});

  final Widget child;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: const [BoxShadow(color: Color(0x140B3B66), blurRadius: 14, offset: Offset(0, 4))],
      ),
      child: child,
    );
  }
}

/// Friendly empty state with a soft illustration built from icons.
class VisitorEmptyState extends StatelessWidget {
  const VisitorEmptyState({
    super.key,
    required this.icon,
    required this.badgeIcon,
    required this.title,
    required this.message,
    this.color = VisitorColors.primary,
  });

  final IconData icon;
  final IconData badgeIcon;
  final String title;
  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 170,
              height: 170,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Container(
                    width: 160,
                    height: 160,
                    decoration: BoxDecoration(shape: BoxShape.circle, color: color.withValues(alpha: 0.10)),
                  ),
                  Container(
                    width: 108,
                    height: 108,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      boxShadow: [BoxShadow(color: color.withValues(alpha: 0.25), blurRadius: 22, offset: const Offset(0, 8))],
                    ),
                    child: Icon(icon, size: 56, color: color),
                  ),
                  Positioned(
                    right: 20,
                    bottom: 22,
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: const BoxDecoration(shape: BoxShape.circle, color: VisitorColors.success),
                      child: Icon(badgeIcon, size: 22, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            Text(title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: VisitorColors.text)),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(fontSize: 14, height: 1.4, color: VisitorColors.muted)),
          ],
        ),
      ),
    );
  }
}

/// The big circular "Add Photo" picker at the top of the Gate Pass /
/// Pre-Approval / Add-helper forms.
class VisitorPhotoPicker extends StatelessWidget {
  const VisitorPhotoPicker({super.key, required this.path, required this.onChanged, this.size = 108});

  final String? path;
  final ValueChanged<String?> onChanged;
  final double size;

  Future<void> _pick(BuildContext context) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            ListTile(
              leading: const Icon(Icons.photo_camera_rounded, color: VisitorColors.primary),
              title: const Text('Take a photo'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_rounded, color: VisitorColors.primary),
              title: const Text('Choose from gallery'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
            if (path != null)
              ListTile(
                leading: const Icon(Icons.delete_outline_rounded, color: VisitorColors.error),
                title: const Text('Remove photo'),
                onTap: () {
                  Navigator.pop(context);
                  onChanged(null);
                },
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (source == null) return;

    final photo = await ImagePicker().pickImage(source: source, maxWidth: 1280, imageQuality: 85);
    if (photo != null) onChanged(photo.path);
  }

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Semantics(
        button: true,
        label: 'Add photo',
        child: GestureDetector(
          onTap: () => _pick(context),
          child: Container(
            width: size,
            height: size,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: VisitorColors.primarySoft,
              border: Border.all(color: Colors.white, width: 4),
              boxShadow: const [BoxShadow(color: Color(0x2211A9E8), blurRadius: 18, offset: Offset(0, 6))],
              image: path != null ? DecorationImage(image: FileImage(File(path!)), fit: BoxFit.cover) : null,
            ),
            child: path != null
                ? null
                : const Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.photo_camera_rounded, color: VisitorColors.text, size: 30),
                      SizedBox(height: 4),
                      Text('Add Photo', style: TextStyle(fontSize: 12, color: VisitorColors.muted)),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

void showVisitorSnack(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Text(message),
        behavior: SnackBarBehavior.floating,
        backgroundColor: error ? VisitorColors.error : VisitorColors.green,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
}
