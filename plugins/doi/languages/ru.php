<?php


$lang["status4"]='Несменяемый.';
$lang["doi_info_link"]='на <a target="_blank" href="https://ru.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>.';
$lang["doi_info_metadata_schema"]='На регистрации DOI на DataCite.org указаны в <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Документации по схеме метаданных Datacite</a>.';
$lang["doi_info_mds_api"]='Информация об использовании DOI-API, используемого этим плагином, указана в <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Документации по API Datacite</a>.';
$lang["doi_plugin_heading"]='Этот плагин создает <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> для неизменяемых объектов и коллекций перед их регистрацией в <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Дополнительная информация.';
$lang["doi_setup_doi_prefix"]='Префикс для генерации DOI (цифрового идентификатора объекта)';
$lang["doi_info_prefix"]='о префиксах <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">DOI</a>.';
$lang["doi_setup_use_testmode"]='Используйте <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовый режим</a>.';
$lang["doi_info_testmode"]='в режиме тестирования <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmode</a>.';
$lang["doi_setup_use_testprefix"]='Используйте префикс <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test (10.5072)</a> вместо.';
$lang["doi_info_testprefix"]='на <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">тестовом префиксе</a>.';
$lang["doi_setup_publisher"]='Издатель';
$lang["doi_resource_conditions_title"]='Ресурс должен соответствовать следующим предварительным условиям, чтобы квалифицироваться для регистрации DOI:';
$lang["doi_resource_conditions"]='<li>Ваш проект должен быть публичным, то есть иметь общедоступную область.</li>
<li>Ресурс должен быть общедоступным, то есть иметь установленный доступ <strong>открытый</strong>.</li>
<li>Ресурс должен иметь <strong>название</strong>.</li>
<li>Он должен быть помечен как {status}, то есть иметь установленное состояние <strong>{status}</strong>.</li>
<li>Затем только <strong>администратор</strong> может начать процесс регистрации.</li>';
$lang["doi_setup_general_config"]='Общая конфигурация.';
$lang["doi_setup_pref_fields_header"]='Предпочтительные поля поиска для создания метаданных.';
$lang["doi_setup_username"]='Имя пользователя DataCite.';
$lang["doi_setup_password"]='Пароль DataCite.';
$lang["doi_pref_publicationYear_fields"]='Ищите <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a> в:<br>(В случае, если значение не найдено, будет использоваться год регистрации.)';
$lang["doi_pref_creator_fields"]='Искать <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Создатель</a> в:';
$lang["doi_pref_title_fields"]='Искать <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Заголовок</a> в:';
$lang["doi_setup_default"]='Если значение не найдено, используйте <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">стандартный код</a>:';
$lang["doi_setup_test_plugin"]='Тестовый плагин.';
$lang["doi_setup_test_succeeded"]='Тест пройден успешно!';
$lang["doi_setup_test_failed"]='Тест не пройден!';
$lang["doi_alert_text"]='Внимание! После отправки DOI в DataCite регистрация не может быть отменена.';
$lang["doi_title_compulsory"]='Пожалуйста, установите заголовок перед продолжением регистрации DOI.';
$lang["doi_register"]='Регистрация';
$lang["doi_cancel"]='Отменить';
$lang["doi_sure"]='Внимание! После отправки DOI в DataCite регистрация не может быть отменена. Информация, уже зарегистрированная в хранилище метаданных DataCite, возможно, будет перезаписана.';
$lang["doi_already_set"]='уже установлено';
$lang["doi_not_yet_set"]='еще не установлено';
$lang["doi_already_registered"]='уже зарегистрирован.';
$lang["doi_not_yet_registered"]='еще не зарегистрирован';
$lang["doi_successfully_registered"]='был успешно зарегистрирован.';
$lang["doi_successfully_registered_pl"]='Ресурс(ы) был(и) успешно зарегистрирован(ы).';
$lang["doi_not_successfully_registered"]='Не удалось зарегистрировать правильно.';
$lang["doi_not_successfully_registered_pl"]='Не удалось зарегистрировать правильно.';
$lang["doi_reload"]='Перезагрузить.';
$lang["doi_successfully_set"]='было установлено.';
$lang["doi_not_successfully_set"]='Не было установлено.';
$lang["doi_sum_already_reg"]='Ресурс(ы) уже имеет/имеют DOI.';
$lang["doi_sum_not_yet_archived"]='Ресурс(ы) не отмечен(ы).';
$lang["doi_sum_not_yet_archived_2"]='Его/их доступ еще не установлен на открытый.';
$lang["doi_sum_ready_for_reg"]='Ресурс(ы) готов(ы) к регистрации.';
$lang["doi_sum_no_title"]='ресурс(ы) все еще нуждаются в названии. Использование:';
$lang["doi_sum_no_title_2"]='Как заголовок вместо этого.';
$lang["doi_register_all"]='Зарегистрируйте DOI для всех ресурсов в этой коллекции.';
$lang["doi_sure_register_resource"]='Продолжить регистрацию x ресурсов?';
$lang["doi_show_meta"]='Показать метаданные DOI.';
$lang["doi_hide_meta"]='Скрыть метаданные DOI.';
$lang["doi_fetched_xml_from_MDS"]='Текущие метаданные XMl были успешно получены из хранилища метаданных DataCite.';
$lang["doi_info_publisher"]='на поле <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">издатель</a>.';