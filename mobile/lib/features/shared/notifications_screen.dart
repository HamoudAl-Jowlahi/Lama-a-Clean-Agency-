import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../customer/booking_detail.dart';
import '../customer/complaints.dart';
import '../customer/contract_detail.dart';
import '../worker/housekeeper_shell.dart';
import '../worker/worker_booking_detail.dart';

/// جرس الإشعارات مع عدد غير المقروء.
class NotificationsButton extends StatefulWidget {
  const NotificationsButton({super.key});

  @override
  State<NotificationsButton> createState() => _NotificationsButtonState();
}

class _NotificationsButtonState extends State<NotificationsButton> {
  int _unread = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await Api.I.get('/notifications');
      if (mounted) setState(() => _unread = (res['meta']?['unread'] as int?) ?? 0);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: () async {
        await Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen()));
        _load();
      },
      icon: Badge(isLabelVisible: _unread > 0, label: Text('$_unread'), child: const Icon(Icons.notifications_outlined)),
    );
  }
}

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  Key _key = UniqueKey();

  /// يفتح الشاشة المرتبطة بالإشعار حسب دور المستخدم.
  Widget? _target(Map? subject) {
    if (subject == null) return null;
    final id = subject['id'] as int?;
    if (id == null) return null;
    final role = Session.I.role;
    return switch ((subject['type'], role)) {
      ('booking', AppRole.customer) => BookingDetailScreen(id: id),
      ('booking', AppRole.teamMember) => WorkerBookingDetailScreen(id: id),
      ('contract', AppRole.customer) => ContractDetailScreen(id: id),
      ('contract', AppRole.housekeeper) => HousekeeperContractScreen(id: id),
      ('complaint', AppRole.customer) => ComplaintDetailScreen(id: id),
      _ => null,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(tr.notifications),
        actions: [
          TextButton(
            onPressed: () async {
              if (await run(context, () => Api.I.post('/notifications/read-all'))) setState(() => _key = UniqueKey());
            },
            child: Text(tr.readAll),
          ),
        ],
      ),
      body: Loader<List>(
        key: _key,
        skeleton: true,
        load: () async => (await Api.I.get('/notifications'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: [EmptyState(tr.noNotifications, icon: Icons.notifications_none)])
            : ListView.separated(
                itemCount: list.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (_, i) {
                  final n = list[i];
                  final unread = n['read'] != true;
                  return ListTile(
                    tileColor: unread ? AppColors.blue50 : Colors.white,
                    leading: Icon(unread ? Icons.circle : Icons.circle_outlined, size: 12, color: AppColors.blue600),
                    title: Text(n['title'] ?? '', style: TextStyle(fontWeight: unread ? FontWeight.w700 : FontWeight.w500)),
                    subtitle: Text('${n['body'] ?? ''}\n${dateTimeLabel(n['created_at'])}'),
                    isThreeLine: true,
                    onTap: () async {
                      if (unread) {
                        try {
                          await Api.I.post('/notifications/${n['id']}/read');
                        } catch (_) {}
                      }
                      final page = _target(n['subject'] as Map?);
                      if (page != null && context.mounted) await Navigator.push(context, MaterialPageRoute(builder: (_) => page));
                      reload();
                    },
                  );
                },
              ),
      ),
    );
  }
}
