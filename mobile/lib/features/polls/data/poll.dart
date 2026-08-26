class PollOption {
  PollOption({required this.id, required this.label, required this.votesCount, required this.percentage});

  factory PollOption.fromJson(Map<String, dynamic> json) {
    return PollOption(
      id: json['id'] as int,
      label: json['label'] as String,
      votesCount: json['votes_count'] as int,
      percentage: (json['percentage'] as num).toDouble(),
    );
  }

  final int id;
  final String label;
  final int votesCount;
  final double percentage;
}

class Poll {
  Poll({
    required this.id,
    required this.question,
    this.description,
    required this.status,
    this.closesAt,
    required this.totalVotes,
    this.myOptionId,
    required this.options,
  });

  factory Poll.fromJson(Map<String, dynamic> json) {
    return Poll(
      id: json['id'] as int,
      question: json['question'] as String,
      description: json['description'] as String?,
      status: json['status'] as String,
      closesAt: json['closes_at'] as String?,
      totalVotes: json['total_votes'] as int,
      myOptionId: json['my_option_id'] as int?,
      options: (json['options'] as List).map((o) => PollOption.fromJson(o as Map<String, dynamic>)).toList(),
    );
  }

  final int id;
  final String question;
  final String? description;
  final String status;
  final String? closesAt;
  final int totalVotes;
  final int? myOptionId;
  final List<PollOption> options;

  bool get hasVoted => myOptionId != null;
  bool get isOpen => status == 'open';
}
