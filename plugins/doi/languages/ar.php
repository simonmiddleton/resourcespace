<?php


$lang["status4"]='غير قابل للتغيير';
$lang["doi_info_link"]='على مُعرّفات الكائن الرقمي (DOIs) <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">(المعرّفات الرقمية للكائنات)</a>.';
$lang["doi_info_metadata_schema"]='يتم ذكر التفاصيل المتعلقة بتسجيل DOI على DataCite.org في <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">وثائق مخطط بيانات Datacite Metadata</a>.';
$lang["doi_info_mds_api"]='يتم تحديد تفاصيل واجهة برمجة تطبيقات DOI-API المستخدمة من قبل هذه الإضافة في <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">وثائق واجهة برمجة تطبيقات Datacite</a>.';
$lang["doi_plugin_heading"]='يقوم هذا الملحق بإنشاء مُعرفات الكائن الرقمي (DOIs) للكائنات والمجموعات الثابتة قبل تسجيلها في DataCite.';
$lang["doi_further_information"]='مزيد من المعلومات';
$lang["doi_setup_doi_prefix"]='البادئة لتوليد مُعرف الكائن الرقمي (DOI)';
$lang["doi_info_prefix"]='عنوان الـ DOI الرئيسي.';
$lang["doi_setup_use_testmode"]='استخدم وضع الاختبار <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmode</a>';
$lang["doi_info_testmode"]='في وضع الاختبار.';
$lang["doi_setup_use_testprefix"]='استخدم البادئة التجريبية <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">(10.5072)</a> بدلاً من ذلك.';
$lang["doi_info_testprefix"]='على بادئة الاختبار <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefix</a>.';
$lang["doi_setup_publisher"]='الناشر.';
$lang["doi_info_publisher"]='على حقل الناشر <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">.</a>';
$lang["doi_resource_conditions_title"]='يجب على المورد تحقيق الشروط الأساسية التالية ليتمكن من التسجيل في معرف DOI.';
$lang["doi_resource_conditions"]='<li>يجب أن يكون مشروعك عامًا، أي يجب أن يحتوي على منطقة عامة.</li>
<li>يجب أن يكون المورد متاحًا للجمهور، أي يجب أن يتم تعيين الوصول إليه بـ<strong>مفتوح</strong>.</li>
<li>يجب أن يحتوي المورد على <strong>عنوان</strong>.</li>
<li>يجب أن يتم وضع علامة {status}، أي يجب أن يتم تعيين حالته إلى <strong>{status}</strong>.</li>
<li>ثم، يسمح فقط للمسؤول ببدء عملية التسجيل.</li>';
$lang["doi_setup_general_config"]='تكوين عام';
$lang["doi_setup_pref_fields_header"]='الحقول المفضلة للبحث لإنشاء البيانات الوصفية.';
$lang["doi_setup_username"]='اسم المستخدم في DataCite';
$lang["doi_setup_password"]='كلمة مرور DataCite';
$lang["doi_pref_publicationYear_fields"]='البحث عن <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">سنة النشر</a> في: <br> (في حالة عدم العثور على قيمة، سيتم استخدام سنة التسجيل.)';
$lang["doi_pref_creator_fields"]='البحث عن <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">المُنشئ</a> في:';
$lang["doi_pref_title_fields"]='البحث عن <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">العنوان</a> في:';
$lang["doi_setup_default"]='إذا لم يتم العثور على قيمة، استخدم <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">الكود القياسي</a>:';
$lang["doi_setup_test_plugin"]='إضافة اختبار..';
$lang["doi_setup_test_succeeded"]='نجح الاختبار!';
$lang["doi_setup_test_failed"]='فشل الاختبار!';
$lang["doi_alert_text"]='تنبيه! بمجرد إرسال رقم DOI إلى DataCite، لا يمكن التراجع عن التسجيل.';
$lang["doi_title_compulsory"]='يرجى تعيين عنوان قبل الاستمرار في تسجيل DOI.';
$lang["doi_register"]='تسجيل';
$lang["doi_cancel"]='إلغاء';
$lang["doi_sure"]='تنبيه! بمجرد إرسال رقم DOI إلى DataCite، لا يمكن التراجع عن التسجيل. سيتم ربما الكتابة فوق المعلومات التي تم تسجيلها بالفعل في مخزن بيانات DataCite.';
$lang["doi_already_set"]='تم تعيينه بالفعل';
$lang["doi_not_yet_set"]='لم يتم تعيينه بعد.';
$lang["doi_already_registered"]='تم التسجيل بالفعل.';
$lang["doi_not_yet_registered"]='لم يتم التسجيل بعد.';
$lang["doi_successfully_registered"]='تم التسجيل بنجاح.';
$lang["doi_successfully_registered_pl"]='تم تسجيل المورد (الموارد) بنجاح.';
$lang["doi_not_successfully_registered"]='لا يمكن التسجيل بشكل صحيح.';
$lang["doi_not_successfully_registered_pl"]='لا يمكن التسجيل بشكل صحيح.';
$lang["doi_reload"]='إعادة التحميل';
$lang["doi_successfully_set"]='تم تعيينه.';
$lang["doi_not_successfully_set"]='لم يتم تعيينه.';
$lang["doi_sum_of"]='من';
$lang["doi_sum_already_reg"]='المورد (الموارد) لديه (لديها) بالفعل مُعرف الكائن الرقمي (DOI).';
$lang["doi_sum_not_yet_archived"]='المورد (الموارد) لم يتم وضع علامة عليها.';
$lang["doi_sum_not_yet_archived_2"]='لم يتم تعيين الوصول حتى الآن أو لم يتم تعيين فتح الوصول له / لها / لهما.';
$lang["doi_sum_ready_for_reg"]='المورد/الموارد جاهزة للتسجيل.';
$lang["doi_sum_no_title"]='المصادر لا تزال بحاجة إلى عنوان. استخدام:';
$lang["doi_sum_no_title_2"]='كعنوان بدلاً من ذلك.';
$lang["doi_register_all"]='تسجيل DOIs لجميع الموارد في هذه المجموعة.';
$lang["doi_sure_register_resource"]='هل ترغب في متابعة تسجيل x موردًا؟';
$lang["doi_show_meta"]='عرض بيانات التعريف الرقمي للموارد (DOI)';
$lang["doi_hide_meta"]='إخفاء بيانات DOI الوصفية';
$lang["doi_fetched_xml_from_MDS"]='يمكن جلب البيانات الوصفية الحالية لـ XML بنجاح من مخزن بيانات DataCite.';