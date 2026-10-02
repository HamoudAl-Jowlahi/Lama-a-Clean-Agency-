import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:flutter/material.dart';

import 'theme.dart';

/// رمز لمعة (بيت + نجمة لمعان) مرسوم بالكود ومقاس من الشعار المعتمد (لمعه.png — النسخة الثالثة
/// "الرمز وحده")، في مربع 100×100 — يُستخدم في الشعار واللودر وتوليد أيقونة التطبيق وشاشة البداية.
class LamaaMarkPainter extends CustomPainter {
  LamaaMarkPainter({
    this.color = AppColors.brand,
    this.house = 1,
    this.houseTrack = false,
    this.sparkleScale = 1,
    this.sparkleTurn = 0,
    this.windows = 1,
    this.opacity = 1,
  });

  final Color color;

  /// نسبة رسم البيت (0..1) — للأنيميشن. 1 = الشعار كما هو.
  final double house;

  /// رسم البيت باهتاً تحت الجزء المتحرك.
  final bool houseTrack;
  final double sparkleScale;

  /// دوران النجمة بالدورات حول مركزها.
  final double sparkleTurn;

  /// ظهور النوافذ والجدار الأيمن (0..1).
  final double windows;
  final double opacity;

  // ---- المقاسات (من الشعار) ----
  static const _stroke = 14.18; // سماكة خط البيت
  static const _roofEnd = Offset(43.55, 20.14); // طرف السقف المستدير
  static const _corner = Offset(7.09, 46.24); // زاوية السقف مع الجدار الأيسر
  static const _bottom = 99.29;
  static const sparkleCenter = Offset(63.4, 41.84);
  static const _windows = [
    Rect.fromLTRB(28.79, 70.92, 35.89, 78.01),
    Rect.fromLTRB(39.72, 70.92, 46.81, 78.01),
    Rect.fromLTRB(28.79, 81.99, 35.89, 89.08),
    Rect.fromLTRB(39.72, 81.99, 46.81, 89.08),
  ];

  /// السقف + الجدار الأيسر كخط واحد (لأنيميشن الرسم).
  static Path houseLine() => Path()
    ..moveTo(_roofEnd.dx, _roofEnd.dy)
    ..lineTo(_corner.dx, _corner.dy)
    ..lineTo(_corner.dx, _bottom);

  /// الجدار الأيمن: قمته منحنية نحو النجمة، وزاويته السفلية مستديرة.
  static Path rightWall() => Path()
    ..moveTo(75.89, 62.5)
    ..cubicTo(75.89, 57.6, 82.6, 54.61, 90.07, 54.61)
    ..lineTo(90.07, 94.0)
    ..quadraticBezierTo(90.07, _bottom, 84.8, _bottom)
    ..lineTo(77.0, _bottom)
    ..quadraticBezierTo(75.89, _bottom, 75.89, 98.2)
    ..close();

  /// نجمة اللمعان: أربعة أذرع غير متساوية (أعلى 41.8 · يمين 36.6 · أسفل 39.7 · يسار 30.1).
  /// كل جانب منحنى تربيعي نقطة تحكمه في المركز — نفس تقعّر النجمة في الشعار.
  static Path sparklePath() {
    const c = sparkleCenter;
    const t = 41.84, r = 36.6, b = 39.72, l = 30.07;
    return Path()
      ..moveTo(c.dx, c.dy - t)
      ..quadraticBezierTo(c.dx, c.dy, c.dx + r, c.dy)
      ..quadraticBezierTo(c.dx, c.dy, c.dx, c.dy + b)
      ..quadraticBezierTo(c.dx, c.dy, c.dx - l, c.dy)
      ..quadraticBezierTo(c.dx, c.dy, c.dx, c.dy - t)
      ..close();
  }

  /// البيت كاملاً كما في الشعار (السقف بطرفين مستديرين + الجدار الأيسر بزاوية سفلية مستديرة + الجدار الأيمن).
  static void _drawHouse(Canvas canvas, Paint fill, {bool rightWall = true}) {
    canvas.drawLine(
      _roofEnd,
      _corner,
      Paint()
        ..color = fill.color
        ..strokeWidth = _stroke
        ..strokeCap = StrokeCap.round,
    );
    canvas.drawRRect(
      RRect.fromLTRBAndCorners(_corner.dx - _stroke / 2, _corner.dy, _corner.dx + _stroke / 2, _bottom,
          bottomLeft: const Radius.circular(5), bottomRight: const Radius.circular(1.5)),
      fill,
    );
    if (rightWall) canvas.drawPath(LamaaMarkPainter.rightWall(), fill);
  }

