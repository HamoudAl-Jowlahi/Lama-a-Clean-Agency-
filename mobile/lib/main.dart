import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'core/brand.dart';
import 'core/i18n.dart';
import 'core/session.dart';
import 'core/theme.dart';
import 'features/auth/login_screen.dart';
import 'features/customer/customer_shell.dart';
import 'features/worker/housekeeper_shell.dart';
import 'features/worker/team_shell.dart';

final navigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting();
  Session.I.restore();
  runApp(const LamaaApp());
}

class LamaaApp extends StatefulWidget {
  const LamaaApp({super.key});

  @override
  State<LamaaApp> createState() => _LamaaAppState();
}

class _LamaaAppState extends State<LamaaApp> {
  String _lang = Session.I.lang;

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
    if (Session.I.lang != _lang) setState(() => _lang = Session.I.lang);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      // مفتاح اللغة: تغييرها يعيد بناء التطبيق كاملاً بالنصوص والاتجاه الجديدين
      key: ValueKey(_lang),
      onGenerateTitle: (_) => tr.appName,
      debugShowCheckedModeBanner: false,
      navigatorKey: navigatorKey,
      theme: buildTheme(),
      locale: Locale(_lang),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
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
  AppRole? _lastRole = Session.I.role;

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
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final s = Session.I;
    final Widget page;
    if (!s.ready) {
      page = const SplashScreen();
    } else if (s.bootError != null && !s.signedIn) {
      page = _BootError(s.bootError!);
    } else {
      page = switch (s.role) {
        AppRole.customer => const CustomerShell(),
        AppRole.teamMember => const TeamShell(),
        AppRole.housekeeper => const HousekeeperShell(),
        null => const LoginScreen(),
      };
    }
    return AnimatedSwitcher(duration: const Duration(milliseconds: 400), child: KeyedSubtree(key: ValueKey(page.runtimeType), child: page));
  }
}

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.blue700,
      body: Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const LamaaLoader(size: 96, color: Colors.white),
          const SizedBox(height: 18),
          Text(tr.appName, style: Theme.of(context).textTheme.displaySmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
          Text(tr.tagline, style: const TextStyle(color: AppColors.blue100)),
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
            OutlinedButton(onPressed: () => Session.I.restore(), child: Text(tr.retry)),
          ]),
        ),
      ),
    );
  }
}
