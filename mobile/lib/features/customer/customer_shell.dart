import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/notifications_screen.dart';
import 'account_tab.dart';
import 'booking_flow.dart';
import 'bookings_tab.dart';
import 'contract_flow.dart';
import 'contracts_tab.dart';

class CustomerShell extends StatefulWidget {
  const CustomerShell({super.key});

  @override
  State<CustomerShell> createState() => _CustomerShellState();
}

class _CustomerShellState extends State<CustomerShell> {
  int _tab = 0;

  void go(int tab) => setState(() => _tab = tab);

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeTab(onGoTab: go),
      const BookingsTab(),
      const ContractsTab(),
      const AccountTab(),
    ];
    return Scaffold(
      body: AnimatedSwitcher(duration: const Duration(milliseconds: 220), child: KeyedSubtree(key: ValueKey(_tab), child: pages[_tab])),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: go,
        destinations: [
          NavigationDestination(icon: const Icon(Icons.home_outlined), selectedIcon: const Icon(Icons.home), label: tr.tabHome),
          NavigationDestination(icon: const Icon(Icons.event_note_outlined), selectedIcon: const Icon(Icons.event_note), label: tr.tabVisits),
          NavigationDestination(icon: const Icon(Icons.assignment_ind_outlined), selectedIcon: const Icon(Icons.assignment_ind), label: tr.tabContracts),
          NavigationDestination(icon: const Icon(Icons.person_outline), selectedIcon: const Icon(Icons.person), label: tr.tabAccount),
        ],
      ),
    );
  }
}

class HomeTab extends StatelessWidget {
  const HomeTab({super.key, required this.onGoTab});

  final ValueChanged<int> onGoTab;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(
        title: Text(tr.hello(Session.I.firstName)),
        actions: const [NotificationsButton()],
      ),
      body: Loader<List>(
        skeleton: true,
        header: true,
        load: () async => (await Api.I.get('/services'))['data'] as List,
        builder: (context, services, reload) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _ContractHero(onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ContractPlansScreen()))),
            const SizedBox(height: 20),
            Text(tr.visitTitle, style: t.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
            Text(tr.visitSubtitle, style: const TextStyle(color: AppColors.gray500)),
            const SizedBox(height: 12),
            if (services.isEmpty) EmptyState(tr.noServices),
            for (final s in services) ...[
              _ServiceCard(
                service: s,
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => BookingFlowScreen(service: s))),
              ),
              const SizedBox(height: 10),
            ],
          ],
        ),
      ),
    );
  }
}

class _ContractHero extends StatelessWidget {
  const _ContractHero({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.blue700,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(tr.heroTitle, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(tr.heroSubtitle, style: const TextStyle(color: AppColors.blue100)),
              ]),
            ),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.arrow_forward, color: Colors.white),
            ),
          ]),
        ),
      ),
    );
  }
}

class _ServiceCard extends StatelessWidget {
  const _ServiceCard({required this.service, required this.onTap});

  final Map service;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(color: AppColors.blue50, borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.cleaning_services_outlined, color: AppColors.blue600),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(service['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                if (service['description'] != null)
                  Text(service['description'], maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.gray500, fontSize: 13)),
              ]),
            ),
            const SizedBox(width: 8),
            Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
              Text(tr.startsFrom, style: const TextStyle(color: AppColors.gray500, fontSize: 12)),
              Text(money(service['starting_price']), style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.blue700)),
            ]),
          ]),
        ),
      ),
    );
  }
}
