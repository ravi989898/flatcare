import 'package:flutter/material.dart';

import '../../theme/app_colors.dart';

/// One choice in a [FcSelectField].
class FcOption {
  const FcOption(this.value, this.label, {this.icon, this.color});

  final String value;
  final String label;
  final IconData? icon;
  final Color? color;
}

/// A dropdown that looks exactly like a FlatCare text field (label, border,
/// focus, placeholder, validation message below) but opens its options in a
/// bottom sheet — scrollable, with a search box once the list is long. A
/// bottom sheet never ends up hidden behind the Android keyboard the way an
/// inline dropdown menu can, and gives elderly users big tap targets.
class FcSelectField extends FormField<String> {
  FcSelectField({
    super.key,
    required String label,
    required List<FcOption> options,
    String? hint,
    IconData? prefixIcon,
    super.initialValue,
    ValueChanged<String?>? onChanged,
    bool required = true,
    String? errorText,
    FormFieldValidator<String>? validator,
  }) : super(
          validator: validator ??
              (value) => required && (value == null || value.isEmpty) ? 'Please select ${label.toLowerCase()}' : null,
          builder: (field) {
            final selected = options.where((o) => o.value == field.value).firstOrNull;
            final context = field.context;

            return InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () async {
                FocusScope.of(context).unfocus();
                final picked = await showFcOptionSheet(context, title: label, options: options, selected: field.value);
                if (picked != null) {
                  field.didChange(picked);
                  onChanged?.call(picked);
                }
              },
              child: InputDecorator(
                isEmpty: selected == null,
                decoration: InputDecoration(
                  labelText: required ? '$label *' : label,
                  hintText: hint ?? 'Select ${label.toLowerCase()}',
                  prefixIcon: selected?.icon != null
                      ? Icon(selected!.icon, color: selected.color ?? AppColors.primary)
                      : (prefixIcon != null ? Icon(prefixIcon) : null),
                  suffixIcon: const Icon(Icons.keyboard_arrow_down_rounded),
                  errorText: field.errorText ?? errorText,
                ),
                child: selected == null
                    ? null
                    : Text(selected.label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
              ),
            );
          },
        );
}

/// Shows [options] in a bottom sheet and returns the picked value.
Future<String?> showFcOptionSheet(
  BuildContext context, {
  required String title,
  required List<FcOption> options,
  String? selected,
}) {
  return showModalBottomSheet<String>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    builder: (context) => _OptionSheet(title: title, options: options, selected: selected),
  );
}

class _OptionSheet extends StatefulWidget {
  const _OptionSheet({required this.title, required this.options, this.selected});

  final String title;
  final List<FcOption> options;
  final String? selected;

  @override
  State<_OptionSheet> createState() => _OptionSheetState();
}

class _OptionSheetState extends State<_OptionSheet> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final searchable = widget.options.length > 8;
    final visible = widget.options.where((o) => o.label.toLowerCase().contains(_query.toLowerCase())).toList();

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.75),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
              child: Text(widget.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            ),
            if (searchable)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                child: TextField(
                  decoration: const InputDecoration(hintText: 'Search', prefixIcon: Icon(Icons.search)),
                  onChanged: (value) => setState(() => _query = value),
                ),
              ),
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
                itemCount: visible.length,
                itemBuilder: (context, index) {
                  final option = visible[index];
                  final isSelected = option.value == widget.selected;
                  final color = option.color ?? AppColors.primary;

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: ListTile(
                      minTileHeight: 52,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      tileColor: isSelected ? AppColors.soft(AppColors.primary) : null,
                      leading: option.icon != null
                          ? Container(
                              width: 36,
                              height: 36,
                              decoration: BoxDecoration(color: AppColors.soft(color), borderRadius: BorderRadius.circular(10)),
                              child: Icon(option.icon, color: color, size: 20),
                            )
                          : null,
                      title: Text(
                        option.label,
                        style: TextStyle(
                          fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                          color: isSelected ? AppColors.primary : scheme.onSurface,
                        ),
                      ),
                      trailing: isSelected ? const Icon(Icons.check_circle_rounded, color: AppColors.primary) : null,
                      onTap: () => Navigator.of(context).pop(option.value),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
