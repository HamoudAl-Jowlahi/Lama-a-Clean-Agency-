import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/notifications_screen.dart';
import 'team_shell.dart';

/// الخادمة (CR-3): تعمل بعقود فقط — لا حالات تنفيذ ولا "في الطريق".
class HousekeeperShell extends StatelessWidget {
  const HousekeeperShell({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('أهلاً ${Session.I.firstName}'),
        actions: [
          const NotificationsButton(),
          IconButton(
            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WorkerAccountScreen())),
            icon: const Icon(Icons.person_outline),
          ),
        ],
      ),
      body: Loader<List>(
        load: () async => (await Api.I.get('/worker/contracts'))['data'] as List,
        builder: (context, list, reload) {
          final current = list.where((c) => c['is_current'] == true).toList();
          final others = list.where((c) => c['is_current'] != true).toList();
          if (list.isEmpty) {
            return ListView(children: const [EmptyState('لا توجد عقود مسندة إليك حالياً', icon: Icons.assignment_outlined)]);
          }
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (current.isNotEmpty) ...[
                const Text('عقدي الحالي', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                const SizedBox(height: 8),
                for (final c in current) _ContractTile(c, highlight: true, onBack: reload),
                const SizedBox(height: 16),
              ],
              if (others.isNotEmpty) ...[
                const Text('العقود السابقة والقادمة', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                const SizedBox(height: 8),
                for (final c in others) _ContractTile(c, onBack: reload),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _ContractTile extends StatelessWidget {
  const _ContractTile(this.c, {this.highlight = false, required this.onBack});

  final Map c;
  final bool highlight;
  final Future<void> Function() onBack;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Card(
        color: highlight ? AppColors.blue50 : null,
        clipBehavior: Clip.antiAlias,
        child: ListTile(
          contentPadding: const EdgeInsets.all(12),
          title: Text(c['plan']?['name'] ?? c['number'], style: const TextStyle(fontWeight: FontWeight.w700)),
          subtitle: Text('${c['customer']?['name'] ?? ''}\n${c['my_period']?['from'] ?? ''} ← ${c['my_period']?['to'] ?? ''}'),
          isThreeLine: true,
          trailing: StatusChip(c['status']),
          onTap: () async {
            await Navigator.push(context, MaterialPageRoute(builder: (_) => HousekeeperContractScreen(id: c['id'] as int)));
            onBack();
          },
        ),
      ),
    );
  }
}

class HousekeeperContractScreen extends StatelessWidget {
  const HousekeeperContractScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('العقد')),
      body: Loader<Map>(
        load: () async => (await Api.I.get('/worker/contracts/$id'))['data'] as Map,
        builder: (context, c, reload) {
          final phone = c['customer']?['phone'] as String?;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              SectionCard(title: c['number'], trailing: StatusChip(c['status']), children: [
                KV('الباقة', c['plan']?['name'] ?? '—'),
                KV('الدوام', '${c['plan']?['work_days_per_week']} أيام × ${c['plan']?['hours_per_day']} ساعات'),
                KV('فترتي', '${dayLabel(c['my_period']?['from'])} ← ${dayLabel(c['my_period']?['to'])}'),
              ]),
              const SizedBox(height: 12),
              SectionCard(title: 'العميل', children: [
                KV('الاسم', c['customer']?['name'] ?? '—'),
                if (c['is_current'] == true) ...[
                  KV('العنوان', addressLine(c['address'])),
                  if (c['customer_notes'] != null) KV('ملاحظات', c['customer_notes']),
                  if (phone != null) ...[
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      onPressed: () => launchUrl(Uri(scheme: 'tel', path: phone)),
                      icon: const Icon(Icons.call),
                      label: Text('اتصال  $phone'),
                    ),
                  ],
                ] else
                  const Text('تظهر بيانات التواصل والعنوان خلال فترة عملك فقط.', style: TextStyle(color: AppColors.gray500)),
              ]),
            ],
          );
        },
      ),
    );
  }
}
