import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'core/session.dart';
import 'core/theme.dart';
import 'features/auth/login_screen.dart';
import 'features/customer/customer_shell.dart';
import 'features/worker/housekeeper_shell.dart';
import 'features/worker/team_shell.dart';

final navigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('ar');
  Session.I.restore();
  runApp(const LamaaApp());
}

class LamaaApp extends StatelessWidget {
  const LamaaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'لمعة',
      debugShowCheckedModeBanner: false,
      navigatorKey: navigatorKey,
      theme: buildTheme(),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: const RootGate(),
    );
  }
}

/// يختار الواجهة حسب الجلسة: الدخول · العميل · فريق الزيارات · الخادمة.
class RootGate extends StatefulWidget {
  const RootGate({super.key});

  @override
  State<RootGate> createState() => _RootGateState();
}

class _RootGateState extends State<RootGate> {
  AppRole? _lastRole;

  @override
  void initState() {
    super.initState();
    Session.I.addListener(_onSession);
  }

  @override
  void dispose() {
    Session.I.removeListener(_onSession);
    super.dispose();
  }

  void _onSession() {
    // عند تغيّر المستخدم (دخول/خروج) نغلق الشاشات المفتوحة فوق الواجهة
    if (Session.I.role != _lastRole) navigatorKey.currentState?.popUntil((r) => r.isFirst);
    _lastRole = Session.I.role;
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final s = Session.I;
    if (!s.ready) return const _Splash();
    if (s.bootError != null && !s.signedIn) return _BootError(s.bootError!);
    return switch (s.role) {
      AppRole.customer => const CustomerShell(),
      AppRole.teamMember => const TeamShell(),
      AppRole.housekeeper => const HousekeeperShell(),
      null => const LoginScreen(),
    };
  }
}

class _Splash extends StatelessWidget {
  const _Splash();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.blue700,
      body: Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.auto_awesome, color: Colors.white, size: 64),
          const SizedBox(height: 12),
          Text('لمعة', style: Theme.of(context).textTheme.displaySmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700)),
        ]),
      ),
    );
  }
}

class _BootError extends StatelessWidget {
  const _BootError(this.message);

  final String message;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.cloud_off_outlined, size: 56, color: AppColors.gray500),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: () => Session.I.restore(), child: const Text('إعادة المحاولة')),
          ]),
        ),
      ),
    );
  }
}
