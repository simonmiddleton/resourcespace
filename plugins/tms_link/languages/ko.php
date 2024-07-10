<?php


$lang["tms_link_configuration"]='TMS 링크 구성';
$lang["tms_link_dsn_name"]='TMS 데이터베이스에 연결할 로컬 DSN의 이름. Windows에서는 관리 도구->데이터 원본(ODBC)에서 구성됩니다. 올바른 연결이 구성되었는지 확인하십시오 (32/64 비트)';
$lang["tms_link_table_name"]='TMS 데이터를 검색하는 데 사용되는 TMS 테이블 또는 뷰의 이름';
$lang["tms_link_user"]='TMS 데이터베이스 연결을 위한 사용자 이름';
$lang["tms_link_password"]='TMS 데이터베이스 사용자 비밀번호';
$lang["tms_link_resource_types"]='TMS에 연결된 리소스 유형 선택';
$lang["tms_link_object_id_field"]='TMS 객체 ID를 저장하는 필드';
$lang["tms_link_checksum_field"]='체크섬을 저장하는 데 사용할 메타데이터 필드. 데이터가 변경되지 않은 경우 불필요한 업데이트를 방지하기 위함입니다';
$lang["tms_link_checksum_column_name"]='TMS 데이터베이스에서 반환된 체크섬에 사용할 TMS 테이블에서 반환된 열.';
$lang["tms_link_tms_data"]='실시간 TMS 데이터';
$lang["tms_link_database_setup"]='TMS 데이터베이스 연결';
$lang["tms_link_metadata_setup"]='TMS 메타데이터 구성';
$lang["tms_link_tms_link_success"]='연결 성공';
$lang["tms_link_tms_link_failure"]='연결 실패. 세부 정보를 확인하십시오.';
$lang["tms_link_test_link"]='TMS로 테스트 링크';
$lang["tms_link_tms_resources"]='TMS 리소스';
$lang["tms_link_no_tms_resources"]='TMS 리소스를 찾을 수 없습니다. 플러그인이 올바르게 구성되었는지, 올바른 ObjectID 메타데이터 및 체크섬 필드가 매핑되었는지 확인하십시오.';
$lang["tms_link_no_resource"]='지정된 리소스 없음';
$lang["tms_link_resource_id"]='리소스 ID';
$lang["tms_link_object_id"]='객체 ID';
$lang["tms_link_checksum"]='체크섬';
$lang["tms_link_no_tms_data"]='TMS에서 데이터가 반환되지 않음';
$lang["tms_link_field_mappings"]='TMS 필드를 ResourceSpace 필드로 매핑';
$lang["tms_link_resourcespace_field"]='ResourceSpace 필드';
$lang["tms_link_column_name"]='TMS 열';
$lang["tms_link_add_mapping"]='매핑 추가';
$lang["tms_link_performance_options"]='TMS 스크립트 설정 - 이 설정은 TMS에서 리소스 데이터를 업데이트하는 예약 작업에 영향을 미칩니다';
$lang["tms_link_query_chunk_size"]='각 청크에서 TMS에서 검색할 레코드 수. 최적의 설정을 찾기 위해 조정할 수 있습니다.';
$lang["tms_link_test_mode"]='테스트 모드 - true로 설정하면 스크립트가 실행되지만 리소스는 업데이트되지 않습니다';
$lang["tms_link_email_notify"]='스크립트가 알림을 보낼 이메일 주소. 비워두면 시스템 알림 주소로 기본 설정됩니다.';
$lang["tms_link_test_count"]='스크립트를 테스트할 레코드 수 - 스크립트와 성능을 테스트하기 위해 더 낮은 숫자로 설정할 수 있습니다';
$lang["tms_link_last_run_date"]='<strong>마지막 스크립트 실행: </strong>';
$lang["tms_link_script_failure_notify_days"]='스크립트가 완료되지 않은 경우 경고를 표시하고 이메일을 보낼 일 수';
$lang["tms_link_script_problem"]='경고 - TMS 스크립트가 지난 %days%일 동안 성공적으로 완료되지 않았습니다. 마지막 실행 시간:';
$lang["tms_link_upload_tms_field"]='TMS 객체 ID';
$lang["tms_link_upload_nodata"]='이 ObjectID에 대한 TMS 데이터를 찾을 수 없습니다:';
$lang["tms_link_confirm_upload_nodata"]='업로드를 진행하려면 확인란을 선택하세요';
$lang["tms_link_enable_update_script"]='TMS 업데이트 스크립트 활성화';
$lang["tms_link_enable_update_script_info"]='ResourceSpace 예약 작업(cron_copy_hitcount.php)이 실행될 때마다 TMS 데이터를 자동으로 업데이트하는 스크립트를 활성화합니다.';
$lang["tms_link_log_directory"]='스크립트 로그를 저장할 디렉토리입니다. 이 항목을 비워두거나 유효하지 않은 경우 로그가 기록되지 않습니다.';
$lang["tms_link_log_expiry"]='스크립트 로그를 저장할 일수. 이 디렉토리의 TMS 로그 중 오래된 것은 삭제됩니다';
$lang["tms_link_column_type_required"]='<strong>참고</strong>: 새 열을 추가하는 경우, 새 열에 숫자 데이터 또는 텍스트 데이터가 포함되어 있는지 나타내기 위해 아래의 적절한 목록에 열 이름을 추가하십시오.';
$lang["tms_link_numeric_columns"]='UTF-8로 가져와야 하는 열 목록';
$lang["tms_link_text_columns"]='UTF-16으로 가져와야 하는 열 목록';
$lang["tms_link_bidirectional_options"]='양방향 동기화 (RS 이미지를 TMS에 추가)';
$lang["tms_link_push_condition"]='이미지가 TMS에 추가되기 위해 충족해야 하는 메타데이터 기준';
$lang["tms_link_tms_loginid"]='ResourceSpace가 레코드를 삽입하는 데 사용할 TMS 로그인 ID. 이 ID로 TMS 계정이 생성되거나 존재해야 합니다';
$lang["tms_link_push_image"]='미리보기 생성 후 이미지를 TMS로 푸시하시겠습니까? (이 작업은 TMS에 새로운 미디어 기록을 생성합니다)';
$lang["tms_link_push_image_sizes"]='TMS로 보낼 선호 미리보기 크기. 쉼표로 구분하여 선호 순서대로 나열하면 첫 번째 사용 가능한 크기가 사용됩니다';
$lang["tms_link_mediatypeid"]='삽입된 미디어 레코드에 사용할 MediaTypeID';
$lang["tms_link_formatid"]='삽입된 미디어 레코드에 사용할 FormatID';
$lang["tms_link_colordepthid"]='삽입된 미디어 레코드에 사용할 ColorDepthID';
$lang["tms_link_media_path"]='TMS에 저장될 파일 저장소의 루트 경로 예: \\RS_SERVERilestore\\. 끝에 슬래시가 포함되어야 합니다. TMS에 저장된 파일 이름은 파일 저장소 루트에서 상대 경로를 포함합니다.';
$lang["tms_link_mediapaths_resource_reference_column"]='MediaMaster 테이블에서 리소스 ID를 저장하는 데 사용할 열. 이는 선택 사항이며 여러 리소스가 동일한 Media Master ID를 사용하는 것을 방지하는 데 사용됩니다.';
$lang["tms_link_modules_mappings"]='추가 모듈(테이블/뷰)에서 동기화';
$lang["tms_link_module"]='모듈';
$lang["tms_link_tms_uid_field"]='TMS UID 필드';
$lang["tms_link_rs_uid_field"]='ResourceSpace UID 필드';
$lang["tms_link_applicable_rt"]='적용 가능한 리소스 유형(들)';
$lang["tms_link_modules_mappings_tools"]='도구';
$lang["tms_link_add_new_tms_module"]='새로운 추가 TMS 모듈 추가';
$lang["tms_link_tms_module_configuration"]='TMS 모듈 구성';
$lang["tms_link_tms_module_name"]='TMS 모듈 이름';
$lang["tms_link_encoding"]='인코딩';
$lang["tms_link_not_found_error_title"]='찾을 수 없음';
$lang["tms_link_not_deleted_error_detail"]='요청된 모듈 구성을 삭제할 수 없습니다.';
$lang["tms_link_uid_field"]='TMS %module_name %tms_uid_field';
$lang["tms_link_confirm_delete_module_config"]='이 모듈 구성을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다!';
$lang["tms_link_write_to_debug_log"]='시스템 디버그 로그에 스크립트 진행 상황 포함 (디버그 로깅은 별도로 구성해야 함). 주의: 디버그 로그 파일이 빠르게 증가할 수 있음.';