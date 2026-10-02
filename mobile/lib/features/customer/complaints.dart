import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

Map<String, String> get complaintTypes => {
      'late': tr.cLate,
      'quality': tr.cQuality,
      'behavior': tr.cBehavior,
      'payment': tr.cPayment,
      'other': tr.cOther,
    };

class ComplaintsScreen extends StatelessWidget {
  const ComplaintsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.myComplaints)),
      body: Loader<List>(
        skeleton: true,
        load: () async => (await Api.I.get('/complaints'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: [EmptyState(tr.noComplaints, icon: Icons.sentiment_satisfied_alt_outlined)])
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final c = list[i];
                  return Appear(index: i, child: Card(
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
                  ));
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
  List<XFile> _files = [];
  Key _key = UniqueKey();

  Future<void> _send() async {
    final body = _msg.text.trim();
    if (body.isEmpty) return;
    if (await run(context, () => Api.I.postWithFiles('/complaints/${widget.id}/messages', {'body': body}, _files))) {
      _msg.clear();
      setState(() {
        _files = [];
        _key = UniqueKey();
      });
    }
  }

  Future<void> _attach() async {
    final picked = await ImagePicker().pickMultiImage(imageQuality: 75, maxWidth: 1920, limit: 5);
    if (picked.isNotEmpty) setState(() => _files = picked.take(5).toList());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.complaint)),
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
                    KV(tr.number, c['number'] ?? ''),
                    KV(tr.regarding, c['subject']?['number'] ?? ''),
                    const SizedBox(height: 6),
                    Text(c['description'] ?? ''),
                  ]),
                  const SizedBox(height: 12),
                  for (final m in msgs) _Bubble(m),
                  if (closed) Padding(padding: const EdgeInsets.all(12), child: Text(tr.complaintClosed, textAlign: TextAlign.center)),
                ],
              );
            },
          ),
        ),
        SafeArea(
          child: Container(
            color: AppColors.surface,
            padding: const EdgeInsets.fromLTRB(8, 8, 12, 8),
            child: Row(children: [
              IconButton(
                onPressed: _attach,
                icon: Badge(isLabelVisible: _files.isNotEmpty, label: Text('${_files.length}'), child: const Icon(Icons.attach_file)),
              ),
              Expanded(child: TextField(controller: _msg, decoration: InputDecoration(hintText: tr.writeReply))),
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
        padding: EdgeInsets.symmetric(vertical: 6),
        child: Text(
          '${tr.statusChangedTo(label(change?['to']))} · ${dateTimeLabel(m['at'])}',
          textAlign: TextAlign.center,
          style: TextStyle(color: AppColors.gray500, fontSize: 12.5),
        ),
      );
    }
    final mine = value(m['from']) == 'customer';
    final files = m['attachments_count'] as int? ?? 0;
    return Align(
      alignment: mine ? AlignmentDirectional.centerStart : AlignmentDirectional.centerEnd,
      child: Container(
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * .75),
        margin: EdgeInsets.symmetric(vertical: 4),
        padding: EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: mine ? AppColors.blue600 : AppColors.surface,
          border: mine ? null : Border.all(color: AppColors.gray200),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (!mine) Text(label(m['from']), style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: AppColors.blue700)),
          Text(m['body'] ?? '', style: TextStyle(color: mine ? Colors.white : AppColors.gray900)),
          if (files > 0)
            Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.attach_file, size: 14, color: mine ? AppColors.blue100 : AppColors.gray500),
              Text(tr.filesCount(files), style: TextStyle(fontSize: 11, color: mine ? AppColors.blue100 : AppColors.gray500)),
            ]),
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
  List<XFile> _files = [];
  bool _busy = false;

  Future<void> _submit() async {
    setState(() => _busy = true);
    final ok = await run(
      context,
      () => Api.I.postWithFiles('/complaints', {
        '${widget.subjectType}_id': widget.subjectId,
        'type': _type,
        'description': _desc.text.trim(),
      }, _files),
      success: tr.complaintSent,
    );
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.newComplaint)),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          if (widget.subjectNumber != null) Text(tr.regardingX(widget.subjectNumber!), style: TextStyle(color: AppColors.gray500)),
          const SizedBox(height: 12),
          Text(tr.problemType, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Wrap(spacing: 8, runSpacing: 8, children: [
            for (final e in complaintTypes.entries)
              ChoiceChip(label: Text(e.value), selected: _type == e.key, onSelected: (_) => setState(() => _type = e.key)),
          ]),
          const SizedBox(height: 16),
          TextField(controller: _desc, maxLines: 5, decoration: InputDecoration(labelText: tr.describeProblem)),
          const SizedBox(height: 16),
          AttachmentsPicker(files: _files, onChanged: (f) => setState(() => _files = f)),
          const SizedBox(height: 24),
          BusyButton(label: tr.sendComplaint, busy: _busy, onPressed: _type != null ? _submit : null),
        ],
      ),
    );
  }
}
