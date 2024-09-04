<?php


$lang["status4"]='Незмінний';
$lang["doi_info_wikipedia"]='https://uk.wikipedia.org/wiki/Digital_Object_Identifier';
$lang["doi_info_link"]='на <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>.';
$lang["doi_info_metadata_schema"]='у реєстрації DOI на DataCite.org зазначені в <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Документації схеми метаданих Datacite</a>.';
$lang["doi_info_mds_api"]='у DOI-API, що використовується цим плагіном, зазначені в <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Документації API Datacite</a>.';
$lang["doi_plugin_heading"]='Цей плагін створює <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> для незмінних об\'єктів і колекцій перед їх реєстрацією на <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Додаткова інформація';
$lang["doi_setup_doi_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">Префікс</a> для генерації <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>';
$lang["doi_info_prefix"]='на <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">префіксах doi</a>.';
$lang["doi_setup_use_testmode"]='Використовуйте <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовий режим</a>';
$lang["doi_info_testmode"]='у <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовому режимі</a>.';
$lang["doi_setup_use_testprefix"]='Використовуйте <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовий префікс (10.5072)</a> замість цього';
$lang["doi_info_testprefix"]='на <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовому префіксі</a>.';
$lang["doi_setup_publisher"]='<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Видавець</a>';
$lang["doi_info_publisher"]='на полі <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">видавця</a>';
$lang["doi_resource_conditions_title"]='Ресурс повинен відповідати наступним передумовам, щоб мати право на реєстрацію DOI:';
$lang["doi_resource_conditions"]='<li>Ваш Проект повинен бути публічним, тобто мати публічну зону.</li>
<li>Ресурс повинен бути публічно доступним, тобто мати доступ, встановлений на <strong>відкритий</strong>.</li>
<li>Ресурс повинен мати <strong>назву</strong>.</li>
<li>Він повинен бути позначений як {status}, тобто мати стан, встановлений на <strong>{status}</strong>.</li>
<li>Тоді лише <strong>адміністратор</strong> має право розпочати процес реєстрації.</li>';
$lang["doi_setup_general_config"]='Загальна конфігурація';
$lang["doi_setup_pref_fields_header"]='Бажані поля пошуку для створення метаданих';
$lang["doi_setup_username"]='Ім\'я користувача DataCite';
$lang["doi_setup_password"]='Пароль DataCite';
$lang["doi_pref_publicationYear_fields"]='Шукайте <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Рік публікації</a> в:<br>(Якщо значення не знайдено, буде використано рік реєстрації.)';
$lang["doi_pref_creator_fields"]='Шукайте <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Творця</a> в:';
$lang["doi_pref_title_fields"]='Шукати <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Назву</a> в:';
$lang["doi_setup_default"]='Якщо значення не знайдено, використовуйте <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">стандартний код</a>:';
$lang["doi_setup_test_plugin"]='Тестовий плагін..';
$lang["doi_setup_test_succeeded"]='Тест пройшов успішно!';
$lang["doi_setup_test_failed"]='Тест не пройдено!';
$lang["doi_alert_text"]='Увага! Після відправлення DOI до DataCite реєстрацію не можна скасувати.';
$lang["doi_title_compulsory"]='Будь ласка, встановіть заголовок перед продовженням реєстрації DOI.';
$lang["doi_register"]='Зареєструватися';
$lang["doi_cancel"]='Скасувати';
$lang["doi_sure"]='Увага! Після відправлення DOI до DataCite, реєстрацію не можна буде скасувати. Інформація, яка вже зареєстрована в Metadata Store DataCite, можливо, буде перезаписана.';
$lang["doi_already_set"]='вже встановлено';
$lang["doi_not_yet_set"]='ще не встановлено';
$lang["doi_already_registered"]='вже зареєстрований';
$lang["doi_not_yet_registered"]='ще не зареєстрований';
$lang["doi_successfully_registered"]='успішно зареєстровано';
$lang["doi_successfully_registered_pl"]='ресурс(и) було успішно зареєстровано.';
$lang["doi_not_successfully_registered"]='не вдалося зареєструвати належним чином';
$lang["doi_not_successfully_registered_pl"]='не вдалося зареєструвати правильно.';
$lang["doi_reload"]='Перезавантажити';
$lang["doi_successfully_set"]='було встановлено';
$lang["doi_not_successfully_set"]='не встановлено';
$lang["doi_sum_of"]='з';
$lang["doi_sum_already_reg"]='ресурс(и) вже має(ють) DOI.';
$lang["doi_sum_not_yet_archived"]='ресурс(и) не позначено';
$lang["doi_sum_not_yet_archived_2"]='ще або їхній доступ не встановлено як відкритий.';
$lang["doi_sum_ready_for_reg"]='ресурс(и) готовий(і) до реєстрації.';
$lang["doi_sum_no_title"]='ресурс(и) все ще потребують назви. Використання';
$lang["doi_sum_no_title_2"]='як заголовок замість цього тоді';
$lang["doi_register_all"]='Зареєструвати DOI для всіх ресурсів у цій колекції';
$lang["doi_sure_register_resource"]='Продовжити реєстрацію x ресурсу(ів)?';
$lang["doi_show_meta"]='Показати метадані DOI';
$lang["doi_hide_meta"]='Приховати метадані DOI';
$lang["doi_fetched_xml_from_MDS"]='Поточні метадані XMl успішно отримані з сховища метаданих DataCite.';