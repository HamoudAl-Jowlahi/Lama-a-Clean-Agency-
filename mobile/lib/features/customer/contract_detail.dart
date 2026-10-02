import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'booking_detail.dart';
import 'complaints.dart';

Map<String, String> get changeReasons => {
      'frequent_delay': tr.reasonDelay,
      'quality': tr.reasonQuality,
      'absence': tr.reasonAbsence,
      'behavior': tr.reasonBehavior,
      'no_longer_needed': tr.reasonNotNeeded,
      'other': tr.reasonOther,
    };

class ContractDetailScreen extends StatelessWidget {
  const ContractDetailScreen({super.key, required this.id, this.justCreated = false});

  final int id;
  final bool justCreated;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.contractDetails)),
      body: Loader<Map>(
        load: () async => (await Api.I.get('/contracts/$id'))['data'] as Map,
        builder: (context, c, reload) {
          final status = value(c['status']);
          final worker = c['current_worker'] as Map?;
          final progress = c['progress'] as Map?;
          final payments = c['payments'] as List? ?? [];
          final requests = c['change_requests'] as List? ?? [];
          final history = c['workers_history'] as List? ?? [];
          final openRequest = requests.any((r) => ['open', 'under_review'].contains(value(r['status'])));
          final running = ['assigned', 'active'].contains(status);
          final ended = ['completed', 'terminated'].contains(status);

          Future<void> openForm(String type) async {
            final sent = await Navigator.push<bool>(
              context,
              MaterialPageRoute(builder: (_) => ChangeRequestScreen(contractId: id, type: type)),
            );
            if (sent == true) reload();
          }

          return ListView(
            padding: EdgeInsets.all(16),
            children: [
              if (justCreated) SuccessBanner(tr.contractReceived),
              SectionCard(title: c['number'], trailing: StatusChip(c['status']), children: [
                KV(tr.plan, c['plan']?['name'] ?? '—'),
                KV(tr.schedule, tr.scheduleValue('${c['plan']?['work_days_per_week']}', '${c['plan']?['hours_per_day']}')),
                KV(tr.from, dayLabel(c['start_date'])),
                KV(tr.to, dayLabel(c['end_date'])),
                KV(tr.address, addressLine(c['address'])),
                if (c['terminated_at'] != null) KV(tr.terminatedOn, dayLabel(c['terminated_at'])),
                if (progress != null) ...[
                  SizedBox(height: 8),
                  LinearProgressIndicator(value: (progress['day'] as int) / (progress['total_days'] as int), minHeight: 8, borderRadius: BorderRadius.circular(4)),
                  SizedBox(height: 4),
                  Text(tr.dayOf(progress['day'] as int, progress['total_days'] as int), style: TextStyle(color: AppColors.gray500, fontSize: 12.5)),
                ],
              ]),
              SizedBox(height: 12),
              SectionCard(title: tr.housekeeper, children: [
                if (worker == null) Text(tr.noWorkerYet, style: TextStyle(color: AppColors.gray500)),
                if (worker != null)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: CircleAvatar(backgroundColor: AppColors.blue50, child: Icon(Icons.person, color: AppColors.blue600)),
                    title: Text(worker['name'] ?? ''),
                    subtitle: Text(tr.since(dayLabel(worker['since']))),
                  ),
                if (history.length > 1) ...[
                  Divider(),
                  for (final h in history)
                    KV(h['name'] ?? '', '${h['from']} $arrow ${h['to'] ?? tr.now}${h['end_reason'] != null ? ' (${label(h['end_reason'])})' : ''}'),
                ],
              ]),
              SizedBox(height: 12),
              SectionCard(title: tr.amountsCash, children: [
                KV(tr.monthly, money(c['monthly_price'])),
                KV(tr.total, money(c['total_amount']), bold: true),
                for (final p in payments)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    dense: true,
                    title: Text(money(p['amount'])),
                    subtitle: Text(p['period_start'] != null ? '${p['period_start']} $arrow ${p['period_end']}' : tr.dueOn(p['due_date'] ?? '')),
                    trailing: StatusChip(p['status']),
                  ),
              ]),
              if (requests.isNotEmpty) ...[
                SizedBox(height: 12),
                SectionCard(title: tr.changeRequests, children: [
                  for (final r in requests)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text('${label(r['type'])} — ${changeReasons[r['reason_type']] ?? ''}'),
                      subtitle: Text([r['number'], if (r['admin_response'] != null) tr.adminReply(r['admin_response'])].join('\n')),
                      trailing: StatusChip(r['status']),
                    ),
                ]),
              ],
              SizedBox(height: 12),
              SectionCard(title: tr.tracking, children: [Timeline(c['timeline'] as List? ?? [])]),
              SizedBox(height: 16),
              if (running && !openRequest) ...[
                FilledButton.icon(onPressed: () => openForm('replace_worker'), icon: Icon(Icons.swap_horiz), label: Text(tr.requestReplace)),
                SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () => openForm('terminate'),
                  icon: Icon(Icons.logout),
                  label: Text(tr.requestTerminate),
                ),
              ],
              if (running && openRequest)
                Text(tr.requestPending, textAlign: TextAlign.center, style: TextStyle(color: AppColors.warning600)),
              if (['pending', 'confirmed', 'assigned'].contains(status)) ...[
                SizedBox(height: 10),
                TextButton(
                  style: TextButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    if (!await confirm(context, tr.cancelContract, tr.cancelContractQ, ok: tr.cancelContract, danger: true)) return;
                    if (!context.mounted) return;
                    if (await run(context, () => Api.I.post('/contracts/$id/cancel', {}), success: tr.contractCancelled)) reload();
                  },
                  child: Text(tr.cancelBeforeStart),
                ),
              ],
              if (ended)
                for (final h in history)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: OutlinedButton.icon(
                      onPressed: () => showRatingSheet(context, path: '/contracts/$id/rating', workerLabel: tr.rateName(h['name'] ?? ''), extra: {'worker_id': h['worker_id']}),
                      icon: const Icon(Icons.star_outline),
                      label: Text(tr.rateName(h['name'] ?? '')),
                    ),
                  ),
              TextButton.icon(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => ComplaintFormScreen(subjectType: 'contract', subjectId: id, subjectNumber: c['number'])),
                ),
                icon: const Icon(Icons.report_outlined),
                label: Text(tr.fileComplaint),
              ),
            ],
          );
        },
      ),
    );
  }
}

