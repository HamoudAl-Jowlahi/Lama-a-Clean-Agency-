import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../auth/login_screen.dart';

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
            child: Text(name.isEmpty ? '?' : name.characters.first, style: const TextStyle(color: Colors.white, fontSize: 22)),
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

class MenuTile extends StatelessWidget {
  const MenuTile({super.key, required this.icon, required this.title, required this.onTap, this.trailing});

  final IconData icon;
  final String title;
  final VoidCallback onTap;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) => ListTile(
        leading: Icon(icon),
        title: Text(title),
        trailing: trailing ?? const Icon(Icons.chevron_right),
        onTap: onTap,
      );
}

/// إعدادات الحساب: الملف الشخصي، كلمة المرور، اللغة، الإشعارات.
class AccountSettingsCard extends StatelessWidget {
  const AccountSettingsCard({super.key});

  @override
  Widget build(BuildContext context) {
    void open(Widget page) => Navigator.push(context, MaterialPageRoute(builder: (_) => page));
    return Card(
      child: Column(children: [
        MenuTile(icon: Icons.badge_outlined, title: tr.editProfile, onTap: () => open(const EditProfileScreen())),
        const Divider(height: 1),
        MenuTile(icon: Icons.password, title: tr.changePassword, onTap: () => open(const ChangePasswordScreen())),
        const Divider(height: 1),
        ListTile(leading: const Icon(Icons.translate), title: Text(tr.language), trailing: const LanguageSwitch()),
        const Divider(height: 1),
        const _NotificationPrefs(),
      ]),
    );
  }
}

/// تفضيلات الإشعارات (تُوقف الـ Push فقط؛ القائمة تبقى) — PATCH /auth/me.
class _NotificationPrefs extends StatelessWidget {
  const _NotificationPrefs();

  Future<void> _set(BuildContext context, String key, bool v) async {
    final prefs = Map<String, dynamic>.from(Session.I.user?['notification_preferences'] ?? {});
    prefs[key] = v;
    await run(context, overlay: false, () async {
      await Api.I.patch('/auth/me', {'notification_preferences': prefs});
      await Session.I.refreshMe();
    });
  }

  @override
  Widget build(BuildContext context) {
    final prefs = Session.I.user?['notification_preferences'] as Map? ?? {};
    final labels = {'orders': tr.notifyOrders, 'complaints': tr.notifyComplaints};
    return Column(children: [
      for (final e in labels.entries)
        if (prefs.containsKey(e.key))
          SwitchListTile(
            secondary: const Icon(Icons.notifications_active_outlined),
            value: prefs[e.key] == true,
            onChanged: (v) => _set(context, e.key, v),
            title: Text(e.value),
          ),
    ]);
  }
}

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late final _name = TextEditingController(text: Session.I.user?['name'] ?? '');
  late final _email = TextEditingController(text: Session.I.user?['email'] ?? '');
  bool _busy = false;
  ApiException? _error;

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await Api.I.patch('/auth/me', {'name': _name.text.trim(), 'email': _email.text.trim().isEmpty ? null : _email.text.trim()});
      await Session.I.refreshMe();
      if (!mounted) return;
      toast(context, tr.saved);
      Navigator.pop(context);
    } on ApiException catch (e) {
      setState(() => _error = e);
      if (e.errors == null && mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.editProfile)),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(controller: _name, decoration: InputDecoration(labelText: tr.name, errorText: _error?.field('name'))),
        const SizedBox(height: 14),
        TextField(
          controller: _email,
          keyboardType: TextInputType.emailAddress,
          textDirection: TextDirection.ltr,
          decoration: InputDecoration(labelText: tr.emailOptional, errorText: _error?.field('email')),
        ),
        const SizedBox(height: 14),
        TextField(
          enabled: false,
          controller: TextEditingController(text: Session.I.user?['phone'] ?? ''),
          textDirection: TextDirection.ltr,
          decoration: InputDecoration(labelText: tr.phone, helperText: tr.phoneLocked),
        ),
        const SizedBox(height: 24),
        BusyButton(label: tr.save, busy: _busy, onPressed: _save),
      ]),
    );
  }
}

class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _current = TextEditingController();
  final _new = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  ApiException? _error;
  String? _mismatch;

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _error = null;
      _mismatch = _new.text != _confirm.text ? tr.passwordsDontMatch : null;
    });
    if (_mismatch != null) return;
    setState(() => _busy = true);
    try {
      await Api.I.post('/auth/password', {'current_password': _current.text, 'password': _new.text});
      if (!mounted) return;
      toast(context, tr.passwordChanged);
      Navigator.pop(context);
    } on ApiException catch (e) {
      setState(() => _error = e);
      if (e.errors == null && mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.changePassword)),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(
          controller: _current,
          obscureText: true,
          decoration: InputDecoration(labelText: tr.currentPassword, errorText: _error?.field('current_password')),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _new,
          obscureText: true,
          decoration: InputDecoration(labelText: tr.newPassword, helperText: tr.passwordHint, errorText: _error?.field('password')),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _confirm,
          obscureText: true,
          decoration: InputDecoration(labelText: tr.confirmPassword, errorText: _mismatch),
        ),
        const SizedBox(height: 8),
        Text(tr.otherDevicesLoggedOut, style: const TextStyle(color: AppColors.gray500, fontSize: 12.5)),
        const SizedBox(height: 24),
        BusyButton(label: tr.save, busy: _busy, onPressed: _save),
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
        if (await confirm(context, tr.logout, tr.logoutQ, ok: tr.logoutShort, danger: true)) {
          if (context.mounted) await run(context, Session.I.logout);
        }
      },
      icon: const Icon(Icons.logout),
      label: Text(tr.logout),
    );
  }
}
