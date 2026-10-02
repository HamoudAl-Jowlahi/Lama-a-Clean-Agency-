import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// زيارة الفريق. الإجراءات (قبول/رفض/تحديث الحالة) تظهر للقائد فقط — والخادم يفرض ذلك أيضاً.
class WorkerBookingDetailScreen extends StatelessWidget {
  const WorkerBookingDetailScreen({super.key, required this.id});

  final int id;

  static const _icons = {
    'on_the_way': Icons.directions_car_outlined,
    'in_progress': Icons.cleaning_services_outlined,
    'completed': Icons.task_alt,
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل الزيارة')),
      body: Loader<Map>(
        load: () async => (await Api.I.get('/worker/bookings/$id'))['data'] as Map,
        builder: (context, b, reload) {
          final leader = b['is_leader'] == true;
          final pending = value(b['assignment_status']) == 'pending';
          final next = b['next_statuses'] as List? ?? [];
          final items = b['items'] as List? ?? [];
          final phone = b['customer']?['phone'] as String?;
          final collect = b['amount_to_collect'];

          Future<void> act(Future<void> Function() call, String ok) async {
            if (await run(context, call, success: ok)) reload();
          }

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              SectionCard(title: b['number'], trailing: StatusChip(b['status']), children: [
                KV('الفريق', b['team']?['name'] ?? '—'),
                KV('الإسناد', label(b['assignment_status'])),
                KV('الموعد', '${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}'),
                for (final i in items) KV(i['service'] ?? '', '${i['option']} × ${i['quantity']}'),
              ]),
              const SizedBox(height: 12),
              SectionCard(title: 'العميل والموقع', children: [
                KV('العميل', b['customer']?['name'] ?? '—'),
                KV('العنوان', addressLine(b['address'])),
                if (b['address']?['details'] != null) KV('وصف', b['address']['details']),
                if (b['customer_notes'] != null) KV('ملاحظات العميل', b['customer_notes']),
                if (phone != null) ...[
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: () => launchUrl(Uri(scheme: 'tel', path: phone)),
                    icon: const Icon(Icons.call),
                    label: Text('اتصال بالعميل  $phone'),
                  ),
                ],
              ]),
              if (collect != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(color: AppColors.success50, borderRadius: BorderRadius.circular(12)),
                  child: Row(children: [
                    const Icon(Icons.payments_outlined, color: AppColors.success600, size: 30),
                    const SizedBox(width: 12),
                    const Expanded(child: Text('المبلغ المطلوب تحصيله نقداً عند الإتمام', style: TextStyle(color: AppColors.success600))),
                    Text(money(collect), style: const TextStyle(color: AppColors.success600, fontWeight: FontWeight.w700, fontSize: 18)),
                  ]),
                ),
              ],
              const SizedBox(height: 20),
              if (!leader)
                const Text('قائد الفريق هو من يقبل الزيارة ويحدّث حالتها.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.gray500)),
              if (leader && pending) ...[
                FilledButton.icon(
                  onPressed: () => act(() => Api.I.post('/worker/bookings/$id/accept'), 'تم قبول الزيارة'),
                  icon: const Icon(Icons.check),
                  label: const Text('قبول الزيارة'),
                ),
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    final reason = await promptText(context, 'رفض الإسناد', hint: 'سبب الرفض', required: true);
                    if (reason == null) return;
                    await act(() => Api.I.post('/worker/bookings/$id/reject', {'reason': reason}), 'تم الرفض — أُعيدت للإدارة');
                  },
                  icon: const Icon(Icons.close),
                  label: const Text('رفض'),
                ),
              ],
              if (leader)
                for (final s in next) ...[
                  FilledButton.icon(
                    onPressed: () async {
                      final v = value(s);
                      if (v == 'completed' &&
                          !await confirm(context, 'إتمام الزيارة', 'هل استلمت ${money(collect)} نقداً من العميل؟', ok: 'نعم، تم الاستلام')) {
                        return;
                      }
                      await act(() => Api.I.post('/worker/bookings/$id/status', {'status': v}), 'تم التحديث: ${label(s)}');
                    },
                    icon: Icon(_icons[value(s)] ?? Icons.arrow_back),
                    label: Text(label(s)),
                  ),
                  const SizedBox(height: 10),
                ],
            ],
          );
        },
      ),
    );
  }
}
