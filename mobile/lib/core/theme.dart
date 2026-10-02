import 'package:flutter/cupertino.dart' show CupertinoPageTransitionsBuilder;
import 'package:flutter/material.dart';

/// ألوان Design System (design/assets/tokens.css) بنسختين: فاتح وداكن.
/// الوضع يتبع إعداد الجهاز تلقائياً — تغييره يعيد بناء التطبيق (main.dart).
class AppColors {
  static bool dark = false;

  static Color _p(int light, int darkValue) => Color(dark ? darkValue : light);

  /// لون الهوية الثابت (الشعار، شاشة البداية، البطاقة الرئيسية) — لا يتغير مع الوضع.
  static const brand = Color(0xFF1A499F);
  static const brandDeep = Color(0xFF132F62);
  static const brandBright = Color(0xFF3570DA);

  static Color get bg => _p(0xFFF5F7FB, 0xFF0B1220);
  static Color get surface => _p(0xFFFFFFFF, 0xFF141C2C);
  static Color get surfaceHigh => _p(0xFFFFFFFF, 0xFF1B2538);

  static Color get blue50 => _p(0xFFEEF4FE, 0xFF15254A);
  static Color get blue100 => _p(0xFFDAE6FC, 0xFF1D3363);
  static Color get blue600 => _p(0xFF1F5AC4, 0xFF5A8BE7); // Primary
  static Color get blue700 => _p(0xFF1A499F, 0xFF9DBCF4); // نص على خلفية فاتحة
  static Color get blue900 => _p(0xFF132F62, 0xFFDAE6FC);

  static Color get success600 => _p(0xFF1C7A4E, 0xFF5BCB8E);
  static Color get success50 => _p(0xFFEAF6EF, 0xFF0F2D1F);
  static Color get warning600 => _p(0xFF8F6200, 0xFFE6B84C);
  static Color get warning50 => _p(0xFFFBF4E2, 0xFF30260C);
  static Color get danger600 => _p(0xFFB42318, 0xFFF47C72);
  static Color get danger50 => _p(0xFFFDEEEC, 0xFF3A1714);

  static Color get gray50 => _p(0xFFF5F7FB, 0xFF0B1220);
  static Color get gray200 => _p(0xFFE3E6EB, 0xFF26324A);
  static Color get gray500 => _p(0xFF6B7280, 0xFF97A3B8);
  static Color get gray900 => _p(0xFF111827, 0xFFE8ECF3);

  static const star = Color(0xFFF5B301);

  /// تدرج الهوية للبطاقات والرؤوس.
  static const brandGradient = LinearGradient(
    begin: AlignmentDirectional.topStart,
    end: AlignmentDirectional.bottomEnd,
    colors: [brandBright, brand, brandDeep],
  );

  static List<BoxShadow> get softShadow => dark
      ? const []
      : [BoxShadow(color: const Color(0xFF1A499F).withValues(alpha: .07), blurRadius: 18, offset: const Offset(0, 6))];
}

ThemeData buildTheme(Brightness brightness) {
  AppColors.dark = brightness == Brightness.dark;
  final scheme = ColorScheme.fromSeed(
    seedColor: AppColors.brand,
    brightness: brightness,
    primary: AppColors.blue600,
    surface: AppColors.surface,
  );
  final base = ThemeData(useMaterial3: true, colorScheme: scheme, fontFamily: 'IBMPlexSansArabic', brightness: brightness);
  final text = base.textTheme.apply(bodyColor: AppColors.gray900, displayColor: AppColors.gray900);

  final radius = BorderRadius.circular(14);
  return base.copyWith(
    textTheme: text,
    scaffoldBackgroundColor: AppColors.bg,
    pageTransitionsTheme: PageTransitionsTheme(builders: {
      TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
      TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
    }),
    appBarTheme: AppBarTheme(
      backgroundColor: AppColors.bg,
      foregroundColor: AppColors.gray900,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      titleTextStyle: text.titleLarge?.copyWith(fontWeight: FontWeight.w700),
    ),
    cardTheme: CardThemeData(
      color: AppColors.surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: radius, side: BorderSide(color: AppColors.gray200.withValues(alpha: AppColors.dark ? 1 : .7))),
    ),
    dividerTheme: DividerThemeData(color: AppColors.gray200, space: 1),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: AppColors.gray200)),
      enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: AppColors.gray200)),
      focusedBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: AppColors.blue600, width: 1.6)),
      contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 15),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: AppColors.blue600,
        foregroundColor: Colors.white,
        minimumSize: Size.fromHeight(54),
        shape: RoundedRectangleBorder(borderRadius: radius),
        textStyle: text.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: Size.fromHeight(50),
        shape: RoundedRectangleBorder(borderRadius: radius),
        side: BorderSide(color: AppColors.gray200),
      ),
    ),
    chipTheme: base.chipTheme.copyWith(
      backgroundColor: AppColors.surface,
      selectedColor: AppColors.blue100,
      side: BorderSide(color: AppColors.gray200),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ),
    bottomSheetTheme: BottomSheetThemeData(
      backgroundColor: AppColors.surface,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      showDragHandle: true,
    ),
    dialogTheme: DialogThemeData(backgroundColor: AppColors.surfaceHigh, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: AppColors.surface,
      indicatorColor: AppColors.blue100,
      elevation: 0,
      height: 68,
      labelTextStyle: WidgetStatePropertyAll(text.labelMedium?.copyWith(fontWeight: FontWeight.w600)),
    ),
    tabBarTheme: TabBarThemeData(labelColor: AppColors.blue600, indicatorColor: AppColors.blue600, unselectedLabelColor: AppColors.gray500),
    snackBarTheme: SnackBarThemeData(shape: RoundedRectangleBorder(borderRadius: radius)),
  );
}
