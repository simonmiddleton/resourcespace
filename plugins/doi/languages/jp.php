<?php


$lang["status4"]='不変の (Fuhenn no)';
$lang["doi_info_link"]='DOIsについて。';
$lang["doi_info_metadata_schema"]='DOI登録に関する情報は、<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Dataciteメタデータスキーマドキュメント</a>に記載されています。';
$lang["doi_info_mds_api"]='このプラグインで使用されるDOI-APIに関する情報は、<a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite APIドキュメンテーション</a>に記載されています。';
$lang["doi_plugin_heading"]='このプラグインは、不変のオブジェクトやコレクションに<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>を作成し、それらを<a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>に登録します。';
$lang["doi_further_information"]='詳細情報';
$lang["doi_setup_doi_prefix"]='DOI生成のための<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">接頭辞</a>';
$lang["doi_info_prefix"]='DOIプレフィックスについて。';
$lang["doi_setup_use_testmode"]='テストモードを使用してください。<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">こちら</a>を参照してください。';
$lang["doi_info_testmode"]='テストモードで。';
$lang["doi_setup_use_testprefix"]='代わりに<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">テストプレフィックス（10.5072）</a>を使用してください。';
$lang["doi_info_testprefix"]='テストプレフィックス上で。';
$lang["doi_setup_publisher"]='出版者';
$lang["doi_info_publisher"]='「出版者」フィールドについて。';
$lang["doi_resource_conditions_title"]='DOI登録の資格を得るためには、リソースが以下の前提条件を満たす必要があります。';
$lang["doi_resource_conditions"]='<li>あなたのプロジェクトは公開する必要があります。つまり、公開エリアを持っている必要があります。</li>
<li>リソースは公開される必要があります。つまり、アクセスが「オープン」に設定されている必要があります。</li>
<li>リソースには<strong>タイトル</strong>が必要です。</li>
<li>リソースは{status}とマークする必要があります。つまり、状態が<strong>{status}</strong>に設定されている必要があります。</li>
<li>その後、登録プロセスを開始するのは<strong>管理者</strong>のみが許可されます。</li>';
$lang["doi_setup_general_config"]='一般設定';
$lang["doi_setup_pref_fields_header"]='メタデータ構築のための優先検索フィールド';
$lang["doi_setup_username"]='DataCiteのユーザー名';
$lang["doi_setup_password"]='DataCiteのパスワード';
$lang["doi_pref_publicationYear_fields"]='<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a>を検索してください。<br>（値が見つからない場合は、登録年が使用されます。）';
$lang["doi_pref_creator_fields"]='「Creator」を以下で検索してください：<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a>';
$lang["doi_pref_title_fields"]='以下で<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">タイトル</a>を検索してください：';
$lang["doi_setup_default"]='値が見つからない場合は、<a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">標準コード</a>を使用してください。';
$lang["doi_setup_test_plugin"]='テストプラグイン..';
$lang["doi_setup_test_succeeded"]='テストに成功しました！';
$lang["doi_setup_test_failed"]='テストに失敗しました！';
$lang["doi_alert_text"]='注意！DOIがDataCiteに送信されると、登録を元に戻すことはできません。';
$lang["doi_title_compulsory"]='DOI登録を続ける前に、タイトルを設定してください。';
$lang["doi_register"]='登録する';
$lang["doi_cancel"]='キャンセルする';
$lang["doi_sure"]='注意！DOIがDataCiteに送信されると、登録を元に戻すことはできません。DataCiteのメタデータストアにすでに登録されている情報が上書きされる可能性があります。';
$lang["doi_already_set"]='すでに設定済み';
$lang["doi_not_yet_set"]='まだ設定されていません。';
$lang["doi_already_registered"]='すでに登録されています。';
$lang["doi_not_yet_registered"]='まだ登録されていません。';
$lang["doi_successfully_registered"]='正常に登録されました。';
$lang["doi_successfully_registered_pl"]='リソースが正常に登録されました。';
$lang["doi_not_successfully_registered"]='正しく登録できませんでした。';
$lang["doi_not_successfully_registered_pl"]='正しく登録できませんでした。';
$lang["doi_reload"]='リロード';
$lang["doi_successfully_set"]='設定されました。';
$lang["doi_not_successfully_set"]='設定されていません。';
$lang["doi_sum_of"]='の (no)';
$lang["doi_sum_already_reg"]='リソースにはすでにDOIがあります。';
$lang["doi_sum_not_yet_archived"]='リソースはマークされていません。';
$lang["doi_sum_not_yet_archived_2"]='まだ、またはそのアクセスは公開に設定されていません。';
$lang["doi_sum_ready_for_reg"]='リソースは登録の準備ができています。';
$lang["doi_sum_no_title"]='リソースにはまだタイトルが必要です。使用中です。';
$lang["doi_sum_no_title_2"]='タイトルとして。';
$lang["doi_register_all"]='このコレクション内のすべてのリソースにDOIを登録してください。';
$lang["doi_sure_register_resource"]='x個のリソースを登録しますか？';
$lang["doi_show_meta"]='DOIメタデータを表示する';
$lang["doi_hide_meta"]='DOIメタデータを非表示にする';
$lang["doi_fetched_xml_from_MDS"]='DataCiteのメタデータストアから現在のXMLメタデータを正常に取得できました。';