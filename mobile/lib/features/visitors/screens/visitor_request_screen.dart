import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// A guard-raised visitor request in full — photo, name, mobile, type,
/// purpose, gate keeper, request time, flat and current status — with
/// Accept / Reject while it is still PENDING. Opened by tapping a push
/// notification or the request in My Visitors. Reloads itself when a push
/// arrives (see PushService, which invalidates visitorRequestProvider), so
/// if another device answers first the screen flips to the final status and
/// the buttons disappear.
class VisitorRequestScreen extends ConsumerStatefulWidget {
  const VisitorRequestScreen({super.key, required this.id});

  final int id;

  @override
  ConsumerState<VisitorRequestScreen> createState() => _VisitorRequestScreenState();
}

class _VisitorRequestScreenState extends ConsumerState<VisitorRequestScreen> {
  bool _busy = false;

  Future<void> _respond(bool approve) async {
    setState(() => _busy = true);
    try {
      final repository = ref.read(visitorRepositoryProvider);
      approve ? await repository.approve(widget.id) : await repository.reject(widget.id);
      if (mounted) showVisitorSnack(context, approve ? 'Visitor approved' : 'Visitor rejected');
    } on ApiException catch (e) {
      // Typically a 409: someone else already answered. Show why, then the current status.
      if (mounted) showVisitorSnack(context, e.message, error: true);
    } finally {
      ref.invalidate(visitorRequestProvider(widget.id));
      ref.invalidate(visitorListProvider);
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final request = ref.watch(visitorRequestProvider(widget.id));
    final visitor = request.valueOrNull;

    return VisitorScaffold(
      title: 'Visitor Request',
      bottom: visitor != null && visitor.canRespond ? _buildActions() : null,
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(visitorRequestProvider(widget.id).future),
        child: AsyncView<Visitor>(
          value: request,
          onRetry: () => ref.invalidate(visitorRequestProvider(widget.id)),
          builder: (context, visitor) => _buildDetails(visitor),
        ),
      ),
    );
  }

  Widget _buildActions() {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton(
            style: OutlinedButton.styleFrom(
              foregroundColor: VisitorColors.error,
              side: const BorderSide(color: VisitorColors.error),
              minimumSize: const Size.fromHeight(50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            onPressed: _busy ? null : () => _respond(false),
            child: const Text('Reject', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: VisitorColors.success,
              minimumSize: const Size.fromHeight(50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            onPressed: _busy ? null : () => _respond(true),
            child: _busy
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('Accept', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ),
      ],
    );
  }

  Widget _buildDetails(Visitor visitor) {
    final flat = visitor.flat;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        VisitorCardShell(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName),
              const SizedBox(height: 12),
              Text(
                visitor.visitorName,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: VisitorColors.text),
              ),
              const SizedBox(height: 8),
              VisitorStatusPill(visitor: visitor),
              if (visitor.canRespond) ...[
                const SizedBox(height: 10),
                const Text(
                  'is waiting at the gate. Please approve or reject.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: VisitorColors.muted, fontSize: 13),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 14),
        VisitorCardShell(
          child: Column(
            children: [
              _row('Mobile number', visitor.visitorPhone),
              _row('Visitor type', purposeLabel(visitor.purpose)),
              _row('Purpose', visitor.notes),
              _row('Gate keeper', visitor.gateKeeperName),
              _row('Requested at', visitor.createdAt == null ? null : formatDateTime(visitor.createdAt)),
              _row('Flat / house', flat?.displayLabel),
              _row('Vehicle', visitor.vehicleNumber),
              _row('Approved at', visitor.approvedAt == null ? null : formatDateTime(visitor.approvedAt)),
              _row('Rejected at', visitor.rejectedAt == null ? null : formatDateTime(visitor.rejectedAt)),
              _row('Entered at', visitor.checkInAt == null ? null : formatDateTime(visitor.checkInAt)),
              _row('Exited at', visitor.checkOutAt == null ? null : formatDateTime(visitor.checkOutAt)),
            ],
          ),
        ),
      ],
    );
  }

  /// A label/value line; skipped entirely when there is no value.
  Widget _row(String label, String? value) {
    if (value == null || value.trim().isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 118, child: Text(label, style: const TextStyle(color: VisitorColors.muted, fontSize: 13))),
          Expanded(
            child: Text(value, style: const TextStyle(color: VisitorColors.text, fontSize: 14, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}
