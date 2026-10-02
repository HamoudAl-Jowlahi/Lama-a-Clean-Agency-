import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
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
      appBar: AppBar(title: Text(tr.visitDetails)),
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
              if (justCreated) SuccessBanner(tr.bookingReceived),
              SectionCard(
                title: b['number'],
                trailing: StatusChip(b['status']),
                children: [
                  KV(tr.appointment, '${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}'),
                  KV(tr.address, addressLine(b['address'])),
                  KV(tr.assignedTeam, b['team']?['name'] ?? tr.notAssignedYet),
                  if (b['customer_notes'] != null) KV(tr.yourNotes, b['customer_notes']),
                  if (b['cancel_reason'] != null) KV(tr.cancelReason, b['cancel_reason']),
                ],
              ),
              const SizedBox(height: 12),
              SectionCard(title: tr.serviceAndAmount, children: [
                for (final i in items) KV('${i['service']} — ${i['option']} × ${i['quantity']}', money(i['total'])),
                const Divider(),
                KV(tr.tax, money(b['tax'])),
                KV(tr.total, money(b['total']), bold: true),
                KV(tr.paymentMethod, tr.cashOnCompletion),
                if (payment != null) KV(tr.paymentStatus, label(payment['status'])),
              ]),
              const SizedBox(height: 12),
              SectionCard(title: tr.tracking, children: [Timeline(b['timeline'] as List? ?? [])]),
              if (rating != null) ...[
                const SizedBox(height: 12),
                SectionCard(title: tr.yourRating, children: [
                  KV(tr.service, '★' * (rating['service_score'] as int? ?? 0)),
                  if (rating['worker_score'] != null) KV(tr.team, '★' * (rating['worker_score'] as int)),
                  if (rating['comment'] != null) Text(rating['comment']),
                ]),
              ],
              const SizedBox(height: 16),
              if (b['can_rate'] == true)
                FilledButton.icon(
                  onPressed: () async {
                    if (await showRatingSheet(context, path: '/bookings/$id/rating', workerLabel: tr.rateTeam)) reload();
                  },
                  icon: Icon(Icons.star_outline),
                  label: Text(tr.rateVisit),
                ),
              if (_cancellable.contains(status)) ...[
                SizedBox(height: 10),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
                  onPressed: () async {
                    final reason = await promptText(context, tr.cancelVisit, hint: tr.cancelReasonOptional);
                    if (reason == null || !context.mounted) return;
                    if (await run(context, () => Api.I.post('/bookings/$id/cancel', {'reason': reason}), success: tr.visitCancelled)) reload();
                  },
                  icon: const Icon(Icons.close),
                  label: Text(tr.cancelVisit),
                ),
              ],
              const SizedBox(height: 10),
              TextButton.icon(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => ComplaintFormScreen(subjectType: 'booking', subjectId: id, subjectNumber: b['number'])),
                ),
                icon: const Icon(Icons.report_outlined),
                label: Text(tr.haveProblem),
              ),
            ],
          );
        },
      ),
    );
  }
}

class SuccessBanner extends StatelessWidget {
  const SuccessBanner(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 500),
      curve: Curves.easeOutBack,
      builder: (_, v, child) => Opacity(opacity: v.clamp(0, 1), child: Transform.scale(scale: .9 + .1 * v, child: child)),
      child: Container(
        margin: EdgeInsets.only(bottom: 14),
        padding: EdgeInsets.symmetric(vertical: 22, horizontal: 16),
        decoration: BoxDecoration(color: AppColors.success50, borderRadius: BorderRadius.circular(20)),
        child: Column(children: [
          SuccessCheck(),
          SizedBox(height: 12),
          Text(text, textAlign: TextAlign.center, style: TextStyle(color: AppColors.success600, fontWeight: FontWeight.w600, height: 1.5)),
        ]),
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
          Text(tr.rateService, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700)),
          StarsInput(value: service, onChanged: (v) => set(() => service = v)),
          Text(workerLabel, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700)),
          StarsInput(value: worker, onChanged: (v) => set(() => worker = v)),
          const SizedBox(height: 8),
          TextField(controller: comment, maxLines: 2, decoration: InputDecoration(hintText: tr.commentOptional)),
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
            child: Text(tr.sendRating),
          ),
        ]),
      ),
    ),
  );
  if (sent == true && context.mounted) toast(context, tr.thanksRating);
  return sent == true;
}
