import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/brand.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
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
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          Container(
            height: 300,
            decoration: BoxDecoration(
              gradient: AppColors.brandGradient,
              borderRadius: BorderRadius.vertical(bottom: Radius.circular(36)),
            ),
            child: Stack(children: [
              Positioned.fill(child: SparkleField(count: 12, maxSize: 26)),
              SafeArea(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Column(children: [
                    Align(alignment: AlignmentDirectional.centerEnd, child: LanguageSwitch(onBrand: true)),
                    Spacer(),
                    _Brand(),
                    Spacer(flex: 2),
                  ]),
                ),
              ),
            ]),
          ),
          Transform.translate(
            offset: Offset(0, -36),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 18),
              child: Appear(
                index: 2,
                offset: 40,
                child: SoftCard(
                  padding: EdgeInsets.all(22),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                    Text(tr.loginTitle, style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
                    SizedBox(height: 4),
                    Text(tr.loginSubtitle, style: TextStyle(color: AppColors.gray500)),
                    SizedBox(height: 22),
                    TextField(
                      controller: _phone,
                      keyboardType: TextInputType.phone,
                      textDirection: TextDirection.ltr,
                      decoration: InputDecoration(
                        labelText: tr.phone,
                        hintText: '05XXXXXXXX',
                        prefixIcon: Icon(Icons.phone_outlined),
                        errorText: _error?.field('phone'),
                      ),
                    ),
                    SizedBox(height: 14),
                    TextField(
                      controller: _password,
                      obscureText: _hide,
                      decoration: InputDecoration(
                        labelText: tr.password,
                        prefixIcon: Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: AnimatedSwitcher(
                            duration: Duration(milliseconds: 200),
                            child: Icon(_hide ? Icons.visibility_outlined : Icons.visibility_off_outlined, key: ValueKey(_hide)),
                          ),
                          onPressed: () => setState(() => _hide = !_hide),
                        ),
                        errorText: _error?.field('password'),
                      ),
                      onSubmitted: (_) => _submit(),
                    ),
                    AnimatedSize(
                      duration: Duration(milliseconds: 250),
                      child: _error != null && _error!.errors == null
                          ? Container(
                              margin: EdgeInsets.only(top: 12),
                              padding: EdgeInsets.all(12),
                              decoration: BoxDecoration(color: AppColors.danger50, borderRadius: BorderRadius.circular(12)),
                              child: Row(children: [
                                Icon(Icons.error_outline, color: AppColors.danger600, size: 20),
                                SizedBox(width: 8),
                                Expanded(child: Text(_error!.message, style: TextStyle(color: AppColors.danger600))),
                              ]),
                            )
                          : SizedBox(width: double.infinity),
                    ),
                    SizedBox(height: 20),
                    BusyButton(label: tr.login, busy: _busy, onPressed: _submit),
                  ]),
                ),
              ),
            ),
          ),
          Appear(
            index: 4,
            child: Column(children: [
              TextButton(
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => RegisterScreen())),
                child: Text(tr.newCustomer),
              ),
              Padding(
                padding: EdgeInsets.fromLTRB(24, 4, 24, 24),
                child: Text(tr.staffAccountsNote, textAlign: TextAlign.center, style: TextStyle(color: AppColors.gray500, fontSize: 12.5)),
              ),
            ]),
          ),
        ],
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
  const LanguageSwitch({super.key, this.onBrand = false});

  final bool onBrand;

  @override
  Widget build(BuildContext context) {
    return SegmentedButton<String>(
      showSelectedIcon: false,
      style: SegmentedButton.styleFrom(
        visualDensity: VisualDensity.compact,
        foregroundColor: onBrand ? Colors.white : null,
        selectedForegroundColor: onBrand ? AppColors.brand : null,
        selectedBackgroundColor: onBrand ? Colors.white : null,
        side: onBrand ? const BorderSide(color: Colors.white54) : null,
      ),
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
      TweenAnimationBuilder<double>(
        tween: Tween(begin: .6, end: 1),
        duration: const Duration(milliseconds: 900),
        curve: Curves.elasticOut,
        builder: (_, v, child) => Transform.scale(scale: v, child: child),
        child: Container(
          width: 96,
          height: 96,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(28),
            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .18), blurRadius: 28, offset: const Offset(0, 12))],
          ),
          child: const LamaaMark(color: AppColors.brand),
        ),
      ),
      const SizedBox(height: 14),
      Appear(
        index: 1,
        child: Column(children: [
          Text(tr.appName, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700, color: Colors.white)),
          Text(tr.tagline, style: const TextStyle(color: Color(0xFFDAE6FC))),
        ]),
      ),
    ]);
  }
}