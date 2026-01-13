# .idx/dev.nix
{ pkgs, ... }: {
  # تحديث القناة لنسخة أحدث لضمان توافق أفضل
  channel = "stable-24.05";

  # الحزم: قمت بتوحيد النسخ (PHP 8.2) وإضافة unzip الضروري لـ Composer
  packages = [
    pkgs.php82
    pkgs.php82Packages.composer
    pkgs.git
    pkgs.unzip  # مهم جدًا: بدونه يفشل Composer في فك ضغط المكتبات ويعلق النظام
    pkgs.curl
  ];

  # متغيرات البيئة (اختياري لكن مفيد)
  env = {};

  idx = {
    # إضافات VS Code
    extensions = [
      "bmewburn.vscode-intelephense-client"
    ];

    # أوامر تجهيز مساحة العمل
    workspace = {
      # يعمل مرة واحدة فقط عند إنشاء البيئة
      onCreate = {
        # قمت بتفعيل الأمر لضمان تثبيت المكتبات فوراً
        # استخدام --no-interaction يمنع توقف السكربت لسؤالك
        install-dependencies = "composer install --no-interaction --prefer-dist";
      };

      # يعمل في كل مرة تعيد تشغيل البيئة
      onStart = {
        # أمر اختياري للتأكد من تحديث الـ autoloader
        dump-autoload = "composer dump-autoload";
      };
    };
  };
}