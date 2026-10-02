import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'complaints.dart';

class BookingDetailScreen extends StatelessWidget {
  const BookingDetailScreen({super.key, required this.id, this.justCreated = false});

  final int id;
  final bool justCreated;

  static const _cancellable = ['pending', 'confirmed', 'assigned'];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل الزيارة')),
      body: Loader<Map>(
        load: () async => (await Api.I.get('/bookings/$id'))['data'] as Map,
        builder: (context, b, reload) {
          final status = value(b['status']);
          final items = b['items'] as List? ?? [];
          final payment = b['payment'] as Map?;
          final rating = b['rating'] as Map?;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (justCreated)
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(color: AppColors.success50, borderRadius: BorderRadius.circular(12)),
                  child: const Row(children: [
                    Icon(Icons.check_circle, color: AppColors.success600),
                    SizedBox(width: 8),
                    Expanded(child: Text('تم استلام طلبك! سنؤكده ونُسند فريقاً قريباً.', style: TextStyle(color: AppColors.success600))),
                  ]),
                ),
              SectionCard(
                title: b['number'],
                trailing: StatusChip(b['status']),
                children: [
                  KV('الموعد', '${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}'),
                  KV('العنوان', addressLine(b['address'])),
                  KV('الفريق المنفذ', b['team']?['name'] ?? 'لم يُسند بعد'),
                  if (b['customer_notes'] != null) KV('ملاحظاتك', b['customer_notes']),
                  if (b['cancel_reason'] != null) KV('سبب الإلغاء', b['cancel_reason']),
                ],
              ),
              const SizedBox(height: 12),
              SectionCard(title: 'الخدمة والمبلغ', children: [
                for (final i in items) KV('${i['service']} — ${i['option']} × ${i['quantity']}', money(i['total'])),
                const Divider(),
                KV('الضريبة', money(b['tax'])),
                KV('الإجمالي', money(b['total']), bold: true),
                KV('طريقة الدفع', 'نقداً عند الإتمام'),
                if (payment != null) KV('حالة الدفع', label(payment['status'])),
              ]),
              const SizedBox(height: 12),
              SectionCard(title: 'التتبع', children: [Timeline(b['timeline'] as List? ?? [])]),
              if (rating != null) ...[
                const SizedBox(height: 12),
                SectionCard(title: 'تقييمك', children: [
                  KV('الخدمة', '★' * (rating['service_score'] as int? ?? 0)),
                  if (rating['worker_score'] != null) KV('الفريق', '★' * (rating['worker_score'] as int)),
                  if (rating['comment'] != null) Text(rating['comment']),
                ]),
              ],
              const SizedBox(height: 16),
              if (b['can_rate'] == true)
                FilledButton.icon(
                  onPressed: () async {
                    if (await showRatingSheet(context, path: '/bookings/$id/rating', workerLabel: 'تقييم الفريق')) reload();
                  },
                  icon: const Icon(Icons.star_outline),
                  label: const Text('قيّم الزيارة'),
                ),
              if (_cancellable.contains(status)) ...[
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    final reason = await promptText(context, 'إلغاء الزيارة', hint: 'سبب الإلغاء (اختياري)');
                    if (reason == null || !context.mounted) return;
                    if (await run(context, () => Api.I.post('/bookings/$id/cancel', {'reason': reason}), success: 'تم إلغاء الزيارة')) reload();
                  },
                  icon: const Icon(Icons.close),
                  label: const Text('إلغاء الزيارة'),
                ),
              ],
              const SizedBox(height: 10),
              TextButton.icon(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => ComplaintFormScreen(subjectType: 'booking', subjectId: id, subjectNumber: b['number'])),
                ),
                icon: const Icon(Icons.report_outlined),
                label: const Text('لديك مشكلة؟ قدّم شكوى'),
              ),
            ],
          );
        },
      ),
    );
  }
}

/// نافذة التقييم (زيارة أو عاملة عقد). ترجع true عند الإرسال.
Future<bool> showRatingSheet(BuildContext context, {required String path, required String workerLabel, Map<String, dynamic> extra = const {}}) async {
  var service = 5;
  var worker = 5;
  final comment = TextEditingController();
  final sent = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    builder: (c) => StatefulBuilder(
      builder: (c, set) => Padding(
        padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + MediaQuery.of(c).viewInsets.bottom),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          const Text('تقييم الخدمة', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700)),
          StarsInput(value: service, onChanged: (v) => set(() => service = v)),
          Text(workerLabel, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700)),
          StarsInput(value: worker, onChanged: (v) => set(() => worker = v)),
          const SizedBox(height: 8),
          TextField(controller: comment, maxLines: 2, decoration: const InputDecoration(hintText: 'تعليق (اختياري)')),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () async {
              final ok = await run(c, () => Api.I.post(path, {
                    ...extra,
                    'service_score': service,
                    'worker_score': worker,
                    if (comment.text.trim().isNotEmpty) 'comment': comment.text.trim(),
                  }));
              if (ok && c.mounted) Navigator.pop(c, true);
            },
            child: const Text('إرسال التقييم'),
          ),
        ]),
      ),
    ),
  );
  if (sent == true && context.mounted) toast(context, 'شكراً لتقييمك!');
  return sent == true;
}
