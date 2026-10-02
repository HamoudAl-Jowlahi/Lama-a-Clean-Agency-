import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/i18n.dart';
import '../../core/motion.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

class AddressesScreen extends StatefulWidget {
  const AddressesScreen({super.key});

  @override
  State<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends State<AddressesScreen> {
  Key _key = UniqueKey();

  void _refresh() => setState(() => _key = UniqueKey());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.myAddresses)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final created = await Navigator.push<Map>(context, MaterialPageRoute(builder: (_) => const AddressFormScreen()));
          if (created != null) _refresh();
        },
        icon: const Icon(Icons.add),
        label: Text(tr.newAddress),
      ),
      body: Loader<List>(
        key: _key,
        skeleton: true,
        load: () async => (await Api.I.get('/addresses'))['data'] as List,
        builder: (context, list, reload) => list.isEmpty
            ? ListView(children: [EmptyState(tr.noAddresses, icon: Icons.location_off_outlined)])
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final a = list[i];
                  return Appear(index: i, child: Card(
                    child: ListTile(
                      leading: const Icon(Icons.location_on_outlined),
                      title: Text(a['label'] ?? ''),
                      subtitle: Text(addressLine({...a, 'label': null})),
                      trailing: IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () async {
                          if (!await confirm(context, tr.deleteAddress, tr.deleteAddressQ(a['label'] ?? ''), ok: tr.delete, danger: true)) return;
                          if (!context.mounted) return;
                          if (await run(context, () => Api.I.delete('/addresses/${a['id']}'), success: tr.deleted)) reload();
                        },
                      ),
                    ),
                  ));
                },
              ),
      ),
    );
  }
}

/// اختيار العنوان في طلب الزيارة أو العقد، مع إضافة عنوان جديد.
class AddressPicker extends StatelessWidget {
  const AddressPicker({
    super.key,
    required this.title,
    required this.addresses,
    required this.selected,
    required this.onSelected,
    required this.onCreated,
  });

  final String title;
  final List addresses;
  final int? selected;
  final ValueChanged<int?> onSelected;
  final ValueChanged<int> onCreated;

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      title: title,
      trailing: TextButton.icon(
        onPressed: () async {
          final created = await Navigator.push<Map>(context, MaterialPageRoute(builder: (_) => const AddressFormScreen()));
          if (created != null) onCreated(created['id'] as int);
        },
        icon: Icon(Icons.add_location_alt_outlined),
        label: Text(tr.newAddress),
      ),
      children: [
        if (addresses.isEmpty) Text(tr.addAddressFirst, style: TextStyle(color: AppColors.gray500)),
        RadioGroup<int>(
          groupValue: selected,
          onChanged: onSelected,
          child: Column(children: [
            for (final a in addresses)
              RadioListTile<int>(
                contentPadding: EdgeInsets.zero,
                value: a['id'] as int,
                title: Text(a['label'] ?? ''),
                subtitle: Text(addressLine({...a, 'label': null})),
              ),
          ]),
        ),
      ],
    );
  }
}

class AddressFormScreen extends StatefulWidget {
  const AddressFormScreen({super.key});

  @override
  State<AddressFormScreen> createState() => _AddressFormScreenState();
}

class _AddressFormScreenState extends State<AddressFormScreen> {
  final _c = {
    for (final f in ['label', 'city', 'district', 'street', 'building', 'floor', 'details']) f: TextEditingController(),
  };
  bool _default = true;
  bool _busy = false;
  ApiException? _error;

  Map<String, String> get _labels => {
        'label': tr.addrLabel,
        'city': tr.addrCity,
        'district': tr.addrDistrict,
        'street': tr.addrStreet,
        'building': tr.addrBuilding,
        'floor': tr.addrFloor,
        'details': tr.addrDetails,
      };

  @override
  void initState() {
    super.initState();
    _c['city']!.text = tr.defaultCity;
  }

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final res = await Api.I.post('/addresses', {
        for (final e in _c.entries)
          if (e.value.text.trim().isNotEmpty) e.key: e.value.text.trim(),
        'make_default': _default,
      });
      if (mounted) Navigator.pop(context, res['data']);
    } on ApiException catch (e) {
      setState(() => _error = e);
      if (e.errors == null && mounted) toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr.newAddress)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          for (final f in _labels.keys) ...[
            TextField(
              controller: _c[f],
              maxLines: f == 'details' ? 2 : 1,
              decoration: InputDecoration(labelText: _labels[f], errorText: _error?.field(f)),
            ),
            const SizedBox(height: 12),
          ],
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _default,
            onChanged: (v) => setState(() => _default = v),
            title: Text(tr.defaultAddress),
          ),
          const SizedBox(height: 12),
          BusyButton(label: tr.saveAddress, busy: _busy, onPressed: _save),
        ],
      ),
    );
  }
}
