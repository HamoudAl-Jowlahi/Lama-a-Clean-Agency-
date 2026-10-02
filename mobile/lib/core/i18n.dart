import 'package:flutter/widgets.dart';

import '../l10n/app_localizations.dart';
import 'session.dart';

export '../l10n/app_localizations.dart';

/// نصوص الواجهة باللغة الحالية — متاحة بدون context (الخدمات والتنسيق أيضاً).
/// تغيير اللغة يعيد بناء التطبيق كاملاً (MaterialApp بمفتاح اللغة).
AppLocalizations get tr => lookupAppLocalizations(Locale(Session.I.lang));

bool get isEn => Session.I.lang == 'en';
