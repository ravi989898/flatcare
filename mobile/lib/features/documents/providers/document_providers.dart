import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/document.dart';
import '../data/document_repository.dart';

final documentListProvider = FutureProvider.autoDispose.family<List<AppDocument>, String?>((ref, category) {
  return ref.watch(documentRepositoryProvider).list(category: category);
});
