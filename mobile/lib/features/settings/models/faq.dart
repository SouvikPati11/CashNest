import 'package:flutter/foundation.dart';

/// A single FAQ question/answer pair.
@immutable
class FaqItem {
  const FaqItem({required this.question, required this.answer});

  final String question;
  final String answer;

  factory FaqItem.fromJson(Map<String, dynamic> json) {
    return FaqItem(
      question: json['question'] as String? ?? '',
      answer: json['answer'] as String? ?? '',
    );
  }
}

/// FAQ entries grouped by category (`GET /v1/support/faqs`).
@immutable
class FaqCategory {
  const FaqCategory({required this.category, required this.items});

  final String category;
  final List<FaqItem> items;

  factory FaqCategory.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'];
    final items = rawItems is List
        ? rawItems.whereType<Map<String, dynamic>>().map(FaqItem.fromJson).toList(growable: false)
        : const <FaqItem>[];
    return FaqCategory(category: json['category'] as String? ?? '', items: items);
  }
}
