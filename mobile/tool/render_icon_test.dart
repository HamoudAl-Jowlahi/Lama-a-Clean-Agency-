// يولّد صور أيقونة التطبيق وشاشة البداية من رمز لمعة المرسوم بالكود (lib/core/brand.dart).
// التشغيل: flutter test tool/render_icon_test.dart  ثم  dart run flutter_launcher_icons  و  dart run flutter_native_splash:create
import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lamaa/core/brand.dart';
import 'package:lamaa/core/theme.dart';

Future<void> _save(String path, ui.Image image) async {
  final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
  File(path)
    ..createSync(recursive: true)
    ..writeAsBytesSync(bytes!.buffer.asUint8List());
}

void main() {
  test('render brand assets', () async {
    // أيقونة كاملة (iOS + Android القديم): خلفية زرقاء ورمز أبيض
    await _save('assets/brand/icon.png', await LamaaMarkPainter.render(size: 1024, mark: Colors.white, background: AppColors.blue700, padding: 190));
    // أيقونة Android التكيفية: الرمز داخل المنطقة الآمنة (66%)
    await _save('assets/brand/icon_foreground.png', await LamaaMarkPainter.render(size: 1024, mark: Colors.white, padding: 300));
    // شاشة البداية
    await _save('assets/brand/splash.png', await LamaaMarkPainter.render(size: 480, mark: Colors.white, padding: 60));
    await _save('assets/brand/splash_android12.png', await LamaaMarkPainter.render(size: 1152, mark: Colors.white, padding: 400));
  });
}
