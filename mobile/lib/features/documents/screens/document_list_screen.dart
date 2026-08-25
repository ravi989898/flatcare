import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../data/document.dart';
import '../providers/document_providers.dart';

const _categoryIcons = {
  'society': Icons.apartment_outlined,
  'maintenance_bill': Icons.receipt_long_outlined,
  'notice': Icons.campaign_outlined,
  'legal': Icons.gavel_outlined,
  'bylaw': Icons.menu_book_outlined,
};

class DocumentListScreen extends ConsumerWidget {
  const DocumentListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return DefaultTabController(
      length: AppDocument.categoryLabels.length + 1,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Documents'),
          bottom: TabBar(
            isScrollable: true,
            tabs: [
              const Tab(text: 'All'),
              for (final label in AppDocument.categoryLabels.values) Tab(text: label),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            const _DocumentCategoryList(category: null),
            for (final category in AppDocument.categoryLabels.keys) _DocumentCategoryList(category: category),
          ],
        ),
      ),
    );
  }
}

class _DocumentCategoryList extends ConsumerWidget {
  const _DocumentCategoryList({required this.category});

  final String? category;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final documents = ref.watch(documentListProvider(category));

    return RefreshIndicator(
      onRefresh: () => ref.refresh(documentListProvider(category).future),
      child: AsyncView<List<AppDocument>>(
        value: documents,
        onRetry: () => ref.invalidate(documentListProvider(category)),
        builder: (context, items) {
          if (items.isEmpty) {
            return const EmptyState(message: 'No documents in this category yet.', icon: Icons.folder_open_outlined);
          }

          return ListView.separated(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final doc = items[index];

              return Card(
                child: ListTile(
                  leading: Icon(_categoryIcons[doc.category] ?? Icons.description_outlined),
                  title: Text(doc.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text('${AppDocument.categoryLabels[doc.category] ?? doc.category} · ${doc.readableSize}'),
                  trailing: const Icon(Icons.download_outlined),
                  onTap: () => launchUrl(Uri.parse(doc.url), mode: LaunchMode.externalApplication),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
