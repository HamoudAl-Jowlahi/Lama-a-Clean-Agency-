import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/account_widgets.dart';
import '../shared/notifications_screen.dart';
import 'ratings_screen.dart';
import 'worker_booking_detail.dart';

/// فريق الزيارات (CR-3): كل الأعضاء يرون زيارات الفريق، والقائد وحده يتصرف.
class TeamShell extends StatelessWidget {
  const TeamShell({super.key});

  static const _scopes = {'new': 'جديدة', 'today': 'اليوم', 'upcoming': 'القادمة', 'done': 'المنجزة'};

  @override
  Widget build(BuildContext context) {
    final team = Session.I.team;
    return DefaultTabController(
      length: _scopes.length,
      initialIndex: 1,
      child: Scaffold(
        appBar: AppBar(
          title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(team?['name'] ?? 'فريقي'),
            Text(
              Session.I.isLeader ? 'أنت قائد الفريق' : 'عضو — القائد يحدّث الحالة',
              style: const TextStyle(fontSize: 12.5, color: AppColors.gray500, fontWeight: FontWeight.w500),
            ),
          ]),
          actions: [
            const NotificationsButton(),
            IconButton(
              tooltip: 'حسابي',
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WorkerAccountScreen())),
              icon: const Icon(Icons.person_outline),
            ),
          ],
          bottom: TabBar(tabs: [for (final s in _scopes.values) Tab(text: s)]),
        ),
        body: team == null
            ? const EmptyState('لم تُضَف إلى فريق بعد. تواصل مع الإدارة.', icon: Icons.groups_outlined)
            : TabBarView(children: [for (final s in _scopes.keys) _BookingsList(scope: s)]),
      ),
    );
  }
}

class _BookingsList extends StatelessWidget {
  const _BookingsList({required this.scope});

  final String scope;

  @override
  Widget build(BuildContext context) {
    return Loader<List>(
      load: () async => (await Api.I.get('/worker/bookings', query: {'scope': scope}))['data'] as List,
      builder: (context, list, reload) => list.isEmpty
          ? ListView(children: const [EmptyState('لا توجد زيارات هنا', icon: Icons.event_available_outlined)])
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (_, i) {
                final b = list[i];
                final items = b['items'] as List? ?? [];
                final pending = value(b['assignment_status']) == 'pending';
                return Card(
                  clipBehavior: Clip.antiAlias,
                  child: InkWell(
                    onTap: () async {
                      await Navigator.push(context, MaterialPageRoute(builder: (_) => WorkerBookingDetailScreen(id: b['id'] as int)));
                      reload();
                    },
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Row(children: [
                          Expanded(
                            child: Text(items.map((i) => i['service']).join('، '), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                          ),
                          StatusChip(pending ? b['assignment_status'] : b['status']),
                        ]),
                        const SizedBox(height: 6),
                        Text('${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}', style: const TextStyle(color: AppColors.gray500)),
                        Text(addressLine(b['address']), style: const TextStyle(color: AppColors.gray500), maxLines: 1, overflow: TextOverflow.ellipsis),
                        if (pending && b['is_leader'] == true)
                          const Padding(
                            padding: EdgeInsets.only(top: 6),
                            child: Text('بانتظار قبولك', style: TextStyle(color: AppColors.warning600, fontWeight: FontWeight.w700)),
                          ),
                      ]),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

class WorkerAccountScreen extends StatelessWidget {
  const WorkerAccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final u = Session.I.user ?? {};
    return Scaffold(
      appBar: AppBar(title: const Text('حسابي')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ProfileHeader(
            name: u['name'] ?? '',
            subtitle: u['phone'] ?? '',
            badge: label(u['worker']?['type']),
          ),
          const SizedBox(height: 16),
          Card(
            child: ListTile(
              leading: const Icon(Icons.star_outline),
              title: const Text('تقييماتي'),
              trailing: const Icon(Icons.chevron_left),
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RatingsScreen())),
            ),
          ),
          const SizedBox(height: 16),
          const NotificationPrefsCard(),
          const SizedBox(height: 16),
          const LogoutButton(),
        ],
      ),
    );
  }
}
