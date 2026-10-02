import 'package:flutter/material.dart';

/// مفاتيح التنقل والرسائل على مستوى التطبيق (للإشعارات التي تصل من خارج أي شاشة).
/// يُستبدلان عند تغيير اللغة أو المظهر — حتى لا يحتفظ Flutter بالشاشات القديمة.
var navigatorKey = GlobalKey<NavigatorState>();
var messengerKey = GlobalKey<ScaffoldMessengerState>();
