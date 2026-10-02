import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import 'api.dart';
import 'brand.dart';
import 'i18n.dart';
import 'theme.dart';

// ------------------------------------------------------------------ تنسيق

String money(Object? amount) => amount == null ? '—' : tr.money(amount.toString());

String dayLabel(String? ymd) {
  if (ymd == null) return '—';
  final d = DateTime.tryParse(ymd);
  return d == null ? ymd : DateFormat('EEEE d MMMM', tr.localeName).format(d);
}

String shortDay(DateTime d) => DateFormat('EEE', tr.localeName).format(d);

String dateTimeLabel(String? iso) {
  if (iso == null) return '';
  final d = DateTime.tryParse(iso)?.toLocal();
  return d == null ? iso : DateFormat('d MMM · HH:mm', tr.localeName).format(d);
}

String ymd(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

String addressLine(Object? a) {
  if (a is! Map) return '—';
  return [a['label'], a['city'], a['district'], a['street'], a['building'] == null ? null : tr.buildingNo(a['building'].toString())]
      .where((p) => p != null && p.toString().isNotEmpty)
      .join(isEn ? ', ' : '، ');
}

String label(Object? enumJson) => enumJson is Map ? (enumJson['label'] ?? '').toString() : '';

String value(Object? enumJson) => enumJson is Map ? (enumJson['value'] ?? '').toString() : '';

// ------------------------------------------------------------ رسائل وحوار

void toast(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Row(children: [
        Icon(error ? Icons.error_outline : Icons.check_circle_outline, color: Colors.white),
        const SizedBox(width: 10),
        Expanded(child: Text(message)),
      ]),
      backgroundColor: error ? AppColors.danger600 : AppColors.gray900,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ));
}

/// ينفّذ عملية مع طبقة انتظار ويعرض خطأ الـ API إن وُجد. يرجع true عند النجاح.
Future<bool> run(BuildContext context, Future<void> Function() action, {String? success, bool overlay = true}) async {
  final entry = overlay ? BusyOverlay.show(context) : null;
  try {
    await action();
    entry?.remove();
    if (success != null && context.mounted) toast(context, success);
    return true;
  } on ApiException catch (e) {
    entry?.remove();
    if (context.mounted) toast(context, e.message, error: true);
  } catch (e) {
    entry?.remove();
    if (context.mounted) toast(context, tr.errGeneric('$e'), error: true);
  }
  return false;
}

Future<bool> confirm(BuildContext context, String title, String body, {String? ok, bool danger = false}) async {
  final res = await showDialog<bool>(
    context: context,
    builder: (c) => AlertDialog(
      title: Text(title),
      content: Text(body),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c, false), child: Text(tr.back)),
        FilledButton(
          style: FilledButton.styleFrom(
            minimumSize: const Size(96, 44),
            backgroundColor: danger ? AppColors.danger600 : null,
          ),
          onPressed: () => Navigator.pop(c, true),
          child: Text(ok ?? tr.confirm),
        ),
      ],
    ),
  );
  return res == true;
}

/// حوار إدخال نص (سبب الرفض/الإلغاء...).
Future<String?> promptText(BuildContext context, String title, {String hint = '', bool required = false}) async {
  final ctrl = TextEditingController();
  final res = await showDialog<String>(
    context: context,
    builder: (c) => AlertDialog(
      title: Text(title),
      content: TextField(controller: ctrl, maxLines: 3, autofocus: true, decoration: InputDecoration(hintText: hint)),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c), child: Text(tr.back)),
        FilledButton(
          style: FilledButton.styleFrom(minimumSize: const Size(96, 44)),
          onPressed: () {
            if (required && ctrl.text.trim().isEmpty) return;
            Navigator.pop(c, ctrl.text.trim());
          },
          child: Text(tr.send),
        ),
      ],
    ),
  );
  return res;
}

// ---------------------------------------------------------------- عناصر

/// زر رئيسي يعرض نقاط تحميل أثناء الإرسال.
class BusyButton extends StatelessWidget {
  const BusyButton({super.key, required this.label, required this.onPressed, this.busy = false, this.icon});

