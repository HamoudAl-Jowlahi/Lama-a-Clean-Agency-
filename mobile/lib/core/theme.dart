import 'package:flutter/material.dart';

/// ألوان Design System (design/assets/tokens.css).
class AppColors {
  static const blue50 = Color(0xFFEEF4FE);
  static const blue100 = Color(0xFFDAE6FC);
  static const blue600 = Color(0xFF1F5AC4); // Primary
  static const blue700 = Color(0xFF1A499F); // الشعار
  static const blue900 = Color(0xFF132F62);
  static const success600 = Color(0xFF1C7A4E);
  static const success50 = Color(0xFFEAF6EF);
  static const warning600 = Color(0xFF8F6200);
  static const warning50 = Color(0xFFFBF4E2);
  static const danger600 = Color(0xFFB42318);
  static const danger50 = Color(0xFFFDEEEC);
  static const gray50 = Color(0xFFF7F8FA);
  static const gray200 = Color(0xFFE3E6EB);
  static const gray500 = Color(0xFF6B7280);
  static const gray900 = Color(0xFF111827);
}

ThemeData buildTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: AppColors.blue600,
    primary: AppColors.blue600,
    surface: Colors.white,
  );
  final base = ThemeData(useMaterial3: true, colorScheme: scheme, fontFamily: 'IBMPlexSansArabic');
  final text = base.textTheme.apply(
    bodyColor: AppColors.gray900,
    displayColor: AppColors.gray900,
  );

  final radius = BorderRadius.circular(12);
  return base.copyWith(
    textTheme: text,
    scaffoldBackgroundColor: AppColors.gray50,
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: AppColors.gray900,
      elevation: 0,
      scrolledUnderElevation: 1,
      centerTitle: false,
      titleTextStyle: text.titleLarge?.copyWith(fontWeight: FontWeight.w700),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: radius, side: const BorderSide(color: AppColors.gray200)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: radius, borderSide: const BorderSide(color: AppColors.gray200)),
      enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: const BorderSide(color: AppColors.gray200)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: radius),
        textStyle: text.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: RoundedRectangleBorder(borderRadius: radius),
      ),
    ),
    navigationBarTheme: const NavigationBarThemeData(backgroundColor: Colors.white, indicatorColor: AppColors.blue100),
  );
}
