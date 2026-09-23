import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/directory_entry.dart';
import '../data/directory_repository.dart';

final directorySearchProvider = StateProvider.autoDispose<String>((ref) => '');

/// `owner` / `tenant` / `occupant`, or null for everyone.
final directoryTypeProvider = StateProvider.autoDispose<String?>((ref) => null);

/// Everything loaded so far for the current search, plus paging state.
class DirectoryState {
  const DirectoryState({required this.items, required this.page, required this.hasMore, this.total, this.loadingMore = false});

  final List<DirectoryEntry> items;
  final int page;
  final bool hasMore;
  final int? total;
  final bool loadingMore;

  DirectoryState copyWith({List<DirectoryEntry>? items, int? page, bool? hasMore, bool? loadingMore}) => DirectoryState(
        items: items ?? this.items,
        page: page ?? this.page,
        hasMore: hasMore ?? this.hasMore,
        total: total,
        loadingMore: loadingMore ?? this.loadingMore,
      );
}

/// Loads the directory a page at a time — page 1 whenever the search text
/// or type filter changes, then [loadMore] appends the next page as the
/// list scrolls.
class DirectoryController extends AutoDisposeAsyncNotifier<DirectoryState> {
  Future<DirectoryPage> _fetch(int page) => ref.read(directoryRepositoryProvider).list(
        search: ref.read(directorySearchProvider),
        residentType: ref.read(directoryTypeProvider),
        page: page,
      );

  @override
  Future<DirectoryState> build() async {
    ref.watch(directorySearchProvider);
    ref.watch(directoryTypeProvider);
    final page = await _fetch(1);
    return DirectoryState(items: page.items, page: page.page, hasMore: page.hasMore, total: page.total);
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.loadingMore) return;

    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await _fetch(current.page + 1);
      state = AsyncData(current.copyWith(
        items: [...current.items, ...next.items],
        page: next.page,
        hasMore: next.hasMore,
        loadingMore: false,
      ));
    } catch (_) {
      // Keep what's already shown; scrolling to the end again retries.
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }
}

final directoryListProvider = AsyncNotifierProvider.autoDispose<DirectoryController, DirectoryState>(DirectoryController.new);
