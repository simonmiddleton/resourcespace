<?php


$lang["status4"]='변경 불가';
$lang["doi_info_wikipedia"]='https://en.wikipedia.org/wiki/Digital_Object_Identifier';
$lang["doi_info_link"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>에 대해.';
$lang["doi_info_metadata_schema"]='DataCite.org에서 DOI 등록에 대한 내용은 <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Datacite 메타데이터 스키마 문서</a>에 명시되어 있습니다.';
$lang["doi_info_mds_api"]='이 플러그인이 사용하는 DOI-API에 대한 내용은 <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API 문서</a>에 명시되어 있습니다.';
$lang["doi_plugin_heading"]='이 플러그인은 변경 불가능한 객체와 컬렉션에 대해 <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>를 생성한 후 <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>에 등록합니다.';
$lang["doi_further_information"]='추가 정보';
$lang["doi_setup_doi_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">접두사</a> for <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> 생성';
$lang["doi_info_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">DOI 접두사</a>에 대해.';
$lang["doi_setup_use_testmode"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">테스트 모드</a> 사용';
$lang["doi_info_testmode"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">테스트 모드</a>에서.';
$lang["doi_setup_use_testprefix"]='대신 <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">테스트 접두사 (10.5072)</a>를 사용하세요';
$lang["doi_info_testprefix"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">테스트 접두사</a>에서.';
$lang["doi_setup_publisher"]='<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">출판사</a>';
$lang["doi_info_publisher"]='<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">발행자</a> 필드에서.';
$lang["doi_resource_conditions_title"]='리소스가 DOI 등록 자격을 얻으려면 다음 전제 조건을 충족해야 합니다:';
$lang["doi_resource_conditions"]='<li>프로젝트는 공개 영역을 가져야 합니다.</li>
<li>리소스는 공개적으로 접근 가능해야 하며, 접근 설정이 <strong>공개</strong>로 설정되어야 합니다.</li>
<li>리소스는 <strong>제목</strong>을 가져야 합니다.</li>
<li>상태가 {status}로 설정되어야 합니다.</li>
<li>그런 다음, <strong>관리자</strong>만 등록 프로세스를 시작할 수 있습니다.</li>';
$lang["doi_setup_general_config"]='일반 설정';
$lang["doi_setup_pref_fields_header"]='메타데이터 구성을 위한 선호 검색 필드';
$lang["doi_setup_username"]='DataCite 사용자 이름';
$lang["doi_setup_password"]='DataCite 비밀번호';
$lang["doi_pref_publicationYear_fields"]='다음을 찾아보세요 <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a> 에서:<br>(값을 찾을 수 없는 경우, 등록 연도가 사용됩니다.)';
$lang["doi_pref_creator_fields"]='다음에서 <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">제작자</a> 찾기:';
$lang["doi_pref_title_fields"]='다음에서 <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">제목</a>을 찾으세요:';
$lang["doi_setup_default"]='값을 찾을 수 없는 경우, <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">표준 코드</a>를 사용하십시오:';
$lang["doi_setup_test_plugin"]='테스트 플러그인..';
$lang["doi_setup_test_succeeded"]='테스트 성공!';
$lang["doi_setup_test_failed"]='테스트 실패!';
$lang["doi_alert_text"]='주의! DOI가 DataCite로 전송되면 등록을 취소할 수 없습니다.';
$lang["doi_title_compulsory"]='DOI 등록을 계속하기 전에 제목을 설정해 주세요.';
$lang["doi_register"]='등록';
$lang["doi_cancel"]='취소';
$lang["doi_sure"]='주의! DOI가 DataCite로 전송되면 등록을 취소할 수 없습니다. DataCite의 메타데이터 저장소에 이미 등록된 정보는 덮어쓰여질 수 있습니다.';
$lang["doi_already_set"]='이미 설정됨';
$lang["doi_not_yet_set"]='아직 설정되지 않음';
$lang["doi_already_registered"]='이미 등록됨';
$lang["doi_not_yet_registered"]='아직 등록되지 않음';
$lang["doi_successfully_registered"]='성공적으로 등록되었습니다';
$lang["doi_successfully_registered_pl"]='리소스가 성공적으로 등록되었습니다.';
$lang["doi_not_successfully_registered"]='정상적으로 등록되지 않았습니다';
$lang["doi_not_successfully_registered_pl"]='정상적으로 등록되지 않았습니다.';
$lang["doi_reload"]='새로 고침';
$lang["doi_successfully_set"]='설정되었습니다.';
$lang["doi_not_successfully_set"]='설정되지 않았습니다.';
$lang["doi_sum_of"]='의';
$lang["doi_sum_already_reg"]='리소스에 이미 DOI가 있습니다.';
$lang["doi_sum_not_yet_archived"]='리소스가 표시되지 않음';
$lang["doi_sum_not_yet_archived_2"]='아직 또는 그들의 접근이 공개로 설정되지 않았습니다.';
$lang["doi_sum_ready_for_reg"]='리소스가 등록 준비되었습니다.';
$lang["doi_sum_no_title"]='리소스에 제목이 필요합니다. 사용 중';
$lang["doi_sum_no_title_2"]='대신 제목으로.';
$lang["doi_register_all"]='이 컬렉션의 모든 리소스에 대해 DOI 등록';
$lang["doi_sure_register_resource"]='x개의 리소스를 등록하시겠습니까?';
$lang["doi_show_meta"]='DOI 메타데이터 표시';
$lang["doi_hide_meta"]='DOI 메타데이터 숨기기';
$lang["doi_fetched_xml_from_MDS"]='현재 XML 메타데이터를 DataCite의 메타데이터 저장소에서 성공적으로 가져올 수 있었습니다.';
$lang["plugin-doi-title"]='디지털 객체 식별자';
$lang["plugin-doi-desc"]='불변 객체에 대해 DOI를 생성한 후 DataCite에 등록하여 지속적인 인용을 가능하게 합니다.';