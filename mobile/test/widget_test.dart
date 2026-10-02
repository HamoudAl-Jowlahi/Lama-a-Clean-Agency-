import 'package:flutter_test/flutter_test.dart';
import 'package:lamaa/core/api.dart';
import 'package:lamaa/core/widgets.dart';

void main() {
  test('ApiException exposes first field error', () {
    final e = ApiException('خطأ', errors: {'phone': ['رقم الجوال مطلوب.']});
    expect(e.field('phone'), 'رقم الجوال مطلوب.');
    expect(e.field('name'), isNull);
  });

  test('address line joins available parts', () {
    expect(addressLine({'label': 'المنزل', 'city': 'الرياض', 'district': 'النرجس', 'street': null}), 'المنزل، الرياض، النرجس');
  });

  test('idempotency keys are unique UUID-like strings', () {
    final a = Api.idempotencyKey(), b = Api.idempotencyKey();
    expect(a, isNot(b));
    expect(a.length, 36);
  });
}
