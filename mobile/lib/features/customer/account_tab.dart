import 'package:flutter/material.dart';

import '../../core/session.dart';
import '../shared/account_widgets.dart';
import '../shared/notifications_screen.dart';
import 'addresses.dart';
import 'complaints.dart';

class AccountTab extends StatelessWidget {
  const AccountTab({super.key});

  @override
  Widget build(BuildContext context) {
    final u = Session.I.user ?? {};
    void open(Widget page) => Navigator.push(context, MaterialPageRoute(builder: (_) => page));
    return Scaffold(
      appBar: AppBar(title: const Text('حسابي')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ProfileHeader(name: u['name'] ?? '', subtitle: u['phone'] ?? ''),
          const SizedBox(height: 16),
          Card(
            child: Column(children: [
              ListTile(leading: const Icon(Icons.location_on_outlined), title: const Text('عناويني'), trailing: const Icon(Icons.chevron_left), onTap: () => open(const AddressesScreen())),
              const Divider(height: 1),
              ListTile(leading: const Icon(Icons.support_agent), title: const Text('شكاواي'), trailing: const Icon(Icons.chevron_left), onTap: () => open(const ComplaintsScreen())),
              const Divider(height: 1),
              ListTile(leading: const Icon(Icons.notifications_outlined), title: const Text('الإشعارات'), trailing: const Icon(Icons.chevron_left), onTap: () => open(const NotificationsScreen())),
            ]),
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
