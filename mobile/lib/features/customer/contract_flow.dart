import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'addresses.dart';
import 'contract_detail.dart';

/// باقات العقود (استئجار عاملة منزلية — CR-2).
class ContractPlansScreen extends StatelessWidget {
  const ContractPlansScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('استئجار عاملة بعقد')),
      body: Loader<Map<String, dynamic>>(
        load: () => Api.I.get('/contract-plans'),
        builder: (context, res, reload) {
          final plans = res['data'] as List;
          final meta = res['meta'] as Map? ?? {};
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const Text('اختر الباقة المناسبة. يمكنك طلب استبدال العاملة أو إنهاء العقد من التطبيق في أي وقت.',
                  style: TextStyle(color: AppColors.gray500)),
              const SizedBox(height: 12),
              if (plans.isEmpty) const EmptyState('لا توجد باقات متاحة حالياً'),
              for (final p in plans) ...[
                Card(
                  clipBehavior: Clip.antiAlias,
                  child: InkWell(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ContractRequestScreen(plan: p, meta: meta))),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Row(children: [
                          Expanded(child: Text(p['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 17))),
                          Text('${money(p['monthly_price'])} / شهر', style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.blue700)),
                        ]),
                        if (p['description'] != null) ...[
                          const SizedBox(height: 4),
                          Text(p['description'], style: const TextStyle(color: AppColors.gray500)),
                        ],
                        const SizedBox(height: 10),
                        Wrap(spacing: 8, children: [
                          Chip(avatar: const Icon(Icons.calendar_month, size: 18), label: Text('${p['work_days_per_week']} أيام/أسبوع')),
                          Chip(avatar: const Icon(Icons.schedule, size: 18), label: Text('${p['hours_per_day']} ساعات/يوم')),
                        ]),
                      ]),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
              ],
            ],
          );
        },
      ),
    );
  }
}

class ContractRequestScreen extends StatefulWidget {
  const ContractRequestScreen({super.key, required this.plan, required this.meta});

  final Map plan;
  final Map meta;

  @override
  State<ContractRequestScreen> createState() => _ContractRequestScreenState();
}

class _ContractRequestScreenState extends State<ContractRequestScreen> {
  final _notes = TextEditingController();
  final _idempotencyKey = Api.idempotencyKey();
  late int _months = widget.plan['min_months'] as int? ?? 1;
  late DateTime _start = _earliest;
  List _addresses = [];
  int? _addressId;
  bool _terms = false;
  Map? _quote;
  bool _busy = false;

  DateTime get _earliest =>
      DateUtils.dateOnly(DateTime.now()).add(Duration(days: (widget.meta['min_start_lead_days'] as int?) ?? 1));

  @override
  void initState() {
    super.initState();
    _loadAddresses();
    _requote();
  }

  Future<void> _loadAddresses({int? select}) async {
    try {
      final list = (await Api.I.get('/addresses'))['data'] as List;
      setState(() {
        _addresses = list;
        _addressId = select ?? _addressId ?? (list.isNotEmpty ? list.first['id'] as int : null);
      });
    } catch (_) {}
  }

  Future<void> _requote() async {
    try {
      final res = await Api.I.post('/contracts/quote', {'plan_id': widget.plan['id'], 'start_date': ymd(_start), 'months': _months});
      if (mounted) setState(() => _quote = res['data'] as Map);
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _quote = null);
        toast(context, e.message, error: true);
      }
    }
  }

  Future<void> _submit() async {
    setState(() => _busy = true);
    await run(context, () async {
      final res = await Api.I.post('/contracts', {
        'plan_id': widget.plan['id'],
        'address_id': _addressId,
        'start_date': ymd(_start),
        'months': _months,
        'accept_terms': true,
        'terms_version': widget.meta['terms_version'],
        if (_notes.text.trim().isNotEmpty) 'customer_notes': _notes.text.trim(),
      }, {'Idempotency-Key': _idempotencyKey});
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => ContractDetailScreen(id: res['data']['id'] as int, justCreated: true)),
      );
    });
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.plan;
    final min = p['min_months'] as int? ?? 1;
    final max = p['max_months'] as int? ?? 12;
    return Scaffold(
      appBar: AppBar(title: Text(p['name'] ?? 'طلب عقد')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SectionCard(title: 'المدة والبداية', children: [
            DropdownButtonFormField<int>(
              initialValue: _months,
              decoration: const InputDecoration(labelText: 'مدة العقد'),
              items: [for (var m = min; m <= max; m++) DropdownMenuItem(value: m, child: Text(m == 1 ? 'شهر واحد' : '$m أشهر'))],
              onChanged: (v) {
                setState(() => _months = v!);
                _requote();
              },
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () async {
                final d = await showDatePicker(
                  context: context,
                  initialDate: _start,
                  firstDate: _earliest,
                  lastDate: _earliest.add(const Duration(days: 90)),
                );
                if (d != null) {
                  setState(() => _start = d);
                  _requote();
                }
              },
              icon: const Icon(Icons.event),
              label: Text('تاريخ البدء: ${dayLabel(ymd(_start))}'),
            ),
          ]),
          const SizedBox(height: 12),
          AddressPicker(
            title: 'عنوان العمل',
            addresses: _addresses,
            selected: _addressId,
            onSelected: (v) => setState(() => _addressId = v),
            onCreated: (id) => _loadAddresses(select: id),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'ملاحظات (اختياري)', children: [
            TextField(controller: _notes, maxLines: 2, decoration: const InputDecoration(hintText: 'مثال: يفضّل من يتحدث العربية')),
          ]),
          const SizedBox(height: 12),
          SectionCard(title: 'الملخص', children: [
            if (_quote != null) ...[
              KV('من', dayLabel(_quote!['start_date'])),
              KV('إلى', dayLabel(_quote!['end_date'])),
              KV('الشهري', money(_quote!['monthly_price'])),
              KV('الإجمالي', money(_quote!['total']), bold: true),
            ],
            const KV('الدفع', 'نقداً — دفعة كل شهر'),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _terms,
              onChanged: (v) => setState(() => _terms = v ?? false),
              controlAffinity: ListTileControlAffinity.leading,
              title: Text('أوافق على شروط العقد (الإصدار ${widget.meta['terms_version'] ?? ''})'),
            ),
          ]),
          const SizedBox(height: 90),
        ],
      ),
      bottomSheet: SafeArea(
        child: Container(
          color: Colors.white,
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: FilledButton(
            onPressed: _terms && _addressId != null && _quote != null && !_busy ? _submit : null,
            child: Text(_busy ? 'جارٍ الإرسال…' : 'إرسال طلب العقد'),
          ),
        ),
      ),
    );
  }
}
