<?php


$lang["emu_configuration"]='EMu 구성';
$lang["emu_api_settings"]='API 서버 설정';
$lang["emu_api_server"]='서버 주소 (예: http://[server.address])';
$lang["emu_api_server_port"]='서버 포트';
$lang["emu_resource_types"]='EMu와 연결된 리소스 유형 선택';
$lang["emu_email_notify"]='스크립트가 알림을 보낼 이메일 주소입니다. 시스템 알림 주소를 기본값으로 사용하려면 비워 두십시오';
$lang["emu_script_failure_notify_days"]='스크립트가 완료되지 않은 경우 경고를 표시하고 이메일을 보낼 일 수';
$lang["emu_script_header"]='ResourceSpace가 예약된 작업(cron_copy_hitcount.php)을 실행할 때마다 EMu 데이터를 자동으로 업데이트하는 스크립트를 활성화합니다.';
$lang["emu_last_run_date"]='마지막 스크립트 실행';
$lang["emu_script_mode"]='스크립트 모드';
$lang["emu_script_mode_option_1"]='EMu에서 메타데이터 가져오기';
$lang["emu_script_mode_option_2"]='모든 EMu 레코드를 가져와 RS와 EMu를 동기화 유지';
$lang["emu_enable_script"]='EMu 스크립트 활성화';
$lang["emu_test_mode"]='테스트 모드 - true로 설정하면 스크립트가 실행되지만 리소스는 업데이트되지 않습니다';
$lang["emu_interval_run"]='다음 간격으로 스크립트를 실행하십시오 (예: +1일, +2주, 2주). 비워두면 cron_copy_hitcount.php가 실행될 때마다 실행됩니다.';
$lang["emu_log_directory"]='스크립트 로그를 저장할 디렉토리입니다. 이 항목을 비워두거나 유효하지 않은 경우 로그가 기록되지 않습니다.';
$lang["emu_created_by_script_field"]='메타데이터 필드는 리소스가 EMu 스크립트에 의해 생성되었는지 여부를 저장하는 데 사용됩니다';
$lang["emu_settings_header"]='EMu 설정';
$lang["emu_irn_field"]='EMu 식별자 (IRN)를 저장하는 메타데이터 필드';
$lang["emu_search_criteria"]='ResourceSpace와 EMu 동기화를 위한 검색 기준';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace 매핑 규칙';
$lang["emu_module"]='EMu 모듈';
$lang["emu_column_name"]='EMu 모듈 열';
$lang["emu_rs_field"]='ResourceSpace 필드';
$lang["emu_add_mapping"]='매핑 추가';
$lang["emu_confirm_upload_nodata"]='업로드를 진행하려면 확인란을 선택하세요';
$lang["emu_test_script_title"]='테스트/ 스크립트 실행';
$lang["emu_run_script"]='처리';
$lang["emu_script_problem"]='경고 - EMu 스크립트가 지난 %days%일 동안 성공적으로 완료되지 않았습니다. 마지막 실행 시간:';
$lang["emu_no_resource"]='리소스 ID가 지정되지 않았습니다!';
$lang["emu_upload_nodata"]='이 IRN에 대한 EMu 데이터가 없습니다:';
$lang["emu_nodata_returned"]='지정된 IRN에 대한 EMu 데이터를 찾을 수 없습니다.';
$lang["emu_createdfromemu"]='EMU 플러그인에서 생성됨';