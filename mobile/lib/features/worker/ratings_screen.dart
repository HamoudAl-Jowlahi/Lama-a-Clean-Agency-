import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// الخادمة: تقييماتها · عضو الفريق: تقييمات فريقه — بدون اسم العميل.
class RatingsScreen extends StatelessWidget {
  const RatingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.myRatings)),
      body: Loader<Map<String, dynamic>>(
        skeleton: true,
        load: () => Api.I.get('/worker/ratings'),
        builder: (context, res, reload) {
          final list = res['data'] as List;
          final meta = res['meta'] as Map? ?? {};
          return ListView(
            padding: EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: Column(children: [
                    Text('${meta['average'] ?? 0}', style: TextStyle(fontSize: 40, fontWeight: FontWeight.w700, color: AppColors.blue700)),
                    Text(tr.averageOf(meta['count'] as int? ?? 0), style: TextStyle(color: AppColors.gray500)),
                  ]),
                ),
              ),
              const SizedBox(height: 12),
              if (list.isEmpty) EmptyState(tr.noRatings, icon: Icons.star_border),
              for (final r in list)
                Appear(index: list.indexOf(r), child: Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: ListTile(
                    title: Text('★' * ((r['worker_score'] ?? r['service_score'] ?? 0) as int), style: const TextStyle(color: Color(0xFFF5B301), fontSize: 18)),
                    subtitle: Text([if (r['comment'] != null) r['comment'], dateTimeLabel(r['created_at'])].join('\n')),
                  ),
                )),
            ],
          );
        },
      ),
    );
  }
}
