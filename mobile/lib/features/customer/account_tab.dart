import 'package:flutter/material.dart';

import '../../core/i18n.dart';
import '../../core/session.dart';
import '../shared/account_widgets.dart';
import '../shared/notifications_screen.dart';
import 'addresses.dart';
import 'complaints.dart';

class AccountTab extends StatelessWidget {
  const AccountTab({super.key});

  @override
  Widget build(BuildContext context) {
    void open(Widget page) => Navigator.push(context, MaterialPageRoute(builder: (_) => page));
    return Scaffold(
      appBar: AppBar(title: Text(tr.tabAccount)),
      body: ListenableBuilder(
        listenable: Session.I,
        builder: (context, _) {
          final u = Session.I.user ?? {};
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ProfileHeader(name: u['name'] ?? '', subtitle: u['phone'] ?? ''),
              const SizedBox(height: 16),
              Card(
                child: Column(children: [
                  MenuTile(icon: Icons.location_on_outlined, title: tr.myAddresses, onTap: () => open(const AddressesScreen())),
                  const Divider(height: 1),
                  MenuTile(icon: Icons.support_agent, title: tr.myComplaints, onTap: () => open(const ComplaintsScreen())),
                  const Divider(height: 1),
                  MenuTile(icon: Icons.notifications_outlined, title: tr.notifications, onTap: () => open(const NotificationsScreen())),
                ]),
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
