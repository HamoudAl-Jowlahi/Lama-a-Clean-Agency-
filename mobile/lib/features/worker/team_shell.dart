import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
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

  @override
  Widget build(BuildContext context) {
    final team = Session.I.team;
    final scopes = {'new': tr.scopeNew, 'today': tr.today, 'upcoming': tr.scopeUpcoming, 'done': tr.scopeDone};
    return DefaultTabController(
      length: scopes.length,
      initialIndex: 1,
      child: Scaffold(
        appBar: AppBar(
          title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(team?['name'] ?? tr.myTeam),
            Text(
              Session.I.isLeader ? tr.youAreLeader : tr.memberNote,
              style: const TextStyle(fontSize: 12.5, color: AppColors.gray500, fontWeight: FontWeight.w500),
            ),
          ]),
          actions: [
            const NotificationsButton(),
            IconButton(
              tooltip: tr.tabAccount,
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WorkerAccountScreen())),
              icon: const Icon(Icons.person_outline),
            ),
          ],
          bottom: TabBar(tabs: [for (final s in scopes.values) Tab(text: s)]),
        ),
        body: team == null
            ? EmptyState(tr.noTeam, icon: Icons.groups_outlined)
            : TabBarView(children: [for (final s in scopes.keys) _BookingsList(scope: s)]),
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
      skeleton: true,
      load: () async => (await Api.I.get('/worker/bookings', query: {'scope': scope}))['data'] as List,
      builder: (context, list, reload) => list.isEmpty
          ? ListView(children: [EmptyState(tr.noVisitsHere, icon: Icons.event_available_outlined)])
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
                            child: Text(items.map((i) => i['service']).join(isEn ? ', ' : '، '), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                          ),
                          StatusChip(pending ? b['assignment_status'] : b['status']),
                        ]),
                        const SizedBox(height: 6),
                        Text('${dayLabel(b['scheduled_date'])} · ${b['scheduled_time']}', style: const TextStyle(color: AppColors.gray500)),
                        Text(addressLine(b['address']), style: const TextStyle(color: AppColors.gray500), maxLines: 1, overflow: TextOverflow.ellipsis),
                        if (pending && b['is_leader'] == true)
                          Padding(
                            padding: const EdgeInsets.only(top: 6),
                            child: Text(tr.awaitingYourAcceptance, style: const TextStyle(color: AppColors.warning600, fontWeight: FontWeight.w700)),
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
    return Scaffold(
      appBar: AppBar(title: Text(tr.tabAccount)),
      body: ListenableBuilder(
        listenable: Session.I,
        builder: (context, _) {
          final u = Session.I.user ?? {};
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ProfileHeader(name: u['name'] ?? '', subtitle: u['phone'] ?? '', badge: label(u['worker']?['type'])),
              const SizedBox(height: 16),
              Card(
                child: MenuTile(
                  icon: Icons.star_outline,
                  title: tr.myRatings,
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RatingsScreen())),
                ),
              ),
              const SizedBox(height: 16),
              const AccountSettingsCard(),
              const SizedBox(height: 16),
              const LogoutButton(),
            ],
          );
        },
      ),
    );
  }
}
