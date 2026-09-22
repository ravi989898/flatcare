import 'package:flutter/material.dart';

import '../api/api_exception.dart';
import '../theme/app_theme.dart';

/// A polished, reusable "Add/Edit X" form dialog — rounded corners, a
/// gradient primary action, an inline error banner (so API validation
/// errors show right there instead of behind a SnackBar the dialog
/// covers), a loading state that disables both buttons and blocks repeat
/// taps while `onSubmit` runs, and a scrollable body capped to a max
/// height so it never gets hidden behind the keyboard or clipped on a
/// small screen.
class AppFormDialog extends StatefulWidget {
  const AppFormDialog({
    super.key,
    required this.title,
    required this.formKey,
    required this.fields,
    required this.onSubmit,
    this.submitLabel = 'Save',
  });

  final String title;
  final GlobalKey<FormState> formKey;
  final List<Widget> fields;
  final Future<void> Function() onSubmit;
  final String submitLabel;

  /// Shows the dialog and returns `true` once [onSubmit] has completed
  /// successfully, or `null` if the user cancelled.
  static Future<bool?> show(
    BuildContext context, {
    required String title,
    required GlobalKey<FormState> formKey,
    required List<Widget> fields,
    required Future<void> Function() onSubmit,
    String submitLabel = 'Save',
  }) {
    return showDialog<bool>(
      context: context,
      builder: (_) => AppFormDialog(
        title: title,
        formKey: formKey,
        fields: fields,
        onSubmit: onSubmit,
        submitLabel: submitLabel,
      ),
    );
  }

  @override
  State<AppFormDialog> createState() => _AppFormDialogState();
}

class _AppFormDialogState extends State<AppFormDialog> {
  bool _isSubmitting = false;
  String? _error;

  Future<void> _handleSubmit() async {
    if (_isSubmitting) return;
    if (!widget.formKey.currentState!.validate()) return;

    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await widget.onSubmit();
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      final fieldMessages = e.fieldErrors?.values.expand((messages) => messages).toList();
      if (mounted) {
        setState(() => _error = (fieldMessages != null && fieldMessages.isNotEmpty) ? fieldMessages.join('\n') : e.message);
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
        child: ConstrainedBox(
          constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.8),
          child: SingleChildScrollView(
            child: Form(
              key: widget.formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(widget.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 18),
                  for (final field in widget.fields) ...[field, const SizedBox(height: 14)],
                  if (_error != null) ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.red.shade200),
                      ),
                      child: Text(_error!, style: TextStyle(color: Colors.red.shade700, fontSize: 13)),
                    ),
                    const SizedBox(height: 6),
                  ],
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _isSubmitting ? null : () => Navigator.of(context).pop(),
                          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                          child: const Text('Cancel'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(12),
                            gradient: _isSubmitting ? null : AppTheme.brandGradient,
                            color: _isSubmitting ? Colors.grey.shade300 : null,
                          ),
                          child: ElevatedButton(
                            onPressed: _isSubmitting ? null : _handleSubmit,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: _isSubmitting
                                ? const SizedBox(
                                    height: 18,
                                    width: 18,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                  )
                                : Text(widget.submitLabel, style: const TextStyle(fontWeight: FontWeight.w700)),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