  @override
  void paint(Canvas canvas, Size size) {
    final s = size.shortestSide / 100;
    canvas.save();
    canvas.translate((size.width - 100 * s) / 2, (size.height - 100 * s) / 2);
    canvas.scale(s);

    final c = color.withValues(alpha: color.a * opacity);
    final fill = Paint()..color = c;

    if (houseTrack) _drawHouse(canvas, Paint()..color = c.withValues(alpha: c.a * .16));

    if (house >= 1) {
      _drawHouse(canvas, fill, rightWall: false);
    } else if (house > 0) {
      // الرسم التدريجي: من طرف السقف إلى أسفل الجدار
      final stroke = Paint()
        ..color = c
        ..style = PaintingStyle.stroke
        ..strokeWidth = _stroke
        ..strokeJoin = StrokeJoin.round;
      canvas.drawCircle(_roofEnd, _stroke / 2, fill);
      final m = houseLine().computeMetrics().first;
      canvas.drawPath(m.extractPath(0, m.length * house), stroke);
    }

    // الجدار الأيمن والنوافذ يظهران مع `windows` (في الشعار الثابت = 1)
    final w = house >= 1 && windows >= 1 ? 1.0 : windows;
    if (w > 0) {
      final p = Paint()..color = c.withValues(alpha: c.a * w);
      canvas.drawPath(rightWall(), p);
      for (final r in _windows) {
        canvas.drawRRect(RRect.fromRectAndRadius(r, const Radius.circular(.8)), p);
      }
    }

    canvas.save();
    canvas.translate(sparkleCenter.dx, sparkleCenter.dy);
    canvas.rotate(sparkleTurn * 2 * math.pi);
    canvas.scale(sparkleScale);
    canvas.translate(-sparkleCenter.dx, -sparkleCenter.dy);
    canvas.drawPath(sparklePath(), fill);
    canvas.restore();

    canvas.restore();
  }

  @override
  bool shouldRepaint(LamaaMarkPainter old) =>
      old.house != house ||
      old.sparkleScale != sparkleScale ||
      old.sparkleTurn != sparkleTurn ||
      old.windows != windows ||
      old.opacity != opacity ||
      old.color != color;

  /// صورة PNG للرمز (لتوليد أيقونة التطبيق — tool/render_icon_test.dart).
  static Future<ui.Image> render({required double size, required Color mark, Color? background, double padding = 0}) {
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    if (background != null) canvas.drawRect(Offset.zero & Size(size, size), Paint()..color = background);
    canvas.translate(padding, padding);
    LamaaMarkPainter(color: mark).paint(canvas, Size(size - 2 * padding, size - 2 * padding));
    return recorder.endRecording().toImage(size.toInt(), size.toInt());
  }
}

/// الشعار الثابت.
class LamaaMark extends StatelessWidget {
  const LamaaMark({super.key, this.size = 48, this.color});

  final double size;
  final Color? color;

  @override
  Widget build(BuildContext context) =>
      CustomPaint(size: Size.square(size), painter: LamaaMarkPainter(color: color ?? AppColors.blue700));
}

/// اللودر: البيت يُرسم والنجمة تلمع وتتمايل — حلقة متصلة.
class LamaaLoader extends StatefulWidget {
  const LamaaLoader({super.key, this.size = 56, this.color, this.label});

  final double size;
  final Color? color;
  final String? label;

  @override
  State<LamaaLoader> createState() => _LamaaLoaderState();
}

