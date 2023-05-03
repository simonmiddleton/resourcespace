<?php


$lang["checkmail_configuration"]='メール設定の確認';
$lang["checkmail_install_php_imap_extension"]='ステップ1：php imap拡張機能をインストールしてください。';
$lang["checkmail_cronhelp"]='このプラグインを使用するには、アップロード用に専用のメールアカウントにログインするためのシステムの特別な設定が必要です。<br /><br />アカウントでIMAPが有効になっていることを確認してください。Gmailアカウントを使用している場合は、設定->POP/IMAP->IMAPを有効にするでIMAPを有効にしてください。<br /><br />
初期設定では、コマンドラインでplugins/checkmail/pages/cron_check_email.phpを手動で実行して、その動作を理解するのが最も役立つ場合があります。
正しく接続でき、スクリプトの動作を理解したら、cronジョブを設定して1〜2分ごとに実行する必要があります。<br />メールボックスをスキャンし、1回の実行ごとに未読のメールを1つ読み取ります。<br /><br />
2分ごとに実行される例のcronジョブ：<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='あなたのIMAPアカウントは、[lastcheck]に最後にチェックされました。';
$lang["checkmail_cronjobprob"]='あなたのチェックメールのクロンジョブが正しく実行されていない可能性があります。最後に実行されてから5分以上経過しているためです。<br /><br />
1分ごとに実行される例として、以下のようなクロンジョブがあります：<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='IMAPサーバー<br />(Gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_password"]='パスワード';
$lang["checkmail_extension_mapping"]='ファイル拡張子マッピングによるリソースタイプ';
$lang["checkmail_default_resource_type"]='デフォルトのリソースタイプ';
$lang["checkmail_extension_mapping_desc"]='デフォルトのリソースタイプセレクターの下に、各リソースタイプごとに1つの入力欄があります。<br />アップロードされた異なるタイプのファイルを特定のリソースタイプに強制するには、ファイル拡張子のカンマ区切りリストを追加してください（例：jpg、gif、png）。';
$lang["checkmail_subject_field"]='件名フィールド';
$lang["checkmail_body_field"]='本文フィールド';
$lang["checkmail_purge"]='アップロード後にメールを削除しますか？';
$lang["checkmail_confirm"]='確認メールを送信しますか？';
$lang["checkmail_users"]='許可されたユーザー';
$lang["checkmail_blocked_users_label"]='ブロックされたユーザー';
$lang["checkmail_default_access"]='デフォルトアクセス';
$lang["checkmail_default_archive"]='デフォルトのステータス';
$lang["checkmail_html"]='HTMLコンテンツを許可しますか？（実験的であり、推奨されません）';
$lang["checkmail_mail_skipped"]='スキップされたメール';
$lang["checkmail_allow_users_based_on_permission_label"]='ユーザーがアップロードを許可されるべきかどうかは、権限に基づいて決定されるべきですか？';
$lang["addresourcesviaemail"]='Eメールで追加する';
$lang["uploadviaemail"]='Eメールで追加する';
$lang["uploadviaemail-intro"]='電子メールでアップロードするには、ファイルを添付して電子メールを<b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>宛に送信してください。</p> <p>送信元アドレスは<b>[fromaddress]</b>から送信してください。そうでない場合は無視されます。</p><p>電子メールの件名にあるものは、[subjectfield]フィールドに入力されます。</p><p>また、電子メールの本文にあるものは、[bodyfield]フィールドに入力されます。</p> <p>複数のファイルはコレクションにグループ化されます。リソースは、アクセスレベルが<b>\'[access]\'</b>、アーカイブステータスが<b>\'[archive]\'</b>にデフォルトで設定されます。</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='あなたのメールが正常に処理された場合、確認メールが送信されます。もし、あなたのメールがプログラム的にスキップされた場合（例えば、誤ったアドレスから送信された場合など）、管理者には対応が必要なメールがあることが通知されます。';
$lang["yourresourcehasbeenuploaded"]='リソースがアップロードされました。';
$lang["yourresourceshavebeenuploaded"]='あなたのリソースがアップロードされました。';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username])、ID [user-ref]、およびメールアドレス [user-email] は、Eメールによるアップロードが許可されていません（権限「c」または「d」を確認するか、checkmail設定ページでブロックされたユーザーを確認してください）。記録日時：[datetime]。';
$lang["checkmail_createdfromcheckmail"]='「Check Mail」プラグインから作成されました。';
$lang["checkmail_email"]='電子メール (でんしメール)';