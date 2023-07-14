<?php


$lang["emu_configuration"]='EMu 配置';
$lang["emu_api_settings"]='API服务器设置';
$lang["emu_api_server"]='服务器地址（例如http://[server.address]）';
$lang["emu_api_server_port"]='服务器端口';
$lang["emu_resource_types"]='选择与 EMu 相关联的资源类型。';
$lang["emu_email_notify"]='脚本将发送通知的电子邮件地址。留空以使用系统通知地址。';
$lang["emu_script_failure_notify_days"]='若脚本未完成，多少天后显示警报并发送电子邮件。';
$lang["emu_script_header"]='启用脚本，每当ResourceSpace运行其计划任务（cron_copy_hitcount.php）时，将自动更新EMu数据。';
$lang["emu_last_run_date"]='脚本上次运行时间';
$lang["emu_script_mode"]='脚本模式';
$lang["emu_script_mode_option_1"]='从 EMu 导入元数据';
$lang["emu_script_mode_option_2"]='提取所有 EMu 记录并保持 RS 和 EMu 同步。';
$lang["emu_enable_script"]='启用 EMu 脚本';
$lang["emu_test_mode"]='测试模式 - 设置为 true，脚本将运行但不会更新资源。';
$lang["emu_log_directory"]='存储脚本日志的目录。如果留空或无效，则不会发生记录日志。';
$lang["emu_created_by_script_field"]='用于存储资源是否由 EMu 脚本创建的元数据字段';
$lang["emu_settings_header"]='EMu设置';
$lang["emu_irn_field"]='用于存储 EMu 标识符 (IRN) 的元数据字段';
$lang["emu_search_criteria"]='同步 EMu 和 ResourceSpace 的搜索条件';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace映射规则';
$lang["emu_module"]='EMu模块';
$lang["emu_column_name"]='EMu模块列';
$lang["emu_rs_field"]='ResourceSpace字段';
$lang["emu_add_mapping"]='添加映射 (Tiānjiā yìngpìan)';
$lang["emu_confirm_upload_nodata"]='请勾选该框以确认您希望继续上传。';
$lang["emu_test_script_title"]='测试/运行脚本';
$lang["emu_run_script"]='处理 (Chǔ lǐ)';
$lang["emu_script_problem"]='警告 - EMu脚本在过去的%days%天内未成功完成。上次运行时间：';
$lang["emu_no_resource"]='未指定资源ID！';
$lang["emu_upload_nodata"]='未找到此IRN的EMu数据：';
$lang["emu_nodata_returned"]='指定的IRN未找到EMu数据。';
$lang["emu_createdfromemu"]='从EMU插件创建';