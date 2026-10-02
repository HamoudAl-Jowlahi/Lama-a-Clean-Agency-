import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:flutter/material.dart';

import 'theme.dart';

/// رمز لمعة (بيت + نجمة لمعان) مرسوم بالكود من design/assets/brand/lamaa-mark.svg
/// (viewBox 64×64) — يُستخدم في الشعار واللودر وتوليد أيقونة التطبيق.
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

  /// نسبة رسم خط البيت (0..1) — للأنيميشن.
  final double house;

  /// رسم البيت باهتاً تحت الجزء المتحرك.
  final bool houseTrack;
  final double sparkleScale;

  /// دوران النجمة بالدورات (0.25 = ربع دورة — النجمة متماثلة فتبدو متصلة).
  final double sparkleTurn;
  final double windows;
  final double opacity;

  static Path housePath() => Path()
    ..moveTo(31, 11.5)
    ..lineTo(10.5, 28)
    ..lineTo(10.5, 52)
    ..lineTo(44, 52)
    ..lineTo(44, 41);

  static Path sparklePath() => Path()
    ..moveTo(45, 3)
    ..cubicTo(46.6, 17.5, 49.2, 22.4, 62, 24)
    ..cubicTo(49.2, 25.6, 46.6, 30.5, 45, 45)
    ..cubicTo(43.4, 30.5, 40.8, 25.6, 32, 24)
    ..cubicTo(40.8, 22.4, 43.4, 17.5, 45, 3)
    ..close();

  @override
  void paint(Canvas canvas, Size size) {
    final s = size.shortestSide / 64;
    canvas.save();
    canvas.translate((size.width - 64 * s) / 2, (size.height - 64 * s) / 2);
    canvas.scale(s);

    final c = color.withValues(alpha: color.a * opacity);
    final stroke = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 7.5
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final housePath = LamaaMarkPainter.housePath();
    if (houseTrack) canvas.drawPath(housePath, stroke..color = c.withValues(alpha: c.a * .18));
    stroke.color = c;
    if (house >= 1) {
      canvas.drawPath(housePath, stroke);
    } else if (house > 0) {
      final metrics = housePath.computeMetrics().toList();
      final total = metrics.fold<double>(0, (a, m) => a + m.length);
      var remain = total * house;
      for (final m in metrics) {
        if (remain <= 0) break;
        canvas.drawPath(m.extractPath(0, math.min(remain, m.length)), stroke);
        remain -= m.length;
      }
    }

    final fill = Paint()..color = c;
    canvas.save();
    canvas.translate(45, 24);
    canvas.rotate(sparkleTurn * 2 * math.pi);
    canvas.scale(sparkleScale);
    canvas.translate(-45, -24);
    canvas.drawPath(LamaaMarkPainter.sparklePath(), fill);
    canvas.restore();

    if (windows > 0) {
      final w = Paint()..color = c.withValues(alpha: c.a * windows);
      for (final o in const [Offset(19, 35), Offset(25.5, 35), Offset(19, 41.5), Offset(25.5, 41.5)]) {
        canvas.drawRRect(RRect.fromRectAndRadius(o & const Size(5, 5), const Radius.circular(.8)), w);
      }
    }
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

/// اللودر: البيت يُرسم والنجمة تلمع وتدور ربع دورة — حلقة متصلة.
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
            sparkleTurn: Curves.easeInOutBack.transform(t) * .25,
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
