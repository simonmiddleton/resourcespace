<?php


$lang["emu_configuration"]='EMuの設定';
$lang["emu_api_settings"]='APIサーバーの設定';
$lang["emu_api_server"]='サーバーアドレス（例：http://[server.address]）';
$lang["emu_api_server_port"]='サーバーポート';
$lang["emu_resource_types"]='EMuにリンクされたリソースタイプを選択してください。';
$lang["emu_email_notify"]='スクリプトが通知を送信する電子メールアドレス。システム通知アドレスにデフォルトにする場合は空白のままにしてください。';
$lang["emu_script_failure_notify_days"]='スクリプトが完了していない場合にアラートを表示し、メールを送信する日数。';
$lang["emu_script_header"]='ResourceSpaceがスケジュールされたタスク（cron_copy_hitcount.php）を実行するたびに、EMuデータを自動的に更新するスクリプトを有効にしてください。';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>スクリプトの最終実行</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='スクリプトモード';
$lang["emu_script_mode_option_1"]='EMuからメタデータをインポートする。';
$lang["emu_script_mode_option_2"]='すべてのEMuレコードを取得し、RSとEMuを同期させます。';
$lang["emu_enable_script"]='EMuスクリプトを有効にする。';
$lang["emu_test_mode"]='テストモード - trueに設定すると、スクリプトは実行されますが、リソースは更新されません。';
$lang["emu_interval_run"]='次の間隔でスクリプトを実行します（例：+1日、+2週間、2週間）。空白にしておくと、cron_copy_hitcount.php が実行されるたびに実行されます。';
$lang["emu_log_directory"]='スクリプトログを保存するディレクトリ。もし空欄であるか無効な場合、ログは記録されません。';
$lang["emu_created_by_script_field"]='EMuスクリプトによって作成されたリソースかどうかを保存するために使用されるメタデータフィールド。';
$lang["emu_settings_header"]='EMuの設定';
$lang["emu_irn_field"]='EMu識別子（IRN）を保存するために使用されるメタデータフィールド。';
$lang["emu_search_criteria"]='EMuとResourceSpaceを同期するための検索基準';
$lang["emu_module"]='EMuモジュール';
$lang["emu_column_name"]='EMuモジュールの列';
$lang["emu_rs_field"]='ResourceSpaceのフィールド';
$lang["emu_add_mapping"]='マッピングを追加する';
$lang["emu_upload_emu_field_label"]='EMu IRNの翻訳はありません。EMuはコレクション管理ソフトウェアであり、IRNはオブジェクトの識別番号を表します。そのため、EMu IRNは「EMuのオブジェクトの識別番号」という意味です。';
$lang["emu_confirm_upload_nodata"]='アップロードを続行することを確認するために、チェックボックスを確認してください。';
$lang["emu_test_script_title"]='テスト/スクリプトの実行';
$lang["emu_run_script"]='処理 (しょり)';
$lang["emu_script_problem"]='警告 - EMuスクリプトが過去%days%日間に正常に完了していません。最終実行時間：';
$lang["emu_no_resource"]='リソースIDが指定されていません！';
$lang["emu_upload_nodata"]='このIRNに対するEMuデータが見つかりませんでした。';
$lang["emu_nodata_returned"]='指定されたIRNに対するEMuデータが見つかりませんでした。';
$lang["emu_createdfromemu"]='EMUプラグインから作成されました。';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpaceのマッピングルール';