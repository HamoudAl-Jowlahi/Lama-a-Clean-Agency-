import 'dart:math';

import 'package:dio/dio.dart';

/// عنوان الـ API — يُغيَّر عند البناء:
/// flutter build apk --dart-define=API_BASE=https://api.example.com/api/v1
/// الافتراضي للتطوير على جهاز موصول بـ `adb reverse tcp:8000 tcp:8000`.
const apiBase = String.fromEnvironment('API_BASE', defaultValue: 'http://127.0.0.1:8000/api/v1');

/// خطأ موحّد من الـ API: `{message, code, errors?}` (docs/03_phase3_api.md).
class ApiException implements Exception {
  ApiException(this.message, {this.code, this.status, this.errors});

  final String message;
  final String? code;
  final int? status;
  final Map<String, dynamic>? errors;

  /// أول رسالة تحقق لحقل معيّن (لعرضها تحت الحقل).
  String? field(String name) {
    final list = errors?[name];
    return list is List && list.isNotEmpty ? list.first.toString() : null;
  }

  @override
  String toString() => message;
}

class Api {
  Api._() {
    dio = Dio(BaseOptions(
      baseUrl: apiBase,
      connectTimeout: const Duration(seconds: 12),
      receiveTimeout: const Duration(seconds: 20),
      headers: {'Accept': 'application/json', 'Accept-Language': 'ar'},
    ));
    dio.interceptors.add(InterceptorsWrapper(onRequest: (options, handler) {
      if (token != null) options.headers['Authorization'] = 'Bearer $token';
      handler.next(options);
    }));
  }

  static final Api I = Api._();

  late final Dio dio;
  String? token;

  /// يُستدعى عند 401 (انتهاء الجلسة أو إيقاف الحساب) — تضبطه Session.
  void Function()? onUnauthenticated;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) =>
      _send(() => dio.get(path, queryParameters: query));

  Future<Map<String, dynamic>> post(String path, [Object? data, Map<String, String>? headers]) =>
      _send(() => dio.post(path, data: data, options: Options(headers: headers)));

  Future<Map<String, dynamic>> patch(String path, Object? data) => _send(() => dio.patch(path, data: data));

  Future<Map<String, dynamic>> put(String path, Object? data) => _send(() => dio.put(path, data: data));

  Future<Map<String, dynamic>> delete(String path) => _send(() => dio.delete(path));

  /// مفتاح منع التكرار لطلبات الإنشاء (Idempotency-Key).
  static String idempotencyKey() {
    final r = Random.secure();
    String hex(int n) => List.generate(n, (_) => r.nextInt(16).toRadixString(16)).join();
    return '${hex(8)}-${hex(4)}-4${hex(3)}-a${hex(3)}-${hex(12)}';
  }

  Future<Map<String, dynamic>> _send(Future<Response> Function() call) async {
    try {
      final res = await call();
      final body = res.data;
      return body is Map<String, dynamic> ? body : <String, dynamic>{};
    } on DioException catch (e) {
      final res = e.response;
      final body = res?.data;
      if (res?.statusCode == 401 && token != null) onUnauthenticated?.call();
      if (body is Map && body['message'] != null) {
        throw ApiException(
          body['message'].toString(),
          code: body['code']?.toString(),
          status: res?.statusCode,
          errors: body['errors'] is Map ? Map<String, dynamic>.from(body['errors']) : null,
        );
      }
      if (res == null) {
        throw ApiException('تعذر الاتصال بالخادم — تحقق من الإنترنت ثم حاول مجدداً.', code: 'NETWORK');
      }
      throw ApiException('حدث خطأ غير متوقع (${res.statusCode}).', status: res.statusCode);
    }
  }
}
