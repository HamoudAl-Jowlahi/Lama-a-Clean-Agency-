import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

class ProfileHeader extends StatelessWidget {
  const ProfileHeader({super.key, required this.name, required this.subtitle, this.badge});

  final String name;
  final String subtitle;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: AppColors.blue600,
            child: Text(name.isEmpty ? '؟' : name.characters.first, style: const TextStyle(color: Colors.white, fontSize: 22)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 17)),
              Text(subtitle, textDirection: TextDirection.ltr, style: const TextStyle(color: AppColors.gray500)),
            ]),
          ),
          if (badge != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(color: AppColors.blue50, borderRadius: BorderRadius.circular(99)),
              child: Text(badge!, style: const TextStyle(color: AppColors.blue700, fontWeight: FontWeight.w600)),
            ),
        ]),
      ),
    );
  }
}

/// تفضيلات الإشعارات (تُوقف الـ Push فقط؛ القائمة تبقى) — PATCH /auth/me.
class NotificationPrefsCard extends StatefulWidget {
  const NotificationPrefsCard({super.key});

  @override
  State<NotificationPrefsCard> createState() => _NotificationPrefsCardState();
}

class _NotificationPrefsCardState extends State<NotificationPrefsCard> {
  static const _labels = {'orders': 'إشعارات الطلبات والعقود', 'complaints': 'إشعارات الشكاوى'};

  Future<void> _set(String key, bool v) async {
    final prefs = Map<String, dynamic>.from(Session.I.user?['notification_preferences'] ?? {});
    prefs[key] = v;
    await run(context, () async {
      await Api.I.patch('/auth/me', {'notification_preferences': prefs});
      await Session.I.refreshMe();
    });
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final prefs = Session.I.user?['notification_preferences'] as Map? ?? {};
    return Card(
      child: Column(children: [
        for (final e in _labels.entries)
          if (prefs.containsKey(e.key))
            SwitchListTile(value: prefs[e.key] == true, onChanged: (v) => _set(e.key, v), title: Text(e.value)),
      ]),
    );
  }
}

class LogoutButton extends StatelessWidget {
  const LogoutButton({super.key});

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger600),
      onPressed: () async {
        if (await confirm(context, 'تسجيل الخروج', 'هل تريد تسجيل الخروج من هذا الجهاز؟', ok: 'خروج', danger: true)) {
          await Session.I.logout();
        }
      },
      icon: const Icon(Icons.logout),
      label: const Text('تسجيل الخروج'),
    );
  }
}
