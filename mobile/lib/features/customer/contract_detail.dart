import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'booking_detail.dart';
import 'complaints.dart';

const changeReasons = {
  'frequent_delay': 'تأخر متكرر',
  'quality': 'جودة العمل',
  'absence': 'غياب',
  'behavior': 'سلوك',
  'no_longer_needed': 'لم أعد بحاجة للخدمة',
  'other': 'سبب آخر',
};

class ContractDetailScreen extends StatelessWidget {
  const ContractDetailScreen({super.key, required this.id, this.justCreated = false});

  final int id;
  final bool justCreated;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل العقد')),
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
            padding: const EdgeInsets.all(16),
            children: [
              if (justCreated)
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(color: AppColors.success50, borderRadius: BorderRadius.circular(12)),
                  child: const Text('تم إرسال طلب العقد. ستراجعه الإدارة وتعيّن لك عاملة.', style: TextStyle(color: AppColors.success600)),
                ),
              SectionCard(title: c['number'], trailing: StatusChip(c['status']), children: [
                KV('الباقة', c['plan']?['name'] ?? '—'),
                KV('الدوام', '${c['plan']?['work_days_per_week']} أيام × ${c['plan']?['hours_per_day']} ساعات'),
                KV('من', dayLabel(c['start_date'])),
                KV('إلى', dayLabel(c['end_date'])),
                KV('العنوان', addressLine(c['address'])),
                if (c['terminated_at'] != null) KV('أُنهي في', dayLabel(c['terminated_at'])),
                if (progress != null) ...[
                  const SizedBox(height: 8),
                  LinearProgressIndicator(value: (progress['day'] as int) / (progress['total_days'] as int), minHeight: 8, borderRadius: BorderRadius.circular(4)),
                  const SizedBox(height: 4),
                  Text('اليوم ${progress['day']} من ${progress['total_days']}', style: const TextStyle(color: AppColors.gray500, fontSize: 12.5)),
                ],
              ]),
              const SizedBox(height: 12),
              SectionCard(title: 'العاملة', children: [
                if (worker == null) const Text('لم تُعيَّن عاملة بعد.', style: TextStyle(color: AppColors.gray500)),
                if (worker != null)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const CircleAvatar(backgroundColor: AppColors.blue50, child: Icon(Icons.person, color: AppColors.blue600)),
                    title: Text(worker['name'] ?? ''),
                    subtitle: Text('منذ ${dayLabel(worker['since'])}'),
                  ),
                if (history.length > 1) ...[
                  const Divider(),
                  for (final h in history)
                    KV(h['name'] ?? '', '${h['from']} ← ${h['to'] ?? 'الآن'}${h['end_reason'] != null ? ' (${label(h['end_reason'])})' : ''}'),
                ],
              ]),
              const SizedBox(height: 12),
              SectionCard(title: 'المبالغ (نقداً)', children: [
                KV('الشهري', money(c['monthly_price'])),
                KV('الإجمالي', money(c['total_amount']), bold: true),
                for (final p in payments)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    dense: true,
                    title: Text(money(p['amount'])),
                    subtitle: Text(p['period_start'] != null ? '${p['period_start']} ← ${p['period_end']}' : 'استحقاق ${p['due_date'] ?? ''}'),
                    trailing: StatusChip(p['status']),
                  ),
              ]),
              if (requests.isNotEmpty) ...[
                const SizedBox(height: 12),
                SectionCard(title: 'طلبات الاستبدال والإنهاء', children: [
                  for (final r in requests)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text('${label(r['type'])} — ${changeReasons[r['reason_type']] ?? ''}'),
                      subtitle: Text([r['number'], if (r['admin_response'] != null) 'رد الإدارة: ${r['admin_response']}'].join('\n')),
                      trailing: StatusChip(r['status']),
                    ),
                ]),
              ],
              const SizedBox(height: 12),
              SectionCard(title: 'التتبع', children: [Timeline(c['timeline'] as List? ?? [])]),
              const SizedBox(height: 16),
              if (running && !openRequest) ...[
                FilledButton.icon(onPressed: () => openForm('replace_worker'), icon: const Icon(Icons.swap_horiz), label: const Text('طلب استبدال العاملة')),
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () => openForm('terminate'),
                  icon: const Icon(Icons.logout),
                  label: const Text('طلب إنهاء العقد'),
                ),
              ],
              if (running && openRequest)
                const Text('لديك طلب قيد المراجعة لدى الإدارة.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.warning600)),
              if (['pending', 'confirmed', 'assigned'].contains(status)) ...[
                const SizedBox(height: 10),
                TextButton(
                  style: TextButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    if (!await confirm(context, 'إلغاء العقد', 'سيُلغى العقد قبل بدئه. هل أنت متأكد؟', ok: 'إلغاء العقد', danger: true)) return;
                    if (!context.mounted) return;
                    if (await run(context, () => Api.I.post('/contracts/$id/cancel', {}), success: 'تم إلغاء العقد')) reload();
                  },
                  child: const Text('إلغاء العقد قبل البدء'),
                ),
              ],
              if (ended)
                for (final h in history)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: OutlinedButton.icon(
                      onPressed: () => showRatingSheet(context, path: '/contracts/$id/rating', workerLabel: 'تقييم ${h['name']}', extra: {'worker_id': h['worker_id']}),
                      icon: const Icon(Icons.star_outline),
                      label: Text('قيّم ${h['name']}'),
                    ),
                  ),
              TextButton.icon(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => ComplaintFormScreen(subjectType: 'contract', subjectId: id, subjectNumber: c['number'])),
                ),
                icon: const Icon(Icons.report_outlined),
                label: const Text('قدّم شكوى'),
              ),
            ],
          );
        },
      ),
    );
  }
}

