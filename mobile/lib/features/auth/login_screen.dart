import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/brand.dart';
import '../../core/i18n.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _hide = true;
  ApiException? _error;

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await Session.I.login(_phone.text.trim(), _password.text);
    } on ApiException catch (e) {
      setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const Align(alignment: AlignmentDirectional.centerEnd, child: LanguageSwitch()),
            const SizedBox(height: 16),
            const _Brand(),
            const SizedBox(height: 36),
            Text(tr.loginTitle, style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            Text(tr.loginSubtitle, style: const TextStyle(color: AppColors.gray500)),
            const SizedBox(height: 24),
            TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(
                labelText: tr.phone,
                hintText: '05XXXXXXXX',
                prefixIcon: const Icon(Icons.phone_outlined),
                errorText: _error?.field('phone'),
              ),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _password,
              obscureText: _hide,
              decoration: InputDecoration(
                labelText: tr.password,
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  icon: Icon(_hide ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                  onPressed: () => setState(() => _hide = !_hide),
                ),
                errorText: _error?.field('password'),
              ),
              onSubmitted: (_) => _submit(),
            ),
            if (_error != null && _error!.errors == null) ...[
              const SizedBox(height: 12),
              Text(_error!.message, style: const TextStyle(color: AppColors.danger600)),
            ],
            const SizedBox(height: 24),
            BusyButton(label: tr.login, busy: _busy, onPressed: _submit),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen())),
              child: Text(tr.newCustomer),
            ),
            const SizedBox(height: 8),
            Text(tr.staffAccountsNote, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.gray500, fontSize: 12.5)),
          ],
        ),
      ),
    );
  }
}

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  ApiException? _error;

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await Session.I.register({
        'name': _name.text.trim(),
        'phone': _phone.text.trim(),
        if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
        'password': _password.text,
      });
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
      appBar: AppBar(title: Text(tr.registerTitle)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(controller: _name, decoration: InputDecoration(labelText: tr.name, errorText: _error?.field('name'))),
          const SizedBox(height: 14),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(labelText: tr.phone, hintText: '05XXXXXXXX', errorText: _error?.field('phone')),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _email,
            keyboardType: TextInputType.emailAddress,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(labelText: tr.emailOptional, errorText: _error?.field('email')),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _password,
            obscureText: true,
            decoration: InputDecoration(labelText: tr.password, helperText: tr.passwordHint, errorText: _error?.field('password')),
          ),
          const SizedBox(height: 24),
          BusyButton(label: tr.createAccount, busy: _busy, onPressed: _submit),
        ],
      ),
    );
  }
}

/// تبديل اللغة (عربي / English).
class LanguageSwitch extends StatelessWidget {
  const LanguageSwitch({super.key});

  @override
  Widget build(BuildContext context) {
    return SegmentedButton<String>(
      showSelectedIcon: false,
      style: SegmentedButton.styleFrom(visualDensity: VisualDensity.compact),
      segments: const [
        ButtonSegment(value: 'ar', label: Text('عربي')),
        ButtonSegment(value: 'en', label: Text('English')),
      ],
      selected: {Session.I.lang},
      onSelectionChanged: (s) => run(context, () => Session.I.setLang(s.first)),
    );
  }
}

class _Brand extends StatelessWidget {
  const _Brand();

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      Container(
        width: 92,
        height: 92,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.blue700,
          borderRadius: BorderRadius.circular(26),
          boxShadow: [BoxShadow(color: AppColors.blue700.withValues(alpha: .3), blurRadius: 24, offset: const Offset(0, 10))],
        ),
        child: const LamaaMark(color: Colors.white),
      ),
      const SizedBox(height: 14),
      Text(tr.appName, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700, color: AppColors.blue700)),
      Text(tr.tagline, style: const TextStyle(color: AppColors.gray500)),
    ]);
  }
}
