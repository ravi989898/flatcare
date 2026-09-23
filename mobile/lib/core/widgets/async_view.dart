import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_exception.dart';
import '../theme/app_colors.dart';

/// Renders an AsyncValue<T> with a consistent loading/error/empty pattern
/// across every feature screen, so each screen only has to describe its
/// success state. Pass [skeleton] on list screens to show shimmer-free
/// placeholder cards instead of a bare spinner while loading.
class AsyncView<T> extends StatelessWidget {
  const AsyncView({
    super.key,
    required this.value,
    required this.builder,
    this.onRetry,
    this.skeleton = false,
  });

  final AsyncValue<T> value;
  final Widget Function(BuildContext context, T data) builder;
  final VoidCallback? onRetry;
  final bool skeleton;

  @override
  Widget build(BuildContext context) {
    return value.when(
      // Keep showing the last data during a refresh (e.g. after an add or
      // delete invalidates the list) instead of flashing a loader; a
      // dependency change (a new search) still shows the loading state.
      skipLoadingOnRefresh: true,
      data: (data) => builder(context, data),
      loading: () => skeleton
          ? const ListSkeleton()
          : const Center(
              child: Padding(
                padding: EdgeInsets.all(32),
                child: CircularProgressIndicator(),
              ),
            ),
      error: (error, stackTrace) => ErrorState(
        message: error is ApiException ? error.message : 'Something went wrong.',
        offline: error is ApiException && error.statusCode == null && error.message.startsWith("Can't reach"),
        onRetry: onRetry,
      ),
    );
  }
}

/// Makes a centered state widget scrollable at full height, so it still
/// works inside a RefreshIndicator (pull-to-refresh needs a scrollable).
class _ScrollableCenter extends StatelessWidget {
  const _ScrollableCenter({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight.isFinite ? constraints.maxHeight : 0),
          child: Center(child: child),
        ),
      ),
    );
  }
}

class _StateBody extends StatelessWidget {
  const _StateBody({required this.icon, required this.color, this.title, required this.message, this.action});

  final IconData icon;
  final Color color;
  final String? title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(color: AppColors.soft(color), shape: BoxShape.circle),
            child: Icon(icon, size: 42, color: color),
          ),
          const SizedBox(height: 18),
          if (title != null) ...[
            Text(
              title!,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: scheme.onSurface),
            ),
            const SizedBox(height: 6),
          ],
          Text(
            message,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14.5, color: scheme.onSurfaceVariant, height: 1.4),
          ),
          if (action != null) ...[const SizedBox(height: 20), action!],
        ],
      ),
    );
  }
}

class ErrorState extends StatelessWidget {
  const ErrorState({super.key, required this.message, this.onRetry, this.offline = false});

  final String message;
  final VoidCallback? onRetry;

  /// Swaps in a "no connection" icon and heading.
  final bool offline;

  @override
  Widget build(BuildContext context) {
    return _ScrollableCenter(
      child: _StateBody(
        icon: offline ? Icons.wifi_off_rounded : Icons.error_outline_rounded,
        color: offline ? AppColors.warning : AppColors.danger,
        title: offline ? "You're offline" : "Couldn't load this",
        message: message,
        action: onRetry == null
            ? null
            : FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Try again'),
              ),
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.message,
    this.icon = Icons.inbox_outlined,
    this.title,
    this.color = AppColors.primary,
    this.action,
  });

  final String message;
  final IconData icon;
  final String? title;
  final Color color;

  /// An optional call to action, e.g. "Add your first vehicle".
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return _ScrollableCenter(
      child: _StateBody(icon: icon, color: color, title: title, message: message, action: action),
    );
  }
}

/// Placeholder cards shown while a list loads.
class ListSkeleton extends StatelessWidget {
  const ListSkeleton({super.key, this.count = 6});

  final int count;

  @override
  Widget build(BuildContext context) {
    final block = Theme.of(context).colorScheme.outlineVariant.withValues(alpha: 0.55);

    Widget bar(double width, double height) => Container(
          width: width,
          height: height,
          decoration: BoxDecoration(color: block, borderRadius: BorderRadius.circular(6)),
        );

    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: count,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (context, index) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd + 2),
        ),
        child: Row(
          children: [
            Container(
              width: AppSpacing.iconBox,
              height: AppSpacing.iconBox,
              decoration: BoxDecoration(color: block, borderRadius: BorderRadius.circular(14)),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [bar(150, 14), const SizedBox(height: 8), bar(100, 12), const SizedBox(height: 8), bar(60, 18)],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