  final String label;
  final VoidCallback? onPressed;
  final bool busy;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final child = busy
        ? const LoadingDots()
        : Row(mainAxisSize: MainAxisSize.min, children: [
            if (icon != null) ...[Icon(icon), const SizedBox(width: 8)],
            Text(label),
          ]);
    return FilledButton(onPressed: busy ? () {} : onPressed, child: AnimatedSwitcher(duration: const Duration(milliseconds: 200), child: child));
  }
}

/// لون الحالة حسب قيمتها (القيم ثابتة في الـ API).
({Color fg, Color bg}) statusColors(String v) {
  const success = ['completed', 'active', 'approved', 'resolved', 'collected', 'closed', 'accepted'];
  const danger = ['cancelled', 'rejected', 'terminated', 'withdrawn'];
  const warning = ['pending', 'open', 'under_review', 'due'];
  if (success.contains(v)) return (fg: AppColors.success600, bg: AppColors.success50);
  if (danger.contains(v)) return (fg: AppColors.danger600, bg: AppColors.danger50);
  if (warning.contains(v)) return (fg: AppColors.warning600, bg: AppColors.warning50);
  return (fg: AppColors.blue700, bg: AppColors.blue50);
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final Object? status;

  @override
  Widget build(BuildContext context) {
    if (status is! Map) return const SizedBox.shrink();
    final c = statusColors(value(status));
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: c.bg, borderRadius: BorderRadius.circular(99)),
      child: Text(label(status), style: TextStyle(color: c.fg, fontWeight: FontWeight.w600, fontSize: 12.5)),
    );
  }
}

class SectionCard extends StatelessWidget {
  const SectionCard({super.key, this.title, required this.children, this.trailing});

  final String? title;
  final Widget? trailing;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (title != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(children: [
                  Expanded(
                    child: Text(title!, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                  ),
                  ?trailing,
                ]),
              ),
            ...children,
          ],
        ),
      ),
    );
  }
}

class KV extends StatelessWidget {
  const KV(this.k, this.v, {super.key, this.bold = false});

  final String k;
  final String v;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Expanded(child: Text(k, style: const TextStyle(color: AppColors.gray500))),
        const SizedBox(width: 12),
        Flexible(
          flex: 2,
          child: Text(v, textAlign: TextAlign.end, style: TextStyle(fontWeight: bold ? FontWeight.w700 : FontWeight.w500)),
        ),
      ]),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState(this.text, {super.key, this.icon = Icons.inbox_outlined});

  final String text;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(color: AppColors.blue50, shape: BoxShape.circle),
            child: Icon(icon, size: 40, color: AppColors.blue600),
          ),
          const SizedBox(height: 14),
          Text(text, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.gray500)),
        ]),
      ),
    );
  }
}

/// تحميل بيانات مع حالات الانتظار (Skeleton أو لودر لمعة) والخطأ وإعادة المحاولة والسحب للتحديث.
class Loader<T> extends StatefulWidget {
  const Loader({super.key, required this.load, required this.builder, this.skeleton = false, this.header = false});

  final Future<T> Function() load;
  final Widget Function(BuildContext context, T data, Future<void> Function() reload) builder;

  /// القوائم: هيكل بطاقات متحرك. التفاصيل: لودر لمعة.
  final bool skeleton;
  final bool header;

  @override
  State<Loader<T>> createState() => _LoaderState<T>();
}

class _LoaderState<T> extends State<Loader<T>> {
  T? _data;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  Future<void> _reload() async {
    setState(() => _loading = _data == null);
    try {
      final data = await widget.load();
      if (!mounted) return;
      setState(() {
        _data = data;
        _error = null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
      if (_data != null) toast(context, e is ApiException ? e.message : tr.errLoad, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final Widget child;
    if (_loading) {
      child = widget.skeleton
          ? SkeletonList(key: const ValueKey('loading'), header: widget.header)
          : Center(key: const ValueKey('loading'), child: LamaaLoader(label: tr.loading));
    } else if (_data == null) {
      child = Center(
        key: const ValueKey('error'),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(color: AppColors.danger50, shape: BoxShape.circle),
              child: const Icon(Icons.cloud_off_outlined, size: 40, color: AppColors.danger600),
            ),
            const SizedBox(height: 14),
            Text(_error is ApiException ? (_error as ApiException).message : tr.errLoad, textAlign: TextAlign.center),
            const SizedBox(height: 14),
            OutlinedButton.icon(onPressed: _reload, icon: const Icon(Icons.refresh), label: Text(tr.retry)),
          ]),
        ),
      );
    } else {
      child = RefreshIndicator(key: const ValueKey('data'), onRefresh: _reload, child: widget.builder(context, _data as T, _reload));
    }
    return AnimatedSwitcher(duration: const Duration(milliseconds: 250), child: child);
  }
}

