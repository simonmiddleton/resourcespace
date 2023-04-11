<?php


$lang["emu_configuration"]='تكوين EMu';
$lang["emu_api_settings"]='إعدادات خادم API';
$lang["emu_api_server"]='عنوان الخادم (مثال: http://[server.address])';
$lang["emu_api_server_port"]='منفذ الخادم (Minfud Alkhadem)';
$lang["emu_resource_types"]='حدد أنواع الموارد المرتبطة بـ EMu.';
$lang["emu_email_notify"]='عنوان البريد الإلكتروني الذي سيتم إرسال الإشعارات إليه. اتركه فارغًا للعودة إلى عنوان البريد الإلكتروني الافتراضي للنظام.';
$lang["emu_script_failure_notify_days"]='عدد الأيام بعد الذي يتم فيه عرض التنبيه وإرسال البريد الإلكتروني إذا لم يتم الانتهاء من النص البرمجي.';
$lang["emu_script_header"]='تمكين البرنامج النصي الذي سيقوم بتحديث بيانات EMu تلقائيًا كلما قام ResourceSpace بتشغيل مهمته المجدولة (cron_copy_hitcount.php)';
$lang["emu_last_run_date"]='تم تشغيل النص الأخير';
$lang["emu_script_mode"]='وضع النصوص البرمجية';
$lang["emu_script_mode_option_1"]='استيراد البيانات الوصفية من برنامج EMu';
$lang["emu_script_mode_option_2"]='استرجاع جميع سجلات EMu والحفاظ على تزامن RS و EMu.';
$lang["emu_enable_script"]='تمكين نص برنامج EMu';
$lang["emu_test_mode"]='وضع الاختبار - عيّنه على القيمة الصحيحة وسيتم تشغيل البرنامج النصي ولكن لن يتم تحديث الموارد.';
$lang["emu_interval_run"]='تشغيل النص البرمجي في فترة زمنية محددة (على سبيل المثال، +1 يوم، +2 أسابيع، فترة أسبوعين). اتركه فارغًا وسيتم تشغيله في كل مرة يتم فيها تشغيل cron_copy_hitcount.php.';
$lang["emu_log_directory"]='الدليل الذي يتم فيه تخزين سجلات النصوص البرمجية. إذا ترك هذا الحقل فارغًا أو كان غير صالح، فلن يتم تسجيل أي سجلات.';
$lang["emu_created_by_script_field"]='حقل البيانات الوصفية المستخدم لتخزين ما إذا كان المورد قد تم إنشاؤه بواسطة نص EMu.';
$lang["emu_settings_header"]='إعدادات EMu';
$lang["emu_irn_field"]='حقل البيانات الوصفية المستخدم لتخزين معرف EMu (IRN)';
$lang["emu_search_criteria"]='معايير البحث لمزامنة EMu مع ResourceSpace.';
$lang["emu_rs_mappings_header"]='قواعد تعيين EMu - ResourceSpace.';
$lang["emu_module"]='وحدة EMu';
$lang["emu_column_name"]='عمود وحدة EMu';
$lang["emu_rs_field"]='حقل ResourceSpace';
$lang["emu_add_mapping"]='إضافة تعيين الخرائط.';
$lang["emu_upload_emu_field_label"]='EMu IRN تعني رقم تعريف الموارد في برنامج إدارة الموارد الرقمية EMu.';
$lang["emu_confirm_upload_nodata"]='يرجى التحقق من الخانة لتأكيد رغبتك في المتابعة مع عملية الرفع.';
$lang["emu_test_script_title"]='اختبار / تشغيل النص البرمجي';
$lang["emu_run_script"]='العملية';
$lang["emu_script_problem"]='تحذير - لم يتم إكمال نص الـ EMu بنجاح خلال الـ %days% الأيام الماضية. وقت آخر تشغيل:';
$lang["emu_no_resource"]='لم يتم تحديد معرف المورد!';
$lang["emu_upload_nodata"]='لم يتم العثور على بيانات EMu لهذا الرقم المعرف الداخلي (IRN).';
$lang["emu_nodata_returned"]='لم يتم العثور على بيانات EMu لرقم IRN المحدد.';
$lang["emu_createdfromemu"]='تم إنشاؤه باستخدام مكوّن إضافي EMU.';