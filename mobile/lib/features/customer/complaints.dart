import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

const complaintTypes = {
  'late': 'تأخر عن الموعد',
  'quality': 'جودة الخدمة',
  'behavior': 'سلوك',
  'payment': 'مشكلة في الدفع',
  'other': 'أخرى',
};

class ComplaintsScreen extends StatelessWidget {
  const ComplaintsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('شكاواي')),
      body: Loader<List>(
        load: () async => (await Api.I.get('/complaints'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: const [
                EmptyState('لا توجد شكاوى. يمكنك تقديم شكوى من صفحة الزيارة أو العقد.', icon: Icons.sentiment_satisfied_alt_outlined),
              ])
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final c = list[i];
                  return Card(
                    child: ListTile(
                      title: Text(c['type_label'] ?? ''),
                      subtitle: Text('${c['number']} · ${c['subject']?['number'] ?? ''}\n${c['description'] ?? ''}', maxLines: 3, overflow: TextOverflow.ellipsis),
                      isThreeLine: true,
                      trailing: StatusChip(c['status']),
                      onTap: () async {
                        await Navigator.push(context, MaterialPageRoute(builder: (_) => ComplaintDetailScreen(id: c['id'] as int)));
                        reload();
                      },
                    ),
                  );
                },
              ),
      ),
    );
  }
}

class ComplaintDetailScreen extends StatefulWidget {
  const ComplaintDetailScreen({super.key, required this.id});

  final int id;

  @override
  State<ComplaintDetailScreen> createState() => _ComplaintDetailScreenState();
}

class _ComplaintDetailScreenState extends State<ComplaintDetailScreen> {
  final _msg = TextEditingController();
  Key _key = UniqueKey();

  Future<void> _send() async {
    final body = _msg.text.trim();
    if (body.isEmpty) return;
    if (await run(context, () => Api.I.post('/complaints/${widget.id}/messages', {'body': body}))) {
      _msg.clear();
      setState(() => _key = UniqueKey());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الشكوى')),
      body: Column(children: [
        Expanded(
          child: Loader<Map>(
            key: _key,
            load: () async => (await Api.I.get('/complaints/${widget.id}'))['data'] as Map,
            builder: (context, c, reload) {
              final msgs = c['messages'] as List? ?? [];
              final closed = value(c['status']) == 'closed';
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  SectionCard(title: c['type_label'], trailing: StatusChip(c['status']), children: [
                    KV('الرقم', c['number'] ?? ''),
                    KV('بخصوص', c['subject']?['number'] ?? ''),
                    const SizedBox(height: 6),
                    Text(c['description'] ?? ''),
                  ]),
                  const SizedBox(height: 12),
                  for (final m in msgs) _Bubble(m),
                  if (closed) const Padding(padding: EdgeInsets.all(12), child: Text('الشكوى مغلقة.', textAlign: TextAlign.center)),
                ],
              );
            },
          ),
        ),
        SafeArea(
          child: Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
            child: Row(children: [
              Expanded(child: TextField(controller: _msg, decoration: const InputDecoration(hintText: 'اكتب ردك…'))),
              const SizedBox(width: 8),
              IconButton.filled(onPressed: _send, icon: const Icon(Icons.send)),
            ]),
          ),
        ),
      ]),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble(this.m);

  final Map m;

  @override
  Widget build(BuildContext context) {
    final change = m['status_change'] as Map?;
    if (change != null || value(m['kind']) == 'status_change') {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Text(
          'تغيّرت الحالة إلى "${label(change?['to'])}" · ${dateTimeLabel(m['at'])}',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.gray500, fontSize: 12.5),
        ),
      );
    }
    final mine = value(m['from']) == 'customer';
    return Align(
      alignment: mine ? AlignmentDirectional.centerStart : AlignmentDirectional.centerEnd,
      child: Container(
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * .75),
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: mine ? AppColors.blue600 : Colors.white,
          border: mine ? null : Border.all(color: AppColors.gray200),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (!mine) Text(label(m['from']), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: AppColors.blue700)),
          Text(m['body'] ?? '', style: TextStyle(color: mine ? Colors.white : AppColors.gray900)),
          Text(dateTimeLabel(m['at']), style: TextStyle(fontSize: 11, color: mine ? AppColors.blue100 : AppColors.gray500)),
        ]),
      ),
    );
  }
}

class ComplaintFormScreen extends StatefulWidget {
  const ComplaintFormScreen({super.key, required this.subjectType, required this.subjectId, this.subjectNumber});

  final String subjectType; // booking | contract
  final int subjectId;
  final String? subjectNumber;

  @override
  State<ComplaintFormScreen> createState() => _ComplaintFormScreenState();
}

class _ComplaintFormScreenState extends State<ComplaintFormScreen> {
  final _desc = TextEditingController();
  String? _type;
  bool _busy = false;

  Future<void> _submit() async {
    setState(() => _busy = true);
    final ok = await run(
      context,
      () => Api.I.post('/complaints', {
        '${widget.subjectType}_id': widget.subjectId,
        'type': _type,
        'description': _desc.text.trim(),
      }),
      success: 'تم إرسال الشكوى — سنتواصل معك قريباً',
    );
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('شكوى جديدة')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (widget.subjectNumber != null) Text('بخصوص: ${widget.subjectNumber}', style: const TextStyle(color: AppColors.gray500)),
          const SizedBox(height: 12),
          const Text('نوع المشكلة', style: TextStyle(fontWeight: FontWeight.w700)),
          Wrap(spacing: 8, runSpacing: 8, children: [
            for (final e in complaintTypes.entries)
              ChoiceChip(label: Text(e.value), selected: _type == e.key, onSelected: (_) => setState(() => _type = e.key)),
          ]),
          const SizedBox(height: 16),
          TextField(controller: _desc, maxLines: 5, decoration: const InputDecoration(labelText: 'اشرح المشكلة')),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _type != null && !_busy ? _submit : null,
            child: const Text('إرسال الشكوى'),
          ),
        ],
      ),
    );
  }
}
