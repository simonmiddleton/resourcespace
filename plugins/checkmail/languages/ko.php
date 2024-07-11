<?php


$lang["checkmail_configuration"]='메일 확인 설정';
$lang["checkmail_install_php_imap_extension"]='1단계: php imap 확장 기능을 설치하세요.';
$lang["checkmail_cronhelp"]='이 플러그인은 업로드를 위해 파일을 수신하는 전용 이메일 계정에 시스템이 로그인할 수 있도록 특별한 설정이 필요합니다.<br /><br />계정에서 IMAP이 활성화되어 있는지 확인하십시오. Gmail 계정을 사용하는 경우 설정->POP/IMAP->IMAP 사용 설정에서 IMAP을 활성화하십시오.<br /><br />초기 설정 시, plugins/checkmail/pages/cron_check_email.php를 명령줄에서 수동으로 실행하여 작동 방식을 이해하는 것이 가장 도움이 될 수 있습니다. 올바르게 연결되고 스크립트 작동 방식을 이해한 후에는 매 1~2분마다 실행되도록 크론 작업을 설정해야 합니다.<br />이 작업은 메일박스를 스캔하고 실행당 한 개의 읽지 않은 이메일을 읽습니다.<br /><br />2분마다 실행되는 크론 작업의 예:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='귀하의 IMAP 계정은 마지막으로 [lastcheck]에 확인되었습니다.';
$lang["checkmail_cronjobprob"]='체크메일 크론잡이 제대로 실행되지 않을 수 있습니다. 마지막 실행 후 5분 이상이 지났기 때문입니다.<br /><br />
매 분마다 실행되는 크론잡의 예시:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap 서버<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='이메일';
$lang["checkmail_password"]='비밀번호';
$lang["checkmail_extension_mapping"]='파일 확장자에 따른 리소스 유형 매핑';
$lang["checkmail_default_resource_type"]='기본 리소스 유형';
$lang["checkmail_extension_mapping_desc"]='기본 리소스 유형 선택기 다음에는 각 리소스 유형에 대해 하나의 입력란이 있습니다. <br />다른 유형의 업로드된 파일을 특정 리소스 유형으로 강제 지정하려면 쉼표로 구분된 파일 확장자 목록을 추가하십시오 (예: jpg,gif,png).';
$lang["checkmail_resource_type_population"]='(허용된 확장자에서)';
$lang["checkmail_subject_field"]='주제 필드';
$lang["checkmail_body_field"]='본문 필드';
$lang["checkmail_purge"]='업로드 후 이메일을 삭제하시겠습니까?';
$lang["checkmail_confirm"]='확인 이메일을 보내시겠습니까?';
$lang["checkmail_users"]='허용된 사용자';
$lang["checkmail_blocked_users_label"]='차단된 사용자';
$lang["checkmail_default_access"]='기본 접근';
$lang["checkmail_default_archive"]='기본 상태';
$lang["checkmail_html"]='HTML 콘텐츠 허용? (실험적, 권장하지 않음)';
$lang["checkmail_mail_skipped"]='건너뛴 이메일';
$lang["checkmail_allow_users_based_on_permission_label"]='사용자가 업로드 권한에 따라 허용되어야 합니까?';
$lang["addresourcesviaemail"]='이메일로 추가';
$lang["uploadviaemail"]='이메일로 추가';
$lang["uploadviaemail-intro"]='<p>이메일을 통해 업로드하려면 파일을 첨부하고 이메일을 <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>로 보내십시오.</p> <p>반드시 <b>[fromaddress]</b>에서 보내야 하며, 그렇지 않으면 무시됩니다.</p><p>이메일의 제목에 있는 모든 내용은 [applicationname]의 [subjectfield] 필드에 들어갑니다.</p><p>또한 이메일 본문에 있는 모든 내용은 [applicationname]의 [bodyfield] 필드에 들어갑니다.</p> <p>여러 파일은 컬렉션으로 그룹화됩니다. 리소스의 기본 접근 수준은 <b>\'[access]\'</b>이며, 아카이브 상태는 <b>\'[archive]\'</b>입니다.</p><p> [confirmation]</p>';
$lang["checkmail_confirmation_message"]='이메일이 성공적으로 처리되면 확인 이메일을 받게 됩니다. 이메일이 어떤 이유로든 (예: 잘못된 주소에서 발송된 경우) 프로그램적으로 건너뛰어진 경우, 관리자는 주의가 필요한 이메일이 있음을 통지받게 됩니다.';
$lang["yourresourcehasbeenuploaded"]='리소스가 업로드되었습니다';
$lang["yourresourceshavebeenuploaded"]='귀하의 리소스가 업로드되었습니다';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), ID [user-ref] 및 이메일 [user-email]은(는) 이메일을 통해 업로드할 수 없습니다 (권한 "c" 또는 "d"를 확인하거나 checkmail 설정 페이지에서 차단된 사용자를 확인하십시오). 기록된 시간: [datetime].';
$lang["checkmail_createdfromcheckmail"]='메일 확인 플러그인에서 생성됨';
$lang["plugin-checkmail-title"]='메일 확인';
$lang["plugin-checkmail-desc"]='[고급] 이메일 첨부 파일의 수집을 허용합니다';