/// طلب استبدال العاملة أو إنهاء العقد (CR-2).
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
  bool _busy = false;

  bool get _terminate => widget.type == 'terminate';

  Future<void> _submit() async {
    setState(() => _busy = true);
    final ok = await run(
      context,
      () => Api.I.post('/contracts/${widget.contractId}/change-requests', {
        'type': widget.type,
        'reason_type': _reason,
        if (_details.text.trim().isNotEmpty) 'details': _details.text.trim(),
        if (_terminate) 'requested_date': ymd(_date),
      }),
      success: 'تم إرسال طلبك للإدارة',
    );
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_terminate ? 'طلب إنهاء العقد' : 'طلب استبدال العاملة')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            _terminate
                ? 'تراجع الإدارة الطلب، ويُحتسب المبلغ حتى آخر يوم عمل فعلي.'
                : 'تراجع الإدارة الطلب وتعيّن عاملة بديلة، ولا تُحتسب أيام الانتظار.',
            style: const TextStyle(color: AppColors.gray500),
          ),
          const SizedBox(height: 16),
          const Text('السبب', style: TextStyle(fontWeight: FontWeight.w700)),
          Wrap(spacing: 8, runSpacing: 8, children: [
            for (final e in changeReasons.entries)
              if (_terminate || e.key != 'no_longer_needed')
                ChoiceChip(label: Text(e.value), selected: _reason == e.key, onSelected: (_) => setState(() => _reason = e.key)),
          ]),
          const SizedBox(height: 16),
          TextField(controller: _details, maxLines: 4, decoration: const InputDecoration(labelText: 'التفاصيل (اختياري)')),
          if (_terminate) ...[
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () async {
                final today = DateUtils.dateOnly(DateTime.now());
                final d = await showDatePicker(context: context, initialDate: _date, firstDate: today, lastDate: today.add(const Duration(days: 365)));
                if (d != null) setState(() => _date = d);
              },
              icon: const Icon(Icons.event),
              label: Text('تاريخ الإنهاء المطلوب: ${dayLabel(ymd(_date))}'),
            ),
          ],
          const SizedBox(height: 24),
          FilledButton(onPressed: _reason != null && !_busy ? _submit : null, child: const Text('إرسال الطلب')),
        ],
      ),
    );
  }
}
