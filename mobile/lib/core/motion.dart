import 'dart:math' as math;

import 'package:flutter/material.dart';

import 'brand.dart';
import 'theme.dart';

/// ظهور متدرّج: انزلاق خفيف للأعلى مع تلاشٍ — `index` يؤخر كل عنصر قليلاً عن سابقه.
class Appear extends StatefulWidget {
  const Appear({super.key, required this.child, this.index = 0, this.offset = 18});

  final Widget child;
  final int index;
  final double offset;

  @override
  State<Appear> createState() => _AppearState();
}

class _AppearState extends State<Appear> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 520));
  late final _curve = CurvedAnimation(parent: _c, curve: Curves.easeOutCubic);

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration(milliseconds: 45 * math.min(widget.index, 10)), () {
      if (mounted) _c.forward();
    });
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _curve,
      builder: (_, child) => Opacity(
        opacity: _curve.value,
        child: Transform.translate(offset: Offset(0, widget.offset * (1 - _curve.value)), child: child),
      ),
      child: widget.child,
    );
  }
}

/// بطاقة تتفاعل مع اللمس: تنكمش قليلاً عند الضغط وتعود بنعومة.
class Pressable extends StatefulWidget {
  const Pressable({super.key, required this.child, this.onTap, this.scale = .97});

  final Widget child;
  final VoidCallback? onTap;
  final double scale;

  @override
  State<Pressable> createState() => _PressableState();
}

class _PressableState extends State<Pressable> {
  bool _down = false;

  void _set(bool v) {
    if (widget.onTap != null && _down != v) setState(() => _down = v);
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: (_) => _set(true),
      onTapUp: (_) => _set(false),
      onTapCancel: () => _set(false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _down ? widget.scale : 1,
        duration: const Duration(milliseconds: 140),
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

/// بطاقة سطح بحواف ناعمة وظل خفيف (في الوضع الفاتح) — بديل أجمل لـ Card.
class SoftCard extends StatelessWidget {
  const SoftCard({super.key, required this.child, this.onTap, this.padding = const EdgeInsets.all(16), this.color, this.border = true});

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final Color? color;
  final bool border;

  @override
  Widget build(BuildContext context) {
    final card = Container(
      padding: padding,
      decoration: BoxDecoration(
        color: color ?? AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: border ? Border.all(color: AppColors.gray200.withValues(alpha: AppColors.dark ? 1 : .6)) : null,
        boxShadow: AppColors.softShadow,
      ),
      child: child,
    );
    return onTap == null ? card : Pressable(onTap: onTap, child: card);
  }
}

/// لمعات متحركة تطفو فوق خلفية الهوية (البطاقة الرئيسية، الدخول، شاشة البداية).
class SparkleField extends StatefulWidget {
  const SparkleField({super.key, this.count = 7, this.color = Colors.white, this.maxSize = 22});

  final int count;
  final Color color;
  final double maxSize;

  @override
  State<SparkleField> createState() => _SparkleFieldState();
}

class _SparkleFieldState extends State<SparkleField> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(seconds: 6))..repeat();
  late final _seeds = List.generate(widget.count, (i) {
    final r = math.Random(i * 97 + 13);
    return (x: r.nextDouble(), y: r.nextDouble(), s: .35 + r.nextDouble() * .65, phase: r.nextDouble());
  });

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: AnimatedBuilder(
        animation: _c,
        builder: (_, _) => CustomPaint(
          size: Size.infinite,
          painter: _SparklePainter(_seeds, _c.value, widget.color, widget.maxSize),
        ),
      ),
    );
  }
}

class _SparklePainter extends CustomPainter {
  _SparklePainter(this.seeds, this.t, this.color, this.maxSize);

  final List<({double x, double y, double s, double phase})> seeds;
  final double t;
  final Color color;
  final double maxSize;

  @override
  void paint(Canvas canvas, Size size) {
    final star = LamaaMarkPainter.sparklePath();
    for (final s in seeds) {
      final p = (t + s.phase) % 1;
      final twinkle = math.sin(p * math.pi); // 0 → 1 → 0
      final dy = -10 * p;
      final scale = (maxSize / 42) * s.s * (.6 + .4 * twinkle);
      canvas.save();
      canvas.translate(s.x * size.width, s.y * size.height + dy);
      canvas.rotate(p * math.pi / 2);
      canvas.scale(scale);
      canvas.translate(-45, -24); // مركز النجمة في viewBox الشعار
      canvas.drawPath(star, Paint()..color = color.withValues(alpha: .08 + .32 * twinkle));
      canvas.restore();
    }
  }

  @override
  bool shouldRepaint(_SparklePainter old) => old.t != t;
}

/// علامة نجاح متحركة: دائرة تكبر ثم ترسم علامة الصح.
class SuccessCheck extends StatelessWidget {
  const SuccessCheck({super.key, this.size = 72});

  final double size;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 900),
      builder: (_, t, _) => CustomPaint(size: Size.square(size), painter: _CheckPainter(t, AppColors.success600)),
    );
  }
}

class _CheckPainter extends CustomPainter {
  _CheckPainter(this.t, this.color);

  final double t;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final c = size.center(Offset.zero);
    final r = size.width / 2;
    final grow = Curves.elasticOut.transform((t / .55).clamp(0, 1));
    canvas.drawCircle(c, r * grow, Paint()..color = color.withValues(alpha: .15));
    canvas.drawCircle(c, r * .72 * grow, Paint()..color = color);
    final draw = Curves.easeOutCubic.transform(((t - .45) / .55).clamp(0, 1));
    if (draw <= 0) return;
    final path = Path()
      ..moveTo(c.dx - r * .3, c.dy + r * .02)
      ..lineTo(c.dx - r * .08, c.dy + r * .24)
      ..lineTo(c.dx + r * .32, c.dy - r * .2);
    final m = path.computeMetrics().first;
    canvas.drawPath(
      m.extractPath(0, m.length * draw),
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.stroke
        ..strokeWidth = r * .12
        ..strokeCap = StrokeCap.round
        ..strokeJoin = StrokeJoin.round,
    );
  }

  @override
  bool shouldRepaint(_CheckPainter old) => old.t != t;
}

/// أيقونة داخل مربع ملوّن ناعم.
class IconTile extends StatelessWidget {
  const IconTile(this.icon, {super.key, this.color, this.size = 48});

  final IconData icon;
  final Color? color;
  final double size;

  @override
  Widget build(BuildContext context) {
    final c = color ?? AppColors.blue600;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: AlignmentDirectional.topStart,
          end: AlignmentDirectional.bottomEnd,
          colors: [c.withValues(alpha: .18), c.withValues(alpha: .08)],
        ),
        borderRadius: BorderRadius.circular(size * .3),
      ),
      child: Icon(icon, color: c, size: size * .5),
    );
  }
}

/// أيقونة الخدمة حسب الحقل `icon` في الكتالوج.
IconData serviceIcon(String? key) => switch (key) {
      'home2' || 'home' => Icons.cottage_outlined,
      'sofa' => Icons.weekend_outlined,
      'window' => Icons.window_outlined,
      'building' => Icons.apartment_outlined,
      _ => Icons.cleaning_services_outlined,
    };

/// لون مميز لكل خدمة (يتكرر بالدور).
Color serviceColor(int i) => [
      AppColors.blue600,
      const Color(0xFF0E9F8E),
      const Color(0xFF7C5CE0),
      const Color(0xFFE07A2E),
    ][i % 4];
