import 'package:flutter/material.dart';

import '../../api/api_exception.dart';
import '../../theme/app_colors.dart';

/// Builds a sheet's fields. [setState] rebuilds the sheet (e.g. to reveal
/// an "Other" text field); [serverErrors] holds the backend's validation
/// message per API field name, so each field can show it right below itself
/// via `errorText: serverErrors['relation']`.
typedef FcFieldsBuilder = List<Widget> Function(
  BuildContext context,
  StateSetter setState,
  Map<String, String> serverErrors,
);

/// The FlatCare add/edit form, presented as a bottom sheet: header with an
/// icon, keyboard-aware scrolling body, field-level validation (client and
/// server), a loading state that disables Save and blocks duplicate taps,
/// and Cancel/Save buttons pinned under the fields.
class FcFormSheet extends StatefulWidget {
  const FcFormSheet._({
    required this.title,
    this.subtitle,
    required this.icon,
    required this.fieldsBuilder,
    required this.onSubmit,
    required this.submitLabel,
  });

  final String title;
  final String? subtitle;
  final IconData icon;
  final FcFieldsBuilder fieldsBuilder;
  final Future<void> Function() onSubmit;
  final String submitLabel;

  /// Returns `true` once [onSubmit] completed without throwing.
  static Future<bool> show(
    BuildContext context, {
    required String title,
    String? subtitle,
    IconData icon = Icons.edit_note_rounded,
    required FcFieldsBuilder fieldsBuilder,
    required Future<void> Function() onSubmit,
    String submitLabel = 'Save',
  }) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (_) => FcFormSheet._(
        title: title,
        subtitle: subtitle,
        icon: icon,
        fieldsBuilder: fieldsBuilder,
        onSubmit: onSubmit,
        submitLabel: submitLabel,
      ),
    );
    return saved == true;
  }

  @override
  State<FcFormSheet> createState() => _FcFormSheetState();
}

class _FcFormSheetState extends State<FcFormSheet> {
  final _formKey = GlobalKey<FormState>();
  bool _isSubmitting = false;
  String? _error;
  Map<String, String> _serverErrors = const {};

  Future<void> _submit() async {
    if (_isSubmitting) return;
    setState(() => _serverErrors = const {});
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await widget.onSubmit();
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      final fieldErrors = {
        for (final entry in (e.fieldErrors ?? const <String, List<String>>{}).entries)
          if (entry.value.isNotEmpty) entry.key: entry.value.first,
      };
      setState(() {
        _serverErrors = fieldErrors;
        // Field errors show under their fields; the banner only carries a
        // general message (network, server, or a field we don't render).
        _error = fieldErrors.isEmpty ? e.message : 'Please fix the highlighted fields.';
      });
    } catch (_) {
      if (mounted) setState(() => _error = 'Something went wrong. Please try again.');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final fields = widget.fieldsBuilder(context, setState, _serverErrors);

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(14)),
                    child: Icon(widget.icon, color: Colors.white),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(widget.title, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800)),
                        if (widget.subtitle != null)
                          Text(widget.subtitle!, style: TextStyle(fontSize: 13.5, color: scheme.onSurfaceVariant)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    for (final field in fields) ...[field, const SizedBox(height: 14)],
                    if (_error != null)
                      Container(
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 8),
                        decoration: BoxDecoration(
                          color: AppColors.dangerSoft,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _error!,
                                style: const TextStyle(color: AppColors.danger, fontWeight: FontWeight.w600, fontSize: 13.5),
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 6, 20, 16),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _isSubmitting ? null : () => Navigator.of(context).pop(false),
                        child: const Text('Cancel'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: FilledButton(
                        onPressed: _isSubmitting ? null : _submit,
                        child: _isSubmitting
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white),
                              )
                            : Text(widget.submitLabel),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// A branded extended FAB ("+ Add Family Member") matching the primary color.
class FcFab extends StatelessWidget {
  const FcFab({super.key, required this.label, required this.onPressed, this.icon = Icons.add_rounded});

  final String label;
  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton.extended(
      onPressed: onPressed,
      icon: Icon(icon),
      label: Text(label),
    );
  }
}
