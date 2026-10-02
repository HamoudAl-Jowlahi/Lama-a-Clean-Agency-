import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
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
      appBar: AppBar(title: Text(tr.visitDetails)),
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
                KV(tr.team, b['team']?['name'] ?? '—'),
                KV(tr.assignment, label(b['assignment_status'])),
                KV(tr.appointment, '${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}'),
                for (final i in items) KV(i['service'] ?? '', '${i['option']} × ${i['quantity']}'),
              ]),
              const SizedBox(height: 12),
              SectionCard(title: tr.customerAndLocation, children: [
                KV(tr.customer, b['customer']?['name'] ?? '—'),
                KV(tr.address, addressLine(b['address'])),
                if (b['address']?['details'] != null) KV(tr.description, b['address']['details']),
                if (b['customer_notes'] != null) KV(tr.customerNotes, b['customer_notes']),
                if (phone != null) ...[
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: () => launchUrl(Uri(scheme: 'tel', path: phone)),
                    icon: const Icon(Icons.call),
                    label: Text(tr.callCustomer(phone)),
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
                    Expanded(child: Text(tr.amountToCollect, style: const TextStyle(color: AppColors.success600))),
                    Text(money(collect), style: const TextStyle(color: AppColors.success600, fontWeight: FontWeight.w700, fontSize: 18)),
                  ]),
                ),
              ],
              const SizedBox(height: 20),
              if (!leader) Text(tr.leaderOnlyNote, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.gray500)),
              if (leader && pending) ...[
                FilledButton.icon(
                  onPressed: () => act(() => Api.I.post('/worker/bookings/$id/accept'), tr.visitAccepted),
                  icon: const Icon(Icons.check),
                  label: Text(tr.acceptVisit),
                ),
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    final reason = await promptText(context, tr.rejectAssignment, hint: tr.rejectReason, required: true);
                    if (reason == null) return;
                    await act(() => Api.I.post('/worker/bookings/$id/reject', {'reason': reason}), tr.rejectedBack);
                  },
                  icon: const Icon(Icons.close),
                  label: Text(tr.reject),
                ),
              ],
              if (leader)
                for (final s in next) ...[
                  FilledButton.icon(
                    onPressed: () async {
                      final v = value(s);
                      if (v == 'completed' &&
                          !await confirm(context, tr.completeVisit, tr.completeVisitQ(money(collect)), ok: tr.yesReceived)) {
                        return;
                      }
                      await act(() => Api.I.post('/worker/bookings/$id/status', {'status': v}), tr.updatedTo(label(s)));
                    },
                    icon: Icon(_icons[value(s)] ?? Icons.arrow_forward),
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
