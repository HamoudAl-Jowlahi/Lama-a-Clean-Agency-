import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

import '../features/shared/notifications_screen.dart';
import 'api.dart';
import 'i18n.dart';
import 'nav.dart';
import 'session.dart';
import 'theme.dart';

/// إشعارات الجوال (FCM): تسجيل الجهاز في الخادم بعد الدخول، عرض الإشعار والتطبيق مفتوح،
/// وفتح الطلب/العقد/الشكوى عند الضغط على الإشعار. الخادم: POST/DELETE /device-tokens (docs/05).
class Push {
  Push._();

  static final Push I = Push._();

  bool _ready = false;
  String? _token;
  RemoteMessage? _pending; // إشعار فتح التطبيق وهو مغلق — يُعرض بعد تحميل الجلسة

  Future<void> init() async {
    try {
      await Firebase.initializeApp();
    } catch (_) {
      return; // بدون إعداد Firebase (مثلاً نسخة تطوير بلا google-services.json) — التطبيق يعمل بدون Push
    }
    _ready = true;
    FirebaseMessaging.onMessage.listen(_showInApp);
    FirebaseMessaging.onMessageOpenedApp.listen(_open);
    FirebaseMessaging.instance.onTokenRefresh.listen((t) {
      _token = t;
      _register();
    });
    _pending = await FirebaseMessaging.instance.getInitialMessage();
  }

  /// بعد تسجيل الدخول أو استرجاع الجلسة.
  Future<void> onSignedIn() async {
    if (!_ready) return;
    try {
      await FirebaseMessaging.instance.requestPermission();
      _token = await FirebaseMessaging.instance.getToken();
      await _register();
    } catch (_) {
      // الإشعارات اختيارية — لا نوقف الدخول بسببها
    }
    final pending = _pending;
    if (pending != null) {
      _pending = null;
      WidgetsBinding.instance.addPostFrameCallback((_) => _open(pending));
    }
  }

  /// قبل الخروج (والجلسة ما زالت صالحة) — حتى لا تصل إشعارات الحساب لهذا الجهاز.
  Future<void> onSigningOut() async {
    final token = _token;
    if (!_ready || token == null) return;
    try {
      await Api.I.dio.delete('/device-tokens', data: {'token': token});
    } catch (_) {}
  }

  Future<void> _register() async {
    final token = _token;
    if (token == null || !Session.I.signedIn) return;
    try {
      await Api.I.post('/device-tokens', {'token': token, 'platform': Platform.isIOS ? 'ios' : 'android'});
    } catch (_) {}
  }

  /// التطبيق مفتوح: النظام لا يعرض الإشعار، فنعرضه داخل التطبيق.
  void _showInApp(RemoteMessage m) {
    final n = m.notification;
    if (n == null) return;
    messengerKey.currentState
      ?..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.brand,
        duration: const Duration(seconds: 6),
        content: Row(children: [
          const Icon(Icons.notifications_active_outlined, color: Colors.white),
          const SizedBox(width: 10),
          Expanded(
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (n.title != null) Text(n.title!, style: const TextStyle(fontWeight: FontWeight.w700)),
              if (n.body != null) Text(n.body!),
            ]),
          ),
        ]),
        action: _screenFor(m) == null ? null : SnackBarAction(label: tr.view, textColor: Colors.white, onPressed: () => _open(m)),
      ));
  }

  void _open(RemoteMessage m) {
    final page = _screenFor(m);
    if (page != null) navigatorKey.currentState?.push(MaterialPageRoute(builder: (_) => page));
  }

  Widget? _screenFor(RemoteMessage m) => subjectScreen({
        'type': m.data['subject_type'],
        'id': int.tryParse(m.data['subject_id']?.toString() ?? ''),
      });
}
