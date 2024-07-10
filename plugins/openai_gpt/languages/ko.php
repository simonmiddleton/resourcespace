<?php


$lang["openai_gpt_title"]='OpenAI 통합';
$lang["openai_gpt_intro"]='기존 데이터를 사용자 정의 가능한 프롬프트로 OpenAI API에 전달하여 생성된 메타데이터를 추가합니다. 자세한 정보는 <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a>를 참조하십시오.';
$lang["property-openai_gpt_prompt"]='GPT 프롬프트';
$lang["property-openai_gpt_input_field"]='GPT 입력';
$lang["openai_gpt_api_key"]='OpenAI API 키. API 키를 <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>에서 받으세요';
$lang["openai_gpt_model"]='사용할 API 모델의 이름 (예: \'text-davinci-003\')';
$lang["openai_gpt_temperature"]='0과 1 사이의 샘플링 온도 (값이 높을수록 모델이 더 많은 위험을 감수함)';
$lang["openai_gpt_max_tokens"]='최대 토큰';
$lang["openai_gpt_advanced"]='경고 - 이 섹션은 테스트 목적으로만 사용되며 실제 시스템에서는 변경하지 마십시오. 여기서 플러그인 옵션을 변경하면 구성된 모든 메타데이터 필드의 동작에 영향을 미칩니다. 신중하게 변경하십시오!';
$lang["openai_gpt_system_message"]='초기 시스템 메시지 텍스트. 자리 표시자 %%IN_TYPE%% 및 %%OUT_TYPE%%는 소스/대상 필드 유형에 따라 \'text\' 또는 \'json\'으로 대체됩니다';