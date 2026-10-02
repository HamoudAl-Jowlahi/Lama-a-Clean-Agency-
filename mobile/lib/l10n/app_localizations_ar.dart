// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'لمعة';

  @override
  String get tagline => 'خدمات تنظيف بثقة';

  @override
  String get loading => 'جارٍ التحميل…';

  @override
  String get retry => 'إعادة المحاولة';

  @override
  String get back => 'تراجع';

  @override
  String get confirm => 'تأكيد';

  @override
  String get send => 'إرسال';

  @override
  String get save => 'حفظ';

  @override
  String get saved => 'تم الحفظ';

  @override
  String get delete => 'حذف';

  @override
  String get deleted => 'تم الحذف';

  @override
  String get now => 'الآن';

  @override
  String get today => 'اليوم';

  @override
  String get tomorrow => 'غداً';

  @override
  String get from => 'من';

  @override
  String get to => 'إلى';

  @override
  String get number => 'الرقم';

  @override
  String get name => 'الاسم';

  @override
  String get phone => 'رقم الجوال';

  @override
  String get password => 'كلمة المرور';

  @override
  String get passwordHint => '8 أحرف على الأقل';

  @override
  String get emailOptional => 'البريد الإلكتروني (اختياري)';

  @override
  String money(String amount) {
    return '$amount ر.س';
  }

  @override
  String buildingNo(String n) {
    return 'مبنى $n';
  }

  @override
  String get errNetwork =>
      'تعذر الاتصال بالخادم — تحقق من الإنترنت ثم حاول مجدداً.';

  @override
  String errUnexpected(int code) {
    return 'حدث خطأ غير متوقع ($code).';
  }

  @override
  String get errSession => 'تعذر فتح الجلسة.';

  @override
  String errGeneric(String error) {
    return 'حدث خطأ: $error';
  }

  @override
  String get errLoad => 'تعذر التحميل';

  @override
  String get errPick => 'تعذر اختيار الصورة';

  @override
  String get loginTitle => 'تسجيل الدخول';

  @override
  String get loginSubtitle => 'للعملاء وفرق التنظيف والعاملات';

  @override
  String get login => 'دخول';

  @override
  String get newCustomer => 'عميل جديد؟ أنشئ حساباً';

  @override
  String get staffAccountsNote =>
      'حسابات الفرق والعاملات تُنشأ من إدارة الوكالة.';

  @override
  String get registerTitle => 'حساب جديد';

  @override
  String get createAccount => 'إنشاء الحساب';

  @override
  String get language => 'اللغة';

  @override
  String get tabHome => 'الرئيسية';

  @override
  String get tabVisits => 'زياراتي';

  @override
  String get tabContracts => 'عقودي';

  @override
  String get tabAccount => 'حسابي';

  @override
  String hello(String name) {
    return 'أهلاً $name 👋';
  }

  @override
  String get heroTitle => 'عاملة منزلية بعقد شهري';

  @override
  String get heroSubtitle =>
      'استبدال العاملة أو إنهاء العقد من التطبيق مباشرة.';

  @override
  String get visitTitle => 'زيارة تنظيف';

  @override
  String get visitSubtitle =>
      'فريق متخصص يأتي في الموعد، ينظّف ويغادر — والدفع نقداً بعد الإتمام.';

  @override
  String get noServices => 'لا توجد خدمات متاحة حالياً';

  @override
  String get startsFrom => 'يبدأ من';

  @override
  String get stepOption => '1. اختر الخيار';

  @override
  String quantity(String unit) {
    return 'الكمية ($unit)';
  }

  @override
  String get stepAddress => '2. العنوان';

  @override
  String get stepDateTime => '3. اليوم والوقت';

  @override
  String get pickDayFirst => 'اختر اليوم لعرض الأوقات المتاحة.';

  @override
  String get noSlots => 'لا توجد أوقات في هذا اليوم.';

  @override
  String get notesForTeam => 'ملاحظات للفريق (اختياري)';

  @override
  String get notesForTeamHint => 'مثال: يوجد قطة في المنزل';

  @override
  String get summary => 'الملخص';

  @override
  String get subtotal => 'السعر قبل الضريبة';

  @override
  String get tax => 'الضريبة';

  @override
  String get total => 'الإجمالي';

  @override
  String get cashToLeader => 'الدفع نقداً لقائد الفريق بعد إتمام الخدمة.';

  @override
  String get confirmBooking => 'تأكيد الطلب';

  @override
  String get myAddresses => 'عناويني';

  @override
  String get newAddress => 'عنوان جديد';

  @override
  String get noAddresses => 'لا توجد عناوين بعد';

  @override
  String get deleteAddress => 'حذف العنوان';

  @override
  String deleteAddressQ(String label) {
    return 'هل تريد حذف \"$label\"؟';
  }

  @override
  String get addAddressFirst => 'أضف عنواناً لإتمام الطلب.';

  @override
  String get addrLabel => 'اسم العنوان (مثال: المنزل)';

  @override
  String get addrCity => 'المدينة';

  @override
  String get addrDistrict => 'الحي';

  @override
  String get addrStreet => 'الشارع (اختياري)';

  @override
  String get addrBuilding => 'رقم المبنى (اختياري)';

  @override
  String get addrFloor => 'الدور (اختياري)';

  @override
  String get addrDetails => 'وصف إضافي (اختياري)';

  @override
  String get defaultCity => 'الرياض';

  @override
  String get defaultAddress => 'العنوان الافتراضي';

  @override
  String get saveAddress => 'حفظ العنوان';

  @override
  String get noVisits => 'لا توجد زيارات بعد — اطلب أول زيارة من الرئيسية';

  @override
  String get visitDetails => 'تفاصيل الزيارة';

  @override
  String get bookingReceived => 'تم استلام طلبك! سنؤكده ونُسند فريقاً قريباً.';

  @override
  String get appointment => 'الموعد';

  @override
  String get address => 'العنوان';

  @override
  String get assignedTeam => 'الفريق المنفذ';

  @override
  String get notAssignedYet => 'لم يُسند بعد';

  @override
  String get yourNotes => 'ملاحظاتك';

  @override
  String get cancelReason => 'سبب الإلغاء';

  @override
  String get serviceAndAmount => 'الخدمة والمبلغ';

  @override
  String get paymentMethod => 'طريقة الدفع';

  @override
  String get cashOnCompletion => 'نقداً عند الإتمام';

  @override
  String get paymentStatus => 'حالة الدفع';

  @override
  String get tracking => 'التتبع';

  @override
  String get yourRating => 'تقييمك';

  @override
  String get service => 'الخدمة';

  @override
  String get team => 'الفريق';

  @override
  String get rateTeam => 'تقييم الفريق';

  @override
  String get rateVisit => 'قيّم الزيارة';

  @override
  String get rateService => 'تقييم الخدمة';

  @override
  String get commentOptional => 'تعليق (اختياري)';

  @override
  String get sendRating => 'إرسال التقييم';

  @override
  String get thanksRating => 'شكراً لتقييمك!';

  @override
  String get cancelVisit => 'إلغاء الزيارة';

  @override
  String get cancelReasonOptional => 'سبب الإلغاء (اختياري)';

  @override
  String get visitCancelled => 'تم إلغاء الزيارة';

  @override
  String get haveProblem => 'لديك مشكلة؟ قدّم شكوى';

  @override
  String get hireTitle => 'استئجار عاملة بعقد';

  @override
  String get hireIntro =>
      'اختر الباقة المناسبة. يمكنك طلب استبدال العاملة أو إنهاء العقد من التطبيق في أي وقت.';

  @override
  String get noPlans => 'لا توجد باقات متاحة حالياً';

  @override
  String perMonth(String price) {
    return '$price / شهر';
  }

  @override
  String daysPerWeek(int n) {
    return '$n أيام/أسبوع';
  }

  @override
  String hoursPerDay(int n) {
    return '$n ساعات/يوم';
  }

  @override
  String get contractRequest => 'طلب عقد';

  @override
  String get durationAndStart => 'المدة والبداية';

  @override
  String get contractDuration => 'مدة العقد';

  @override
  String months(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n شهراً',
      few: '$n أشهر',
      two: 'شهران',
      one: 'شهر واحد',
    );
    return '$_temp0';
  }

  @override
  String startDate(String date) {
    return 'تاريخ البدء: $date';
  }

  @override
  String get workAddress => 'عنوان العمل';

  @override
  String get notesOptional => 'ملاحظات (اختياري)';

  @override
  String get contractNotesHint => 'مثال: يفضّل من يتحدث العربية';

  @override
  String get monthly => 'الشهري';

  @override
  String get payment => 'الدفع';

  @override
  String get cashMonthly => 'نقداً — دفعة كل شهر';

  @override
  String acceptTerms(String version) {
    return 'أوافق على شروط العقد (الإصدار $version)';
  }

  @override
  String get sendContractRequest => 'إرسال طلب العقد';

  @override
  String get newContract => 'عقد جديد';

  @override
  String get noContracts => 'لا توجد عقود — استأجر عاملة منزلية بعقد شهري';

  @override
  String dayOf(int day, int total) {
    return 'اليوم $day من $total';
  }

  @override
  String get contractDetails => 'تفاصيل العقد';

  @override
  String get contractReceived =>
      'تم إرسال طلب العقد. ستراجعه الإدارة وتعيّن لك عاملة.';

  @override
  String get plan => 'الباقة';

  @override
  String get schedule => 'الدوام';

  @override
  String scheduleValue(String days, String hours) {
    return '$days أيام × $hours ساعات';
  }

  @override
  String get terminatedOn => 'أُنهي في';

  @override
  String get housekeeper => 'العاملة';

  @override
  String get noWorkerYet => 'لم تُعيَّن عاملة بعد.';

  @override
  String since(String date) {
    return 'منذ $date';
  }

  @override
  String get amountsCash => 'المبالغ (نقداً)';

  @override
  String dueOn(String date) {
    return 'استحقاق $date';
  }

  @override
  String get changeRequests => 'طلبات الاستبدال والإنهاء';

  @override
  String adminReply(String reply) {
    return 'رد الإدارة: $reply';
  }

  @override
  String get requestReplace => 'طلب استبدال العاملة';

  @override
  String get requestTerminate => 'طلب إنهاء العقد';

  @override
  String get requestPending => 'لديك طلب قيد المراجعة لدى الإدارة.';

  @override
  String get cancelContract => 'إلغاء العقد';

  @override
  String get cancelContractQ => 'سيُلغى العقد قبل بدئه. هل أنت متأكد؟';

  @override
  String get contractCancelled => 'تم إلغاء العقد';

  @override
  String get cancelBeforeStart => 'إلغاء العقد قبل البدء';

  @override
  String rateName(String name) {
    return 'قيّم $name';
  }

  @override
  String get fileComplaint => 'قدّم شكوى';

  @override
  String get reasonDelay => 'تأخر متكرر';

  @override
  String get reasonQuality => 'جودة العمل';

  @override
  String get reasonAbsence => 'غياب';

  @override
  String get reasonBehavior => 'سلوك';

  @override
  String get reasonNotNeeded => 'لم أعد بحاجة للخدمة';

  @override
  String get reasonOther => 'سبب آخر';

  @override
  String get reason => 'السبب';

  @override
  String get detailsOptional => 'التفاصيل (اختياري)';

  @override
  String get terminateNote =>
      'تراجع الإدارة الطلب، ويُحتسب المبلغ حتى آخر يوم عمل فعلي.';

  @override
  String get replaceNote =>
      'تراجع الإدارة الطلب وتعيّن عاملة بديلة، ولا تُحتسب أيام الانتظار.';

  @override
  String requestedEndDate(String date) {
    return 'تاريخ الإنهاء المطلوب: $date';
  }

  @override
  String get sendRequest => 'إرسال الطلب';

  @override
  String get requestSent => 'تم إرسال طلبك للإدارة';

  @override
  String attachmentsTitle(int count, int max) {
    return 'صور مرفقة ($count/$max)';
  }

  @override
  String get gallery => 'المعرض';

  @override
  String get camera => 'الكاميرا';

  @override
  String filesCount(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n مرفقاً',
      few: '$n مرفقات',
      two: 'مرفقان',
      one: 'مرفق واحد',
    );
    return '$_temp0';
  }

  @override
  String get cLate => 'تأخر عن الموعد';

  @override
  String get cQuality => 'جودة الخدمة';

  @override
  String get cBehavior => 'سلوك';

  @override
  String get cPayment => 'مشكلة في الدفع';

  @override
  String get cOther => 'أخرى';

  @override
  String get myComplaints => 'شكاواي';

  @override
  String get noComplaints =>
      'لا توجد شكاوى. يمكنك تقديم شكوى من صفحة الزيارة أو العقد.';

  @override
  String get complaint => 'الشكوى';

  @override
  String get regarding => 'بخصوص';

  @override
  String regardingX(String number) {
    return 'بخصوص: $number';
  }

  @override
  String get complaintClosed => 'الشكوى مغلقة.';

  @override
  String get writeReply => 'اكتب ردك…';

  @override
  String statusChangedTo(String status) {
    return 'تغيّرت الحالة إلى \"$status\"';
  }

  @override
  String get complaintSent => 'تم إرسال الشكوى — سنتواصل معك قريباً';

  @override
  String get newComplaint => 'شكوى جديدة';

  @override
  String get problemType => 'نوع المشكلة';

  @override
  String get describeProblem => 'اشرح المشكلة';

  @override
  String get sendComplaint => 'إرسال الشكوى';

  @override
  String get notifications => 'الإشعارات';

  @override
  String get readAll => 'قراءة الكل';

  @override
  String get noNotifications => 'لا توجد إشعارات';

  @override
  String get notifyOrders => 'إشعارات الطلبات والعقود';

  @override
  String get notifyComplaints => 'إشعارات الشكاوى';

  @override
  String get editProfile => 'تعديل الملف الشخصي';

  @override
  String get phoneLocked => 'لتغيير الرقم تواصل مع الوكالة';

  @override
  String get changePassword => 'تغيير كلمة المرور';

  @override
  String get currentPassword => 'كلمة المرور الحالية';

  @override
  String get newPassword => 'كلمة المرور الجديدة';

  @override
  String get confirmPassword => 'تأكيد كلمة المرور';

  @override
  String get passwordsDontMatch => 'كلمتا المرور غير متطابقتين';

  @override
  String get passwordChanged => 'تم تغيير كلمة المرور';

  @override
  String get otherDevicesLoggedOut => 'سيتم تسجيل الخروج من الأجهزة الأخرى.';

  @override
  String get logout => 'تسجيل الخروج';

  @override
  String get logoutQ => 'هل تريد تسجيل الخروج من هذا الجهاز؟';

  @override
  String get logoutShort => 'خروج';

  @override
  String get scopeNew => 'جديدة';

  @override
  String get scopeUpcoming => 'القادمة';

  @override
  String get scopeDone => 'المنجزة';

  @override
  String get myTeam => 'فريقي';

  @override
  String get youAreLeader => 'أنت قائد الفريق';

  @override
  String get memberNote => 'عضو — القائد يحدّث الحالة';

  @override
  String get noTeam => 'لم تُضَف إلى فريق بعد. تواصل مع الإدارة.';

  @override
  String get noVisitsHere => 'لا توجد زيارات هنا';

  @override
  String get awaitingYourAcceptance => 'بانتظار قبولك';

  @override
  String get myRatings => 'تقييماتي';

  @override
  String get assignment => 'الإسناد';

  @override
  String get customerAndLocation => 'العميل والموقع';

  @override
  String get customer => 'العميل';

  @override
  String get description => 'وصف';

  @override
  String get customerNotes => 'ملاحظات العميل';

  @override
  String callCustomer(String phone) {
    return 'اتصال بالعميل  $phone';
  }

  @override
  String get amountToCollect => 'المبلغ المطلوب تحصيله نقداً عند الإتمام';

  @override
  String get leaderOnlyNote => 'قائد الفريق هو من يقبل الزيارة ويحدّث حالتها.';

  @override
  String get acceptVisit => 'قبول الزيارة';

  @override
  String get visitAccepted => 'تم قبول الزيارة';

  @override
  String get reject => 'رفض';

  @override
  String get rejectAssignment => 'رفض الإسناد';

  @override
  String get rejectReason => 'سبب الرفض';

  @override
  String get rejectedBack => 'تم الرفض — أُعيدت للإدارة';

  @override
  String get completeVisit => 'إتمام الزيارة';

  @override
  String completeVisitQ(String amount) {
    return 'هل استلمت $amount نقداً من العميل؟';
  }

  @override
  String get yesReceived => 'نعم، تم الاستلام';

  @override
  String updatedTo(String status) {
    return 'تم التحديث: $status';
  }

  @override
  String get noAssignedContracts => 'لا توجد عقود مسندة إليك حالياً';

  @override
  String get currentContract => 'عقدي الحالي';

  @override
  String get otherContracts => 'العقود السابقة والقادمة';

  @override
  String get contract => 'العقد';

  @override
  String get myPeriod => 'فترتي';

  @override
  String get contactDuringPeriodOnly =>
      'تظهر بيانات التواصل والعنوان خلال فترة عملك فقط.';

  @override
  String averageOf(int n) {
    return 'متوسط $n تقييم';
  }

  @override
  String get noRatings => 'لا توجد تقييمات بعد';
}
