import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/brand.dart';
import '../../core/i18n.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'addresses.dart';
import 'booking_detail.dart';

/// طلب زيارة تنظيف: الخيار ← العنوان ← اليوم والوقت ← الملخص (الدفع نقداً).
class BookingFlowScreen extends StatefulWidget {
  const BookingFlowScreen({super.key, required this.service});

  final Map service;

  @override
  State<BookingFlowScreen> createState() => _BookingFlowScreenState();
}

class _BookingFlowScreenState extends State<BookingFlowScreen> {
  final _notes = TextEditingController();
  final _idempotencyKey = Api.idempotencyKey();

  Map? _price;
  int _qty = 1;
  List _addresses = [];
  int? _addressId;
  DateTime? _date;
  List _slots = [];
  bool _slotsLoading = false;
  String? _time;
  Map? _quote;
  bool _busy = false;

  List get _prices => widget.service['prices'] as List? ?? [];

  @override
  void initState() {
    super.initState();
    if (_prices.isNotEmpty) _price = _prices.first;
    _loadAddresses();
    _requote();
  }

  Future<void> _loadAddresses({int? select}) async {
    try {
      final res = await Api.I.get('/addresses');
      final list = res['data'] as List;
      setState(() {
        _addresses = list;
        _addressId = select ?? _addressId ?? (list.isNotEmpty ? list.first['id'] as int : null);
      });
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    }
  }

  Future<void> _requote() async {
    final p = _price;
    if (p == null) return;
    try {
      final res = await Api.I.post('/bookings/quote', {'service_price_id': p['id'], 'quantity': _qty});
      if (mounted) setState(() => _quote = res['data'] as Map);
    } catch (_) {
      if (mounted) setState(() => _quote = null);
    }
  }

  Future<void> _pickDate(DateTime d) async {
    setState(() {
      _date = d;
      _time = null;
      _slotsLoading = true;
      _slots = [];
    });
    try {
      final res = await Api.I.get('/availability', query: {'date': ymd(d)});
      if (mounted) setState(() => _slots = res['data']['slots'] as List);
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _slotsLoading = false);
    }
  }

  Future<void> _submit() async {
    setState(() => _busy = true);
    await run(context, () async {
      final res = await Api.I.post('/bookings', {
        'service_price_id': _price!['id'],
        'quantity': _qty,
        'address_id': _addressId,
        'scheduled_date': ymd(_date!),
        'scheduled_time': _time,
        if (_notes.text.trim().isNotEmpty) 'customer_notes': _notes.text.trim(),
      }, {'Idempotency-Key': _idempotencyKey});
      if (!mounted) return;
      final id = res['data']['id'] as int;
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => BookingDetailScreen(id: id, justCreated: true)));
    });
    if (mounted) setState(() => _busy = false);
  }

  bool get _ready => _price != null && _addressId != null && _date != null && _time != null;

  void _setQty(int q) {
    setState(() => _qty = q);
    _requote();
  }

  @override
  Widget build(BuildContext context) {
    final days = List.generate(14, (i) => DateUtils.dateOnly(DateTime.now()).add(Duration(days: i)));
    final unit = value(_price?['unit']);

    return Scaffold(
      appBar: AppBar(title: Text(widget.service['name'] ?? tr.visitTitle)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SectionCard(title: tr.stepOption, children: [
            RadioGroup<int>(
              groupValue: _price?['id'] as int?,
              onChanged: (id) {
                setState(() {
                  _price = _prices.firstWhere((p) => p['id'] == id);
                  _qty = 1;
                });
                _requote();
              },
              child: Column(children: [
                for (final p in _prices)
                  RadioListTile<int>(
                    contentPadding: EdgeInsets.zero,
                    value: p['id'] as int,
                    title: Text(p['label'] ?? ''),
                    subtitle: Text('${money(p['amount'])} / ${label(p['unit'])}'),
                  ),
              ]),
            ),
            if (_price != null && unit != 'fixed')
              Row(children: [
                Text(tr.quantity(label(_price!['unit']))),
                const Spacer(),
                IconButton.outlined(onPressed: _qty > 1 ? () => _setQty(_qty - 1) : null, icon: const Icon(Icons.remove)),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  child: Text('$_qty', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                ),
                IconButton.outlined(onPressed: () => _setQty(_qty + 1), icon: const Icon(Icons.add)),
              ]),
          ]),
          const SizedBox(height: 12),
          AddressPicker(
            title: tr.stepAddress,
            addresses: _addresses,
            selected: _addressId,
            onSelected: (v) => setState(() => _addressId = v),
            onCreated: (id) => _loadAddresses(select: id),
          ),
          const SizedBox(height: 12),
          SectionCard(title: tr.stepDateTime, children: [
            SizedBox(
              height: 74,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: days.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (_, i) {
                  final d = days[i];
                  return ChoiceChip(
                    selected: _date == d,
                    onSelected: (_) => _pickDate(d),
                    showCheckmark: false,
                    label: Column(mainAxisSize: MainAxisSize.min, children: [
                      Text(i == 0 ? tr.today : (i == 1 ? tr.tomorrow : shortDay(d)), style: const TextStyle(fontSize: 12)),
                      Text('${d.day}/${d.month}', style: const TextStyle(fontWeight: FontWeight.w700)),
                    ]),
                  );
                },
              ),
            ),
            const SizedBox(height: 10),
            if (_date == null) Text(tr.pickDayFirst, style: const TextStyle(color: AppColors.gray500)),
            if (_slotsLoading) const Padding(padding: EdgeInsets.all(12), child: Center(child: LamaaLoader(size: 40))),
            if (_date != null && !_slotsLoading && _slots.isEmpty) Text(tr.noSlots, style: const TextStyle(color: AppColors.gray500)),
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final s in _slots)
                ChoiceChip(
                  label: Text(s['time'], textDirection: TextDirection.ltr),
                  selected: _time == s['time'],
                  onSelected: s['available'] == true ? (_) => setState(() => _time = s['time'] as String) : null,
                ),
            ]),
          ]),
          const SizedBox(height: 12),
          SectionCard(title: tr.notesForTeam, children: [
            TextField(controller: _notes, maxLines: 2, decoration: InputDecoration(hintText: tr.notesForTeamHint)),
          ]),
          const SizedBox(height: 12),
          SectionCard(title: tr.summary, children: [
            if (_quote == null) const Text('—'),
            if (_quote != null) ...[
              KV(tr.subtotal, money(_quote!['subtotal'])),
              KV(tr.tax, money(_quote!['tax'])),
              KV(tr.total, money(_quote!['total']), bold: true),
            ],
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: AppColors.success50, borderRadius: BorderRadius.circular(10)),
              child: Row(children: [
                const Icon(Icons.payments_outlined, color: AppColors.success600),
                const SizedBox(width: 8),
                Expanded(child: Text(tr.cashToLeader, style: const TextStyle(color: AppColors.success600))),
              ]),
            ),
          ]),
          const SizedBox(height: 90),
        ],
      ),
      bottomSheet: SafeArea(
        child: Container(
          color: Colors.white,
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: BusyButton(label: tr.confirmBooking, busy: _busy, onPressed: _ready ? _submit : null),
        ),
      ),
    );
  }
}
