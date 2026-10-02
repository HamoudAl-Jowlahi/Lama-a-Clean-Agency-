import 'package:flutter/material.dart';

import '../../core/api.dart';
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
            const SizedBox(height: 40),
            const _Brand(),
            const SizedBox(height: 40),
            Text('تسجيل الدخول', style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            const Text('للعملاء وفرق التنظيف والعاملات', style: TextStyle(color: AppColors.gray500)),
            const SizedBox(height: 24),
            TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(
                labelText: 'رقم الجوال',
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
                labelText: 'كلمة المرور',
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
            FilledButton(
              onPressed: _busy ? null : _submit,
              child: _busy
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                  : const Text('دخول'),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen())),
              child: const Text('عميل جديد؟ أنشئ حساباً'),
            ),
            const SizedBox(height: 8),
            const Text(
              'حسابات الفرق والعاملات تُنشأ من إدارة الوكالة.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.gray500, fontSize: 12.5),
            ),
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
      appBar: AppBar(title: const Text('حساب جديد')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(controller: _name, decoration: InputDecoration(labelText: 'الاسم', errorText: _error?.field('name'))),
          const SizedBox(height: 14),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(labelText: 'رقم الجوال', hintText: '05XXXXXXXX', errorText: _error?.field('phone')),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _email,
            keyboardType: TextInputType.emailAddress,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(labelText: 'البريد الإلكتروني (اختياري)', errorText: _error?.field('email')),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _password,
            obscureText: true,
            decoration: InputDecoration(labelText: 'كلمة المرور', errorText: _error?.field('password')),
          ),
          const SizedBox(height: 24),
          FilledButton(onPressed: _busy ? null : _submit, child: Text(_busy ? 'جارٍ الإنشاء…' : 'إنشاء الحساب')),
        ],
      ),
    );
  }
}

class _Brand extends StatelessWidget {
  const _Brand();

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      Container(
        width: 84,
        height: 84,
        decoration: BoxDecoration(color: AppColors.blue700, borderRadius: BorderRadius.circular(24)),
        child: const Icon(Icons.auto_awesome, color: Colors.white, size: 44),
      ),
      const SizedBox(height: 12),
      Text('لمعة', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700, color: AppColors.blue700)),
      const Text('خدمات تنظيف بثقة', style: TextStyle(color: AppColors.gray500)),
    ]);
  }
}
