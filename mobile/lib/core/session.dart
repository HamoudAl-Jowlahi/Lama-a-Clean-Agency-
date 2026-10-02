import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';
import 'i18n.dart';

/// واجهة التطبيق حسب المستخدم (CR-3): عميل · فريق زيارات · خادمة بعقود.
enum AppRole { customer, teamMember, housekeeper }

class Session extends ChangeNotifier {
  Session._() {
    Api.I.onUnauthenticated = () => _clear();
  }

  static final Session I = Session._();
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'auth_token';
  static const _langKey = 'lang';
  static const _themeKey = 'theme';

  bool ready = false;
  String? bootError;
  Map<String, dynamic>? user;

  /// لغة الواجهة: ar (افتراضي) أو en — تُحفظ في حساب المستخدم وعلى الجهاز.
  String lang = 'ar';

  /// المظهر: system (حسب الجهاز — الافتراضي) أو light أو dark — يُحفظ على الجهاز.
  String theme = 'system';

  bool get signedIn => user != null;

  AppRole? get role {
    final u = user;
    if (u == null) return null;
    if (u['role']?['value'] == 'customer') return AppRole.customer;
    return u['worker']?['type']?['value'] == 'housekeeper' ? AppRole.housekeeper : AppRole.teamMember;
  }

  String get firstName => (user?['name'] as String? ?? '').split(' ').first;

  Map<String, dynamic>? get team => user?['worker']?['team'] as Map<String, dynamic>?;

  bool get isLeader => team?['is_leader'] == true;

  /// عند فتح التطبيق: استرجاع الجلسة المحفوظة.
  Future<void> restore() async {
    bootError = null;
    try {
      _applyLang(await _storage.read(key: _langKey) ?? lang);
      theme = await _storage.read(key: _themeKey) ?? theme;
      Api.I.token = await _storage.read(key: _tokenKey);
      if (Api.I.token != null) await refreshMe();
    } on ApiException catch (e) {
      if (e.status == 401 || e.status == 403) {
        await _clear(notify: false);
      } else {
        bootError = e.message;
      }
    } catch (_) {
      bootError = tr.errSession;
    }
    ready = true;
    notifyListeners();
  }

  Future<void> refreshMe() async {
    final res = await Api.I.get('/auth/me');
    user = res['data'] as Map<String, dynamic>;
    final serverLang = user?['locale'] as String?;
    if (serverLang != null && serverLang != lang) {
      _applyLang(serverLang);
      await _storage.write(key: _langKey, value: serverLang);
    }
    notifyListeners();
  }

  Future<void> setLang(String code) async {
    if (signedIn) {
      final res = await Api.I.patch('/auth/me', {'locale': code});
      user = res['data'] as Map<String, dynamic>;
    }
    _applyLang(code);
    await _storage.write(key: _langKey, value: code);
    if (signedIn) await refreshMe(); // نصوص الحالات من الخادم باللغة الجديدة
    notifyListeners();
  }

  Future<void> setTheme(String value) async {
    theme = const ['light', 'dark'].contains(value) ? value : 'system';
    await _storage.write(key: _themeKey, value: theme);
    notifyListeners();
  }

  Future<void> login(String phone, String password) async {
    final res = await Api.I.post('/auth/login', {'phone': phone, 'password': password, 'device_name': 'lamaa-app'});
    await _signedIn(res['data']['token'] as String);
  }

  Future<void> register(Map<String, dynamic> data) async {
    final res = await Api.I.post('/auth/register', {...data, 'device_name': 'lamaa-app'});
    await _signedIn(res['data']['token'] as String);
    // الحساب الجديد يُنشأ بالعربية — نطابق لغة الجهاز
    if (user?['locale'] != lang) await setLang(lang);
  }

  Future<void> logout() async {
    try {
      await Api.I.post('/auth/logout');
    } catch (_) {
      // الخروج محلياً حتى لو فشل الطلب
    }
    await _clear();
  }

  void _applyLang(String code) {
    lang = code == 'en' ? 'en' : 'ar';
    Api.I.lang = lang;
  }

  Future<void> _signedIn(String token) async {
    Api.I.token = token;
    await _storage.write(key: _tokenKey, value: token);
    await refreshMe();
  }

  Future<void> _clear({bool notify = true}) async {
    Api.I.token = null;
    user = null;
    await _storage.delete(key: _tokenKey);
    if (notify) notifyListeners();
  }
}
