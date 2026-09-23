import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/directory_entry.dart';
import '../data/directory_repository.dart';

final directorySearchProvider = StateProvider.autoDispose<String>((ref) => '');

/// Everything loaded so far for the current search, plus paging state.
class DirectoryState {
  const DirectoryState({required this.items, required this.page, required this.hasMore, this.loadingMore = false});

  final List<DirectoryEntry> items;
  final int page;
  final bool hasMore;
  final bool loadingMore;

  DirectoryState copyWith({List<DirectoryEntry>? items, int? page, bool? hasMore, bool? loadingMore}) => DirectoryState(
        items: items ?? this.items,
        page: page ?? this.page,
        hasMore: hasMore ?? this.hasMore,
        loadingMore: loadingMore ?? this.loadingMore,
      );
}

/// Loads the directory a page at a time — page 1 whenever the search text
/// changes, then [loadMore] appends the next page as the list scrolls.
class DirectoryController extends AutoDisposeAsyncNotifier<DirectoryState> {
  @override
  Future<DirectoryState> build() async {
    final search = ref.watch(directorySearchProvider);
    final page = await ref.read(directoryRepositoryProvider).list(search: search);
    return DirectoryState(items: page.items, page: page.page, hasMore: page.hasMore);
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.loadingMore) return;

    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await ref
          .read(directoryRepositoryProvider)
          .list(search: ref.read(directorySearchProvider), page: current.page + 1);
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