/// طلب استبدال العاملة أو إنهاء العقد (CR-2) — مع صور اختيارية.
class ChangeRequestScreen extends StatefulWidget {
  const ChangeRequestScreen({super.key, required this.contractId, required this.type});

  final int contractId;
  final String type;

  @override
  State<ChangeRequestScreen> createState() => _ChangeRequestScreenState();
}

class _ChangeRequestScreenState extends State<ChangeRequestScreen> {
  final _details = TextEditingController();
  String? _reason;
  DateTime _date = DateUtils.dateOnly(DateTime.now()).add(const Duration(days: 1));
  List<XFile> _files = [];
  bool _busy = false;

  bool get _terminate => widget.type == 'terminate';

  Future<void> _submit() async {
    setState(() => _busy = true);
    final ok = await run(
      context,
      () => Api.I.postWithFiles('/contracts/${widget.contractId}/change-requests', {
        'type': widget.type,
        'reason_type': _reason,
        if (_details.text.trim().isNotEmpty) 'details': _details.text.trim(),
        if (_terminate) 'requested_date': ymd(_date),
      }, _files),
      success: tr.requestSent,
    );
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_terminate ? tr.requestTerminate : tr.requestReplace)),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          Text(_terminate ? tr.terminateNote : tr.replaceNote, style: TextStyle(color: AppColors.gray500)),
          const SizedBox(height: 16),
          Text(tr.reason, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Wrap(spacing: 8, runSpacing: 8, children: [
            for (final e in changeReasons.entries)
              if (_terminate || e.key != 'no_longer_needed')
                ChoiceChip(label: Text(e.value), selected: _reason == e.key, onSelected: (_) => setState(() => _reason = e.key)),
          ]),
          const SizedBox(height: 16),
          TextField(controller: _details, maxLines: 4, decoration: InputDecoration(labelText: tr.detailsOptional)),
          const SizedBox(height: 16),
          AttachmentsPicker(files: _files, onChanged: (f) => setState(() => _files = f)),
          if (_terminate) ...[
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () async {
                final today = DateUtils.dateOnly(DateTime.now());
                final d = await showDatePicker(context: context, initialDate: _date, firstDate: today, lastDate: today.add(const Duration(days: 365)));
                if (d != null) setState(() => _date = d);
              },
              icon: const Icon(Icons.event),
              label: Text(tr.requestedEndDate(dayLabel(ymd(_date)))),
            ),
          ],
          const SizedBox(height: 24),
          BusyButton(label: tr.sendRequest, busy: _busy, onPressed: _reason != null ? _submit : null),
        ],
      ),
    );
  }
}
