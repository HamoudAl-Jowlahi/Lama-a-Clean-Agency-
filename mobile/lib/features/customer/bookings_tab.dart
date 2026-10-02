import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'booking_detail.dart';

class BookingsTab extends StatelessWidget {
  const BookingsTab({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.tabVisits)),
      body: Loader<List>(
        skeleton: true,
        load: () async => (await Api.I.get('/bookings'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: [EmptyState(tr.noVisits, icon: Icons.event_busy_outlined)])
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) => BookingCard(
                  booking: list[i],
                  onTap: () async {
                    await Navigator.push(context, MaterialPageRoute(builder: (_) => BookingDetailScreen(id: list[i]['id'] as int)));
                    reload();
                  },
                ),
              ),
      ),
    );
  }
}

class BookingCard extends StatelessWidget {
  const BookingCard({super.key, required this.booking, required this.onTap});

  final Map booking;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final items = booking['items'] as List? ?? [];
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(
                child: Text(
                  items.isEmpty ? (booking['number'] ?? '') : items.map((i) => i['service']).join(isEn ? ', ' : '، '),
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
                ),
              ),
              StatusChip(booking['status']),
            ]),
            const SizedBox(height: 6),
            Row(children: [
              const Icon(Icons.schedule, size: 16, color: AppColors.gray500),
              const SizedBox(width: 4),
              Text('${dayLabel(booking['scheduled_date'])} · ${booking['scheduled_time'] ?? ''}', style: const TextStyle(color: AppColors.gray500)),
            ]),
            const SizedBox(height: 4),
            Row(children: [
              Text(booking['number'] ?? '', style: const TextStyle(color: AppColors.gray500, fontSize: 12)),
              const Spacer(),
              Text(money(booking['total']), style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.blue700)),
            ]),
          ]),
        ),
      ),
    );
  }
}
