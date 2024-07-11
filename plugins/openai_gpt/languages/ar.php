<?php


$lang["openai_gpt_title"]='التكامل مع OpenAI';
$lang["property-openai_gpt_prompt"]='النص المحفِّز لـ GPT';
$lang["property-openai_gpt_input_field"]='حقل إدخال GPT';
$lang["openai_gpt_model"]='اسم نموذج API المستخدم (على سبيل المثال \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='بادئة تنبيه الإكمال';
$lang["openai_gpt_prompt_return_json"]='لاحقة تعليمات الإكمال (لإرجاع JSON لحقول القوائم الثابتة)';
$lang["openai_gpt_prompt_return_text"]='لاحقة تعليمات الإكمال (لإرجاع النص في حقول النص)';
$lang["openai_gpt_temperature"]='درجة حرارة العينة بين 0 و 1 (القيم الأعلى تعني أن النموذج سيتحمل مزيدًا من المخاطر)';
$lang["openai_gpt_max_tokens"]='أقصى عدد من الرموز (التوكنز)';
$lang["openai_gpt_advanced"]='تحذير - هذا القسم مخصص لأغراض الاختبار فقط ولا ينبغي تغييره على الأنظمة الحية. سيؤثر تغيير أي من خيارات المكون الإضافي هنا على سلوك جميع حقول البيانات الوصفية التي تم تكوينها. يرجى التعديل بحذر!';
$lang["openai_gpt_system_message"]='النص الأولي لرسالة النظام. سيتم استبدال العناصر النائبة %%IN_TYPE%% و %%OUT_TYPE%% بـ \'text\' أو \'json\' اعتمادًا على أنواع الحقول المصدر/الهدف';
$lang["openai_gpt_intro"]='يضيف البيانات الوصفية التي يتم إنشاؤها عن طريق تمرير البيانات الحالية إلى واجهة برمجة تطبيقات OpenAI مع مطالبة قابلة للتخصيص. راجع <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> لمزيد من المعلومات التفصيلية.';
$lang["openai_gpt_api_key"]='مفتاح API الخاص بـ OpenAI. احصل على مفتاح API الخاص بك من <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='تكامل OpenAI API GPT';
$lang["plugin-openai_gpt-desc"]='تم إنشاء البيانات الوصفية بواسطة OpenAI. ينقل بيانات الحقول المُعدة إلى واجهة برمجة تطبيقات OpenAI ويخزن المعلومات المُعادة.';