/// تقييم بالنجوم.
class StarsInput extends StatelessWidget {
  const StarsInput({super.key, required this.value, required this.onChanged});

  final int value;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(5, (i) {
        final on = i < value;
        return IconButton(
          onPressed: () => onChanged(i + 1),
          icon: Icon(on ? Icons.star_rounded : Icons.star_outline_rounded, size: 34, color: on ? const Color(0xFFF5B301) : AppColors.gray500),
        );
      }),
    );
  }
}

/// السجل الزمني للحالة.
class Timeline extends StatelessWidget {
  const Timeline(this.items, {super.key});

  final List items;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        for (var i = 0; i < items.length; i++)
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Column(children: [
              Container(
                width: 12,
                height: 12,
                margin: const EdgeInsets.only(top: 4),
                decoration: BoxDecoration(
                  color: i == items.length - 1 ? AppColors.blue600 : AppColors.blue100,
                  shape: BoxShape.circle,
                ),
              ),
              if (i < items.length - 1) Container(width: 2, height: 34, color: AppColors.blue100),
            ]),
            const SizedBox(width: 12),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(label(items[i]['status']), style: const TextStyle(fontWeight: FontWeight.w600)),
                  Text(
                    [dateTimeLabel(items[i]['at']), if (items[i]['note'] != null) items[i]['note']].join(' — '),
                    style: const TextStyle(color: AppColors.gray500, fontSize: 12.5),
                  ),
                ]),
              ),
            ),
          ]),
      ],
    );
  }
}

/// اختيار مرفقات (صور حتى 5 — الخادم يقبل jpg/png/webp حتى 5MB).
class AttachmentsPicker extends StatelessWidget {
  const AttachmentsPicker({super.key, required this.files, required this.onChanged, this.max = 5});

  final List<XFile> files;
  final ValueChanged<List<XFile>> onChanged;
  final int max;

  Future<void> _pick(BuildContext context, ImageSource source) async {
    final picker = ImagePicker();
    try {
      final picked = source == ImageSource.camera
          ? [?await picker.pickImage(source: source, imageQuality: 75, maxWidth: 1920)]
          : await picker.pickMultiImage(imageQuality: 75, maxWidth: 1920, limit: max - files.length);
      onChanged([...files, ...picked].take(max).toList());
    } catch (_) {
      if (context.mounted) toast(context, tr.errPick, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Text(tr.attachmentsTitle(files.length, max), style: const TextStyle(fontWeight: FontWeight.w700)),
      const SizedBox(height: 8),
      Wrap(spacing: 8, runSpacing: 8, children: [
        for (final f in files)
          Chip(
            avatar: const Icon(Icons.image_outlined, size: 18),
            label: SizedBox(width: 90, child: Text(f.name, overflow: TextOverflow.ellipsis)),
            onDeleted: () => onChanged([...files]..remove(f)),
          ),
        if (files.length < max) ...[
          ActionChip(avatar: const Icon(Icons.photo_library_outlined, size: 18), label: Text(tr.gallery), onPressed: () => _pick(context, ImageSource.gallery)),
          ActionChip(avatar: const Icon(Icons.photo_camera_outlined, size: 18), label: Text(tr.camera), onPressed: () => _pick(context, ImageSource.camera)),
        ],
      ]),
    ]);
  }
}

/// سهم الفترة (من ← إلى) حسب اتجاه اللغة.
String get arrow => isEn ? '→' : '←';