class _LamaaLoaderState extends State<LamaaLoader> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1600))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final color = widget.color ?? AppColors.blue600;
    final mark = AnimatedBuilder(
      animation: _c,
      builder: (_, _) {
        final t = _c.value;
        final draw = Curves.easeInOutCubic.transform((t / .6).clamp(0, 1));
        final fade = t < .85 ? 1.0 : 1 - (t - .85) / .15;
        final pulse = math.sin(t * math.pi);
        return CustomPaint(
          size: Size.square(widget.size),
          painter: LamaaMarkPainter(
            color: color,
            house: draw * fade,
            houseTrack: true,
            sparkleScale: .72 + .34 * pulse,
            sparkleTurn: .025 * math.sin(t * 2 * math.pi), // تمايل خفيف — أذرع النجمة غير متساوية
            windows: ((t - .45) / .2).clamp(0, 1) * fade,
          ),
        );
      },
    );
    if (widget.label == null) return mark;
    return Column(mainAxisSize: MainAxisSize.min, children: [
      mark,
      const SizedBox(height: 14),
      Text(widget.label!, style: TextStyle(color: color.withValues(alpha: .8), fontWeight: FontWeight.w500)),
    ]);
  }
}

/// ثلاث نقاط متتابعة داخل الأزرار أثناء الإرسال.
class LoadingDots extends StatefulWidget {
  const LoadingDots({super.key, this.color = Colors.white});

  final Color color;

  @override
  State<LoadingDots> createState() => _LoadingDotsState();
}

class _LoadingDotsState extends State<LoadingDots> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (_, _) => Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(3, (i) {
          final v = math.sin(((_c.value - i * .18) % 1) * math.pi).clamp(0.0, 1.0);
          return Container(
            width: 8,
            height: 8,
            margin: const EdgeInsets.symmetric(horizontal: 3),
            transform: Matrix4.translationValues(0, -5 * v, 0),
            decoration: BoxDecoration(color: widget.color.withValues(alpha: .45 + .55 * v), shape: BoxShape.circle),
          );
        }),
      ),
    );
  }
}

/// هيكل تحميل (Skeleton) بلمعة متحركة — يظهر بدل القوائم أثناء التحميل الأول.
class SkeletonList extends StatefulWidget {
  const SkeletonList({super.key, this.count = 5, this.header = false});

  final int count;
  final bool header;

  @override
  State<SkeletonList> createState() => _SkeletonListState();
}

class _SkeletonListState extends State<SkeletonList> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1300))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  Widget _bar(double width, double height) => Container(
        width: width,
        height: height,
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(6)),
      );

  Widget _card() => Container(
        margin: EdgeInsets.only(bottom: 10),
        padding: EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface.withValues(alpha: .4),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.surface),
        ),
        child: Row(children: [
          _bar(48, 48),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              _bar(150, 14),
              const SizedBox(height: 8),
              _bar(double.infinity, 10),
              const SizedBox(height: 6),
              _bar(110, 10),
            ]),
          ),
          const SizedBox(width: 12),
          _bar(54, 22),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (context, child) {
        final x = _c.value * 3 - 1.5;
        return ShaderMask(
          blendMode: BlendMode.srcATop,
          shaderCallback: (rect) => LinearGradient(
            begin: Alignment(x - 1, -.3),
            end: Alignment(x + 1, .3),
            colors: AppColors.dark
                ? const [Color(0xFF1B2538), Color(0xFF2A3650), Color(0xFF1B2538)]
                : const [Color(0xFFE6EAF0), Color(0xFFF6F8FB), Color(0xFFE6EAF0)],
            stops: const [.35, .5, .65],
          ).createShader(rect),
          child: child,
        );
      },
      child: ListView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          if (widget.header) ...[_bar(double.infinity, 110), const SizedBox(height: 18), _bar(140, 18), const SizedBox(height: 14)],
          for (var i = 0; i < widget.count; i++) _card(),
        ],
      ),
    );
  }
}

/// طبقة انتظار فوق الشاشة أثناء تنفيذ عملية (إرسال، قبول، إلغاء...).
class BusyOverlay {
  static OverlayEntry show(BuildContext context, {String? label}) {
    final entry = OverlayEntry(
      builder: (_) => Stack(children: [
        ModalBarrier(dismissible: false, color: Color(0x66111827)),
        Center(
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: .85, end: 1),
            duration: Duration(milliseconds: 220),
            curve: Curves.easeOutBack,
            builder: (_, v, child) => Transform.scale(scale: v, child: child),
            child: Material(
              color: AppColors.surfaceHigh,
              elevation: 8,
              borderRadius: BorderRadius.circular(20),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
                child: LamaaLoader(size: 64, label: label),
              ),
            ),
          ),
        ),
      ]),
    );
    Overlay.of(context, rootOverlay: true).insert(entry);
    return entry;
  }
}
