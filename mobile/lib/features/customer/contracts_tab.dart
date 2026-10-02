import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'contract_detail.dart';
import 'contract_flow.dart';

class ContractsTab extends StatelessWidget {
  const ContractsTab({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.tabContracts)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ContractPlansScreen())),
        icon: const Icon(Icons.add),
        label: Text(tr.newContract),
      ),
      body: Loader<List>(
        skeleton: true,
        load: () async => (await Api.I.get('/contracts'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: [EmptyState(tr.noContracts, icon: Icons.assignment_outlined)])
            : ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 90),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final c = list[i];
                  final progress = c['progress'] as Map?;
                  return Card(
                    clipBehavior: Clip.antiAlias,
                    child: InkWell(
                      onTap: () async {
                        await Navigator.push(context, MaterialPageRoute(builder: (_) => ContractDetailScreen(id: c['id'] as int)));
                        reload();
                      },
                      child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Row(children: [
                            Expanded(child: Text(c['plan']?['name'] ?? c['number'], style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16))),
                            StatusChip(c['status']),
                          ]),
                          const SizedBox(height: 6),
                          Text('${dayLabel(c['start_date'])} $arrow ${dayLabel(c['end_date'])}', style: const TextStyle(color: AppColors.gray500)),
                          if (progress != null) ...[
                            const SizedBox(height: 8),
                            LinearProgressIndicator(
                              value: (progress['day'] as int) / (progress['total_days'] as int),
                              borderRadius: BorderRadius.circular(4),
                              minHeight: 6,
                            ),
                            const SizedBox(height: 4),
                            Text(tr.dayOf(progress['day'] as int, progress['total_days'] as int), style: const TextStyle(fontSize: 12, color: AppColors.gray500)),
                          ],
                        ]),
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
