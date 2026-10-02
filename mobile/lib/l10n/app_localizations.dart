import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// No description provided for @appName.
  ///
  /// In ar, this message translates to:
  /// **'لمعة'**
  String get appName;

  /// No description provided for @tagline.
  ///
  /// In ar, this message translates to:
  /// **'خدمات تنظيف بثقة'**
  String get tagline;

  /// No description provided for @loading.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التحميل…'**
  String get loading;

  /// No description provided for @retry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get retry;

  /// No description provided for @back.
  ///
  /// In ar, this message translates to:
  /// **'تراجع'**
  String get back;

  /// No description provided for @confirm.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد'**
  String get confirm;

  /// No description provided for @send.
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get send;

  /// No description provided for @save.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get save;

  /// No description provided for @saved.
  ///
  /// In ar, this message translates to:
  /// **'تم الحفظ'**
  String get saved;

  /// No description provided for @delete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get delete;

  /// No description provided for @deleted.
  ///
  /// In ar, this message translates to:
  /// **'تم الحذف'**
  String get deleted;

  /// No description provided for @now.
  ///
  /// In ar, this message translates to:
  /// **'الآن'**
  String get now;

  /// No description provided for @today.
  ///
  /// In ar, this message translates to:
  /// **'اليوم'**
  String get today;

  /// No description provided for @tomorrow.
  ///
  /// In ar, this message translates to:
  /// **'غداً'**
  String get tomorrow;

  /// No description provided for @from.
  ///
  /// In ar, this message translates to:
  /// **'من'**
  String get from;

  /// No description provided for @to.
  ///
  /// In ar, this message translates to:
  /// **'إلى'**
  String get to;

  /// No description provided for @number.
  ///
  /// In ar, this message translates to:
  /// **'الرقم'**
  String get number;

  /// No description provided for @name.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get name;

  /// No description provided for @phone.
  ///
  /// In ar, this message translates to:
  /// **'رقم الجوال'**
  String get phone;

  /// No description provided for @password.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور'**
  String get password;

  /// No description provided for @passwordHint.
  ///
  /// In ar, this message translates to:
  /// **'8 أحرف على الأقل'**
  String get passwordHint;

  /// No description provided for @emailOptional.
  ///
  /// In ar, this message translates to:
  /// **'البريد الإلكتروني (اختياري)'**
  String get emailOptional;

  /// No description provided for @money.
  ///
  /// In ar, this message translates to:
  /// **'{amount} ر.س'**
  String money(String amount);

  /// No description provided for @buildingNo.
  ///
  /// In ar, this message translates to:
  /// **'مبنى {n}'**
  String buildingNo(String n);

  /// No description provided for @errNetwork.
  ///
  /// In ar, this message translates to:
  /// **'تعذر الاتصال بالخادم — تحقق من الإنترنت ثم حاول مجدداً.'**
  String get errNetwork;

  /// No description provided for @errUnexpected.
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ غير متوقع ({code}).'**
  String errUnexpected(int code);

  /// No description provided for @errSession.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فتح الجلسة.'**
  String get errSession;

  /// No description provided for @errGeneric.
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ: {error}'**
  String errGeneric(String error);

  /// No description provided for @errLoad.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التحميل'**
  String get errLoad;

  /// No description provided for @errPick.
  ///
  /// In ar, this message translates to:
  /// **'تعذر اختيار الصورة'**
  String get errPick;

  /// No description provided for @loginTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get loginTitle;

  /// No description provided for @loginSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'للعملاء وفرق التنظيف والعاملات'**
  String get loginSubtitle;

  /// No description provided for @login.
  ///
  /// In ar, this message translates to:
  /// **'دخول'**
  String get login;

  /// No description provided for @newCustomer.
  ///
  /// In ar, this message translates to:
  /// **'عميل جديد؟ أنشئ حساباً'**
  String get newCustomer;

  /// No description provided for @staffAccountsNote.
  ///
  /// In ar, this message translates to:
  /// **'حسابات الفرق والعاملات تُنشأ من إدارة الوكالة.'**
  String get staffAccountsNote;

  /// No description provided for @registerTitle.
  ///
  /// In ar, this message translates to:
  /// **'حساب جديد'**
  String get registerTitle;

  /// No description provided for @createAccount.
  ///
  /// In ar, this message translates to:
  /// **'إنشاء الحساب'**
  String get createAccount;

  /// No description provided for @language.
  ///
  /// In ar, this message translates to:
  /// **'اللغة'**
  String get language;

  /// No description provided for @tabHome.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get tabHome;

  /// No description provided for @tabVisits.
  ///
  /// In ar, this message translates to:
  /// **'زياراتي'**
  String get tabVisits;

  /// No description provided for @tabContracts.
  ///
  /// In ar, this message translates to:
  /// **'عقودي'**
  String get tabContracts;

  /// No description provided for @tabAccount.
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get tabAccount;

  /// No description provided for @hello.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً {name} 👋'**
  String hello(String name);

  /// No description provided for @heroTitle.
  ///
  /// In ar, this message translates to:
  /// **'عاملة منزلية بعقد شهري'**
  String get heroTitle;

  /// No description provided for @heroSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'استبدال العاملة أو إنهاء العقد من التطبيق مباشرة.'**
  String get heroSubtitle;

  /// No description provided for @visitTitle.
  ///
  /// In ar, this message translates to:
  /// **'زيارة تنظيف'**
  String get visitTitle;

  /// No description provided for @visitSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'فريق متخصص يأتي في الموعد، ينظّف ويغادر — والدفع نقداً بعد الإتمام.'**
  String get visitSubtitle;

  /// No description provided for @noServices.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد خدمات متاحة حالياً'**
  String get noServices;

  /// No description provided for @startsFrom.
  ///
  /// In ar, this message translates to:
  /// **'يبدأ من'**
  String get startsFrom;

  /// No description provided for @stepOption.
  ///
  /// In ar, this message translates to:
  /// **'1. اختر الخيار'**
  String get stepOption;

  /// No description provided for @quantity.
  ///
  /// In ar, this message translates to:
  /// **'الكمية ({unit})'**
  String quantity(String unit);

  /// No description provided for @stepAddress.
  ///
  /// In ar, this message translates to:
  /// **'2. العنوان'**
  String get stepAddress;

  /// No description provided for @stepDateTime.
  ///
  /// In ar, this message translates to:
  /// **'3. اليوم والوقت'**
  String get stepDateTime;

  /// No description provided for @pickDayFirst.
  ///
  /// In ar, this message translates to:
  /// **'اختر اليوم لعرض الأوقات المتاحة.'**
  String get pickDayFirst;

  /// No description provided for @noSlots.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد أوقات في هذا اليوم.'**
  String get noSlots;

  /// No description provided for @notesForTeam.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات للفريق (اختياري)'**
  String get notesForTeam;

  /// No description provided for @notesForTeamHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: يوجد قطة في المنزل'**
  String get notesForTeamHint;

  /// No description provided for @summary.
  ///
  /// In ar, this message translates to:
  /// **'الملخص'**
  String get summary;

  /// No description provided for @subtotal.
  ///
  /// In ar, this message translates to:
  /// **'السعر قبل الضريبة'**
  String get subtotal;

  /// No description provided for @tax.
  ///
  /// In ar, this message translates to:
  /// **'الضريبة'**
  String get tax;

  /// No description provided for @total.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي'**
  String get total;

  /// No description provided for @cashToLeader.
  ///
  /// In ar, this message translates to:
  /// **'الدفع نقداً لقائد الفريق بعد إتمام الخدمة.'**
  String get cashToLeader;

  /// No description provided for @confirmBooking.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الطلب'**
  String get confirmBooking;

  /// No description provided for @myAddresses.
  ///
  /// In ar, this message translates to:
  /// **'عناويني'**
  String get myAddresses;

  /// No description provided for @newAddress.
  ///
  /// In ar, this message translates to:
  /// **'عنوان جديد'**
  String get newAddress;

  /// No description provided for @noAddresses.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عناوين بعد'**
  String get noAddresses;

  /// No description provided for @deleteAddress.
  ///
  /// In ar, this message translates to:
  /// **'حذف العنوان'**
  String get deleteAddress;

  /// No description provided for @deleteAddressQ.
  ///
  /// In ar, this message translates to:
  /// **'هل تريد حذف \"{label}\"؟'**
  String deleteAddressQ(String label);

  /// No description provided for @addAddressFirst.
  ///
  /// In ar, this message translates to:
  /// **'أضف عنواناً لإتمام الطلب.'**
  String get addAddressFirst;

  /// No description provided for @addrLabel.
  ///
  /// In ar, this message translates to:
  /// **'اسم العنوان (مثال: المنزل)'**
  String get addrLabel;

  /// No description provided for @addrCity.
  ///
  /// In ar, this message translates to:
  /// **'المدينة'**
  String get addrCity;

  /// No description provided for @addrDistrict.
  ///
  /// In ar, this message translates to:
  /// **'الحي'**
  String get addrDistrict;

  /// No description provided for @addrStreet.
  ///
  /// In ar, this message translates to:
  /// **'الشارع (اختياري)'**
  String get addrStreet;

  /// No description provided for @addrBuilding.
  ///
  /// In ar, this message translates to:
  /// **'رقم المبنى (اختياري)'**
  String get addrBuilding;

  /// No description provided for @addrFloor.
  ///
  /// In ar, this message translates to:
  /// **'الدور (اختياري)'**
  String get addrFloor;

  /// No description provided for @addrDetails.
  ///
  /// In ar, this message translates to:
  /// **'وصف إضافي (اختياري)'**
  String get addrDetails;

  /// No description provided for @defaultCity.
  ///
  /// In ar, this message translates to:
  /// **'الرياض'**
  String get defaultCity;

  /// No description provided for @defaultAddress.
  ///
  /// In ar, this message translates to:
  /// **'العنوان الافتراضي'**
  String get defaultAddress;

  /// No description provided for @saveAddress.
  ///
  /// In ar, this message translates to:
  /// **'حفظ العنوان'**
  String get saveAddress;

  /// No description provided for @noVisits.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد زيارات بعد — اطلب أول زيارة من الرئيسية'**
  String get noVisits;

  /// No description provided for @visitDetails.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الزيارة'**
  String get visitDetails;

  /// No description provided for @bookingReceived.
  ///
  /// In ar, this message translates to:
  /// **'تم استلام طلبك! سنؤكده ونُسند فريقاً قريباً.'**
  String get bookingReceived;

  /// No description provided for @appointment.
  ///
  /// In ar, this message translates to:
  /// **'الموعد'**
  String get appointment;

  /// No description provided for @address.
  ///
  /// In ar, this message translates to:
  /// **'العنوان'**
  String get address;

  /// No description provided for @assignedTeam.
  ///
  /// In ar, this message translates to:
  /// **'الفريق المنفذ'**
  String get assignedTeam;

  /// No description provided for @notAssignedYet.
  ///
  /// In ar, this message translates to:
  /// **'لم يُسند بعد'**
  String get notAssignedYet;

  /// No description provided for @yourNotes.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظاتك'**
  String get yourNotes;

  /// No description provided for @cancelReason.
  ///
  /// In ar, this message translates to:
  /// **'سبب الإلغاء'**
  String get cancelReason;

  /// No description provided for @serviceAndAmount.
  ///
  /// In ar, this message translates to:
  /// **'الخدمة والمبلغ'**
  String get serviceAndAmount;

  /// No description provided for @paymentMethod.
  ///
  /// In ar, this message translates to:
  /// **'طريقة الدفع'**
  String get paymentMethod;

  /// No description provided for @cashOnCompletion.
  ///
  /// In ar, this message translates to:
  /// **'نقداً عند الإتمام'**
  String get cashOnCompletion;

  /// No description provided for @paymentStatus.
  ///
  /// In ar, this message translates to:
  /// **'حالة الدفع'**
  String get paymentStatus;

  /// No description provided for @tracking.
  ///
  /// In ar, this message translates to:
  /// **'التتبع'**
  String get tracking;

  /// No description provided for @yourRating.
  ///
  /// In ar, this message translates to:
  /// **'تقييمك'**
  String get yourRating;

  /// No description provided for @service.
  ///
  /// In ar, this message translates to:
  /// **'الخدمة'**
  String get service;

  /// No description provided for @team.
  ///
  /// In ar, this message translates to:
  /// **'الفريق'**
  String get team;

  /// No description provided for @rateTeam.
  ///
  /// In ar, this message translates to:
  /// **'تقييم الفريق'**
  String get rateTeam;

  /// No description provided for @rateVisit.
  ///
  /// In ar, this message translates to:
  /// **'قيّم الزيارة'**
  String get rateVisit;

  /// No description provided for @rateService.
  ///
  /// In ar, this message translates to:
  /// **'تقييم الخدمة'**
  String get rateService;

  /// No description provided for @commentOptional.
  ///
  /// In ar, this message translates to:
  /// **'تعليق (اختياري)'**
  String get commentOptional;

  /// No description provided for @sendRating.
  ///
  /// In ar, this message translates to:
  /// **'إرسال التقييم'**
  String get sendRating;

  /// No description provided for @thanksRating.
  ///
  /// In ar, this message translates to:
  /// **'شكراً لتقييمك!'**
  String get thanksRating;

  /// No description provided for @cancelVisit.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الزيارة'**
  String get cancelVisit;

  /// No description provided for @cancelReasonOptional.
  ///
  /// In ar, this message translates to:
  /// **'سبب الإلغاء (اختياري)'**
  String get cancelReasonOptional;

  /// No description provided for @visitCancelled.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء الزيارة'**
  String get visitCancelled;

  /// No description provided for @haveProblem.
  ///
  /// In ar, this message translates to:
  /// **'لديك مشكلة؟ قدّم شكوى'**
  String get haveProblem;

  /// No description provided for @hireTitle.
  ///
  /// In ar, this message translates to:
  /// **'استئجار عاملة بعقد'**
  String get hireTitle;

  /// No description provided for @hireIntro.
  ///
  /// In ar, this message translates to:
  /// **'اختر الباقة المناسبة. يمكنك طلب استبدال العاملة أو إنهاء العقد من التطبيق في أي وقت.'**
  String get hireIntro;

  /// No description provided for @noPlans.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد باقات متاحة حالياً'**
  String get noPlans;

  /// No description provided for @perMonth.
  ///
  /// In ar, this message translates to:
  /// **'{price} / شهر'**
  String perMonth(String price);

  /// No description provided for @daysPerWeek.
  ///
  /// In ar, this message translates to:
  /// **'{n} أيام/أسبوع'**
  String daysPerWeek(int n);

  /// No description provided for @hoursPerDay.
  ///
  /// In ar, this message translates to:
  /// **'{n} ساعات/يوم'**
  String hoursPerDay(int n);

  /// No description provided for @contractRequest.
  ///
  /// In ar, this message translates to:
  /// **'طلب عقد'**
  String get contractRequest;

  /// No description provided for @durationAndStart.
  ///
  /// In ar, this message translates to:
  /// **'المدة والبداية'**
  String get durationAndStart;

  /// No description provided for @contractDuration.
  ///
  /// In ar, this message translates to:
  /// **'مدة العقد'**
  String get contractDuration;

  /// No description provided for @months.
  ///
  /// In ar, this message translates to:
  /// **'{n, plural, =1{شهر واحد} =2{شهران} few{{n} أشهر} other{{n} شهراً}}'**
  String months(int n);

  /// No description provided for @startDate.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ البدء: {date}'**
  String startDate(String date);

  /// No description provided for @workAddress.
  ///
  /// In ar, this message translates to:
  /// **'عنوان العمل'**
  String get workAddress;

  /// No description provided for @notesOptional.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات (اختياري)'**
  String get notesOptional;

  /// No description provided for @contractNotesHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: يفضّل من يتحدث العربية'**
  String get contractNotesHint;

  /// No description provided for @monthly.
  ///
  /// In ar, this message translates to:
  /// **'الشهري'**
  String get monthly;

  /// No description provided for @payment.
  ///
  /// In ar, this message translates to:
  /// **'الدفع'**
  String get payment;

  /// No description provided for @cashMonthly.
  ///
  /// In ar, this message translates to:
  /// **'نقداً — دفعة كل شهر'**
  String get cashMonthly;

  /// No description provided for @acceptTerms.
  ///
  /// In ar, this message translates to:
  /// **'أوافق على شروط العقد (الإصدار {version})'**
  String acceptTerms(String version);

  /// No description provided for @sendContractRequest.
  ///
  /// In ar, this message translates to:
  /// **'إرسال طلب العقد'**
  String get sendContractRequest;

  /// No description provided for @newContract.
  ///
  /// In ar, this message translates to:
  /// **'عقد جديد'**
  String get newContract;

  /// No description provided for @noContracts.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عقود — استأجر عاملة منزلية بعقد شهري'**
  String get noContracts;

  /// No description provided for @dayOf.
  ///
  /// In ar, this message translates to:
  /// **'اليوم {day} من {total}'**
  String dayOf(int day, int total);

  /// No description provided for @contractDetails.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل العقد'**
  String get contractDetails;

  /// No description provided for @contractReceived.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلب العقد. ستراجعه الإدارة وتعيّن لك عاملة.'**
  String get contractReceived;

  /// No description provided for @plan.
  ///
  /// In ar, this message translates to:
  /// **'الباقة'**
  String get plan;

  /// No description provided for @schedule.
  ///
  /// In ar, this message translates to:
  /// **'الدوام'**
  String get schedule;

  /// No description provided for @scheduleValue.
  ///
  /// In ar, this message translates to:
  /// **'{days} أيام × {hours} ساعات'**
  String scheduleValue(String days, String hours);

  /// No description provided for @terminatedOn.
  ///
  /// In ar, this message translates to:
  /// **'أُنهي في'**
  String get terminatedOn;

  /// No description provided for @housekeeper.
  ///
  /// In ar, this message translates to:
  /// **'العاملة'**
  String get housekeeper;

  /// No description provided for @noWorkerYet.
  ///
  /// In ar, this message translates to:
  /// **'لم تُعيَّن عاملة بعد.'**
  String get noWorkerYet;

  /// No description provided for @since.
  ///
  /// In ar, this message translates to:
  /// **'منذ {date}'**
  String since(String date);

  /// No description provided for @amountsCash.
  ///
  /// In ar, this message translates to:
  /// **'المبالغ (نقداً)'**
  String get amountsCash;

  /// No description provided for @dueOn.
  ///
  /// In ar, this message translates to:
  /// **'استحقاق {date}'**
  String dueOn(String date);

  /// No description provided for @changeRequests.
  ///
  /// In ar, this message translates to:
  /// **'طلبات الاستبدال والإنهاء'**
  String get changeRequests;

  /// No description provided for @adminReply.
  ///
  /// In ar, this message translates to:
  /// **'رد الإدارة: {reply}'**
  String adminReply(String reply);

  /// No description provided for @requestReplace.
  ///
  /// In ar, this message translates to:
  /// **'طلب استبدال العاملة'**
  String get requestReplace;

  /// No description provided for @requestTerminate.
  ///
  /// In ar, this message translates to:
  /// **'طلب إنهاء العقد'**
  String get requestTerminate;

  /// No description provided for @requestPending.
  ///
  /// In ar, this message translates to:
  /// **'لديك طلب قيد المراجعة لدى الإدارة.'**
  String get requestPending;

  /// No description provided for @cancelContract.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء العقد'**
  String get cancelContract;

  /// No description provided for @cancelContractQ.
  ///
  /// In ar, this message translates to:
  /// **'سيُلغى العقد قبل بدئه. هل أنت متأكد؟'**
  String get cancelContractQ;

  /// No description provided for @contractCancelled.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء العقد'**
  String get contractCancelled;

  /// No description provided for @cancelBeforeStart.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء العقد قبل البدء'**
  String get cancelBeforeStart;

  /// No description provided for @rateName.
  ///
  /// In ar, this message translates to:
  /// **'قيّم {name}'**
  String rateName(String name);

  /// No description provided for @fileComplaint.
  ///
  /// In ar, this message translates to:
  /// **'قدّم شكوى'**
  String get fileComplaint;

  /// No description provided for @reasonDelay.
  ///
  /// In ar, this message translates to:
  /// **'تأخر متكرر'**
  String get reasonDelay;

  /// No description provided for @reasonQuality.
  ///
  /// In ar, this message translates to:
  /// **'جودة العمل'**
  String get reasonQuality;

  /// No description provided for @reasonAbsence.
  ///
  /// In ar, this message translates to:
  /// **'غياب'**
  String get reasonAbsence;

  /// No description provided for @reasonBehavior.
  ///
  /// In ar, this message translates to:
  /// **'سلوك'**
  String get reasonBehavior;

  /// No description provided for @reasonNotNeeded.
  ///
  /// In ar, this message translates to:
  /// **'لم أعد بحاجة للخدمة'**
  String get reasonNotNeeded;

  /// No description provided for @reasonOther.
  ///
  /// In ar, this message translates to:
  /// **'سبب آخر'**
  String get reasonOther;

  /// No description provided for @reason.
  ///
  /// In ar, this message translates to:
  /// **'السبب'**
  String get reason;

  /// No description provided for @detailsOptional.
  ///
  /// In ar, this message translates to:
  /// **'التفاصيل (اختياري)'**
  String get detailsOptional;

  /// No description provided for @terminateNote.
  ///
  /// In ar, this message translates to:
  /// **'تراجع الإدارة الطلب، ويُحتسب المبلغ حتى آخر يوم عمل فعلي.'**
  String get terminateNote;

  /// No description provided for @replaceNote.
  ///
  /// In ar, this message translates to:
  /// **'تراجع الإدارة الطلب وتعيّن عاملة بديلة، ولا تُحتسب أيام الانتظار.'**
  String get replaceNote;

  /// No description provided for @requestedEndDate.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الإنهاء المطلوب: {date}'**
  String requestedEndDate(String date);

  /// No description provided for @sendRequest.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get sendRequest;

  /// No description provided for @requestSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك للإدارة'**
  String get requestSent;

  /// No description provided for @attachmentsTitle.
  ///
  /// In ar, this message translates to:
  /// **'صور مرفقة ({count}/{max})'**
  String attachmentsTitle(int count, int max);

  /// No description provided for @gallery.
  ///
  /// In ar, this message translates to:
  /// **'المعرض'**
  String get gallery;

  /// No description provided for @camera.
  ///
  /// In ar, this message translates to:
  /// **'الكاميرا'**
  String get camera;

  /// No description provided for @filesCount.
  ///
  /// In ar, this message translates to:
  /// **'{n, plural, =1{مرفق واحد} =2{مرفقان} few{{n} مرفقات} other{{n} مرفقاً}}'**
  String filesCount(int n);

  /// No description provided for @cLate.
  ///
  /// In ar, this message translates to:
  /// **'تأخر عن الموعد'**
  String get cLate;

  /// No description provided for @cQuality.
  ///
  /// In ar, this message translates to:
  /// **'جودة الخدمة'**
  String get cQuality;

  /// No description provided for @cBehavior.
  ///
  /// In ar, this message translates to:
  /// **'سلوك'**
  String get cBehavior;

  /// No description provided for @cPayment.
  ///
  /// In ar, this message translates to:
  /// **'مشكلة في الدفع'**
  String get cPayment;

  /// No description provided for @cOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get cOther;

  /// No description provided for @myComplaints.
  ///
  /// In ar, this message translates to:
  /// **'شكاواي'**
  String get myComplaints;

  /// No description provided for @noComplaints.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد شكاوى. يمكنك تقديم شكوى من صفحة الزيارة أو العقد.'**
  String get noComplaints;

  /// No description provided for @complaint.
  ///
  /// In ar, this message translates to:
  /// **'الشكوى'**
  String get complaint;

  /// No description provided for @regarding.
  ///
  /// In ar, this message translates to:
  /// **'بخصوص'**
  String get regarding;

  /// No description provided for @regardingX.
  ///
  /// In ar, this message translates to:
  /// **'بخصوص: {number}'**
  String regardingX(String number);

  /// No description provided for @complaintClosed.
  ///
  /// In ar, this message translates to:
  /// **'الشكوى مغلقة.'**
  String get complaintClosed;

  /// No description provided for @writeReply.
  ///
  /// In ar, this message translates to:
  /// **'اكتب ردك…'**
  String get writeReply;

  /// No description provided for @statusChangedTo.
  ///
  /// In ar, this message translates to:
  /// **'تغيّرت الحالة إلى \"{status}\"'**
  String statusChangedTo(String status);

  /// No description provided for @complaintSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال الشكوى — سنتواصل معك قريباً'**
  String get complaintSent;

  /// No description provided for @newComplaint.
  ///
  /// In ar, this message translates to:
  /// **'شكوى جديدة'**
  String get newComplaint;

  /// No description provided for @problemType.
  ///
  /// In ar, this message translates to:
  /// **'نوع المشكلة'**
  String get problemType;

  /// No description provided for @describeProblem.
  ///
  /// In ar, this message translates to:
  /// **'اشرح المشكلة'**
  String get describeProblem;

  /// No description provided for @sendComplaint.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الشكوى'**
  String get sendComplaint;

  /// No description provided for @notifications.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات'**
  String get notifications;

  /// No description provided for @readAll.
  ///
  /// In ar, this message translates to:
  /// **'قراءة الكل'**
  String get readAll;

  /// No description provided for @noNotifications.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إشعارات'**
  String get noNotifications;

  /// No description provided for @notifyOrders.
  ///
  /// In ar, this message translates to:
  /// **'إشعارات الطلبات والعقود'**
  String get notifyOrders;

  /// No description provided for @notifyComplaints.
  ///
  /// In ar, this message translates to:
  /// **'إشعارات الشكاوى'**
  String get notifyComplaints;

  /// No description provided for @editProfile.
  ///
  /// In ar, this message translates to:
  /// **'تعديل الملف الشخصي'**
  String get editProfile;

  /// No description provided for @phoneLocked.
  ///
  /// In ar, this message translates to:
  /// **'لتغيير الرقم تواصل مع الوكالة'**
  String get phoneLocked;

  /// No description provided for @changePassword.
  ///
  /// In ar, this message translates to:
  /// **'تغيير كلمة المرور'**
  String get changePassword;

  /// No description provided for @currentPassword.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور الحالية'**
  String get currentPassword;

  /// No description provided for @newPassword.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور الجديدة'**
  String get newPassword;

  /// No description provided for @confirmPassword.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد كلمة المرور'**
  String get confirmPassword;

  /// No description provided for @passwordsDontMatch.
  ///
  /// In ar, this message translates to:
  /// **'كلمتا المرور غير متطابقتين'**
  String get passwordsDontMatch;

  /// No description provided for @passwordChanged.
  ///
  /// In ar, this message translates to:
  /// **'تم تغيير كلمة المرور'**
  String get passwordChanged;

  /// No description provided for @otherDevicesLoggedOut.
  ///
  /// In ar, this message translates to:
  /// **'سيتم تسجيل الخروج من الأجهزة الأخرى.'**
  String get otherDevicesLoggedOut;

  /// No description provided for @logout.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get logout;

  /// No description provided for @logoutQ.
  ///
  /// In ar, this message translates to:
  /// **'هل تريد تسجيل الخروج من هذا الجهاز؟'**
  String get logoutQ;

  /// No description provided for @logoutShort.
  ///
  /// In ar, this message translates to:
  /// **'خروج'**
  String get logoutShort;

  /// No description provided for @scopeNew.
  ///
  /// In ar, this message translates to:
  /// **'جديدة'**
  String get scopeNew;

  /// No description provided for @scopeUpcoming.
  ///
  /// In ar, this message translates to:
  /// **'القادمة'**
  String get scopeUpcoming;

  /// No description provided for @scopeDone.
  ///
  /// In ar, this message translates to:
  /// **'المنجزة'**
  String get scopeDone;

  /// No description provided for @myTeam.
  ///
  /// In ar, this message translates to:
  /// **'فريقي'**
  String get myTeam;

  /// No description provided for @youAreLeader.
  ///
  /// In ar, this message translates to:
  /// **'أنت قائد الفريق'**
  String get youAreLeader;

  /// No description provided for @memberNote.
  ///
  /// In ar, this message translates to:
  /// **'عضو — القائد يحدّث الحالة'**
  String get memberNote;

  /// No description provided for @noTeam.
  ///
  /// In ar, this message translates to:
  /// **'لم تُضَف إلى فريق بعد. تواصل مع الإدارة.'**
  String get noTeam;

  /// No description provided for @noVisitsHere.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد زيارات هنا'**
  String get noVisitsHere;

  /// No description provided for @awaitingYourAcceptance.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار قبولك'**
  String get awaitingYourAcceptance;

  /// No description provided for @myRatings.
  ///
  /// In ar, this message translates to:
  /// **'تقييماتي'**
  String get myRatings;

  /// No description provided for @assignment.
  ///
  /// In ar, this message translates to:
  /// **'الإسناد'**
  String get assignment;

  /// No description provided for @customerAndLocation.
  ///
  /// In ar, this message translates to:
  /// **'العميل والموقع'**
  String get customerAndLocation;

  /// No description provided for @customer.
  ///
  /// In ar, this message translates to:
  /// **'العميل'**
  String get customer;

  /// No description provided for @description.
  ///
  /// In ar, this message translates to:
  /// **'وصف'**
  String get description;

  /// No description provided for @customerNotes.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات العميل'**
  String get customerNotes;

  /// No description provided for @callCustomer.
  ///
  /// In ar, this message translates to:
  /// **'اتصال بالعميل  {phone}'**
  String callCustomer(String phone);

  /// No description provided for @amountToCollect.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ المطلوب تحصيله نقداً عند الإتمام'**
  String get amountToCollect;

  /// No description provided for @leaderOnlyNote.
  ///
  /// In ar, this message translates to:
  /// **'قائد الفريق هو من يقبل الزيارة ويحدّث حالتها.'**
  String get leaderOnlyNote;

  /// No description provided for @acceptVisit.
  ///
  /// In ar, this message translates to:
  /// **'قبول الزيارة'**
  String get acceptVisit;

  /// No description provided for @visitAccepted.
  ///
  /// In ar, this message translates to:
  /// **'تم قبول الزيارة'**
  String get visitAccepted;

  /// No description provided for @reject.
  ///
  /// In ar, this message translates to:
  /// **'رفض'**
  String get reject;

  /// No description provided for @rejectAssignment.
  ///
  /// In ar, this message translates to:
  /// **'رفض الإسناد'**
  String get rejectAssignment;

  /// No description provided for @rejectReason.
  ///
  /// In ar, this message translates to:
  /// **'سبب الرفض'**
  String get rejectReason;

  /// No description provided for @rejectedBack.
  ///
  /// In ar, this message translates to:
  /// **'تم الرفض — أُعيدت للإدارة'**
  String get rejectedBack;

  /// No description provided for @completeVisit.
  ///
  /// In ar, this message translates to:
  /// **'إتمام الزيارة'**
  String get completeVisit;

  /// No description provided for @completeVisitQ.
  ///
  /// In ar, this message translates to:
  /// **'هل استلمت {amount} نقداً من العميل؟'**
  String completeVisitQ(String amount);

  /// No description provided for @yesReceived.
  ///
  /// In ar, this message translates to:
  /// **'نعم، تم الاستلام'**
  String get yesReceived;

  /// No description provided for @updatedTo.
  ///
  /// In ar, this message translates to:
  /// **'تم التحديث: {status}'**
  String updatedTo(String status);

  /// No description provided for @noAssignedContracts.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عقود مسندة إليك حالياً'**
  String get noAssignedContracts;

  /// No description provided for @currentContract.
  ///
  /// In ar, this message translates to:
  /// **'عقدي الحالي'**
  String get currentContract;

  /// No description provided for @otherContracts.
  ///
  /// In ar, this message translates to:
  /// **'العقود السابقة والقادمة'**
  String get otherContracts;

  /// No description provided for @contract.
  ///
  /// In ar, this message translates to:
  /// **'العقد'**
  String get contract;

  /// No description provided for @myPeriod.
  ///
  /// In ar, this message translates to:
  /// **'فترتي'**
  String get myPeriod;

  /// No description provided for @contactDuringPeriodOnly.
  ///
  /// In ar, this message translates to:
  /// **'تظهر بيانات التواصل والعنوان خلال فترة عملك فقط.'**
  String get contactDuringPeriodOnly;

  /// No description provided for @averageOf.
  ///
  /// In ar, this message translates to:
  /// **'متوسط {n} تقييم'**
  String averageOf(int n);

  /// No description provided for @noRatings.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد تقييمات بعد'**
  String get noRatings;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
