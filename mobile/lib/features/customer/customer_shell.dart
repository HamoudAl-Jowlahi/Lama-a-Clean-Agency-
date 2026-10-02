import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
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
      body: AnimatedSwitcher(
        duration: Duration(milliseconds: 280),
        switchInCurve: Curves.easeOutCubic,
        transitionBuilder: (child, a) => FadeTransition(
          opacity: a,
          child: SlideTransition(position: Tween(begin: Offset(0, .02), end: Offset.zero).animate(a), child: child),
        ),
        child: KeyedSubtree(key: ValueKey(_tab), child: pages[_tab]),
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(border: Border(top: BorderSide(color: AppColors.gray200.withValues(alpha: .6)))),
        child: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: go,
          destinations: [
            NavigationDestination(icon: const Icon(Icons.home_outlined), selectedIcon: const Icon(Icons.home_rounded), label: tr.tabHome),
            NavigationDestination(icon: const Icon(Icons.event_note_outlined), selectedIcon: const Icon(Icons.event_note), label: tr.tabVisits),
            NavigationDestination(icon: const Icon(Icons.assignment_ind_outlined), selectedIcon: const Icon(Icons.assignment_ind), label: tr.tabContracts),
            NavigationDestination(icon: const Icon(Icons.person_outline), selectedIcon: const Icon(Icons.person), label: tr.tabAccount),
          ],
        ),
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
      body: Loader<List>(
        skeleton: true,
        header: true,
        load: () async => (await Api.I.get('/services'))['data'] as List,
        builder: (context, services, reload) => CustomScrollView(
          physics: AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: _HomeHeader(onContract: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ContractPlansScreen()))),
            ),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(16, 20, 16, 0),
              sliver: SliverToBoxAdapter(
                child: Appear(
                  index: 2,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(tr.howItWorks, style: t.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                    SizedBox(height: 10),
                    Row(children: [
                      Expanded(child: _Step(icon: Icons.touch_app_outlined, text: tr.howStep1, n: 1)),
                      SizedBox(width: 8),
                      Expanded(child: _Step(icon: Icons.groups_2_outlined, text: tr.howStep2, n: 2)),
                      SizedBox(width: 8),
                      Expanded(child: _Step(icon: Icons.payments_outlined, text: tr.howStep3, n: 3)),
                    ]),
                  ]),
                ),
              ),
            ),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(16, 24, 16, 4),
              sliver: SliverToBoxAdapter(
                child: Appear(
                  index: 3,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(tr.visitTitle, style: t.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
                    SizedBox(height: 2),
                    Text(tr.visitSubtitle, style: TextStyle(color: AppColors.gray500)),
                  ]),
                ),
              ),
            ),
            if (services.isEmpty) SliverToBoxAdapter(child: EmptyState(tr.noServices)),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              sliver: SliverGrid.builder(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: .8,
                ),
                itemCount: services.length,
                itemBuilder: (_, i) => Appear(
                  index: 4 + i,
                  child: _ServiceCard(
                    service: services[i],
                    color: serviceColor(i),
                    onTap: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => BookingFlowScreen(service: services[i], color: serviceColor(i))),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// رأس الرئيسية: تدرج الهوية مع لمعات متحركة، التحية، وبطاقة العقود.
class _HomeHeader extends StatelessWidget {
  const _HomeHeader({required this.onContract});

  final VoidCallback onContract;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: AppColors.brandGradient,
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
      ),
      child: Stack(children: [
        const Positioned.fill(child: SparkleField(count: 9)),
        SafeArea(
          bottom: false,
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(20, 8, 8, 22),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(
                  child: Appear(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(tr.hello(Session.I.firstName), style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700)),
                      Text(tr.homeQuestion, style: const TextStyle(color: Color(0xFFDAE6FC))),
                    ]),
                  ),
                ),
                const IconTheme(data: IconThemeData(color: Colors.white), child: NotificationsButton()),
              ]),
              const SizedBox(height: 18),
              Padding(
                padding: const EdgeInsetsDirectional.only(end: 12),
                child: Appear(
                  index: 1,
                  child: Pressable(
                    onTap: onContract,
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: .13),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: Colors.white.withValues(alpha: .22)),
                      ),
                      child: Row(children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14)),
                          child: const Icon(Icons.badge_outlined, color: AppColors.brand),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(tr.heroTitle, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 2),
                            Text(tr.heroSubtitle, style: const TextStyle(color: Color(0xFFDAE6FC), fontSize: 12.5)),
                          ]),
                        ),
                        const Icon(Icons.arrow_forward, color: Colors.white),
                      ]),
                    ),
                  ),
                ),
              ),
            ]),
          ),
        ),
      ]),
    );
  }
}

class _Step extends StatelessWidget {
  const _Step({required this.icon, required this.text, required this.n});

  final IconData icon;
  final String text;
  final int n;

  @override
  Widget build(BuildContext context) {
    return SoftCard(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 12),
      child: Column(children: [
        Stack(clipBehavior: Clip.none, children: [
          IconTile(icon, size: 42),
          PositionedDirectional(
            top: -6,
            end: -6,
            child: CircleAvatar(
              radius: 9,
              backgroundColor: AppColors.blue600,
              child: Text('$n', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
            ),
          ),
        ]),
        const SizedBox(height: 8),
        Text(text, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, height: 1.3)),
      ]),
    );
  }
}

class _ServiceCard extends StatelessWidget {
  const _ServiceCard({required this.service, required this.onTap, required this.color});

  final Map service;
  final VoidCallback onTap;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return SoftCard(
      onTap: onTap,
      padding: EdgeInsets.all(14),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Hero(tag: 'service-${service['id']}', child: IconTile(serviceIcon(service['icon']), color: color, size: 52)),
        SizedBox(height: 12),
        Text(service['name'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15, height: 1.3)),
        SizedBox(height: 4),
        Expanded(
          child: Text(service['description'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: AppColors.gray500, fontSize: 12)),
        ),
        Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(tr.startsFrom, style: TextStyle(color: AppColors.gray500, fontSize: 11)),
              Text(money(service['starting_price']), style: TextStyle(fontWeight: FontWeight.w700, color: color)),
            ]),
          ),
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(color: color.withValues(alpha: .12), shape: BoxShape.circle),
            child: Icon(Icons.arrow_forward, size: 16, color: color),
          ),
        ]),
      ]),
    );
  }
}
