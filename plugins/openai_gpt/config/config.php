<?php
// Open AI key from https://openai.com/api/
$openai_gpt_api_key = "";

// The following don't need to be changed by the plugin setup page
$openai_gpt_api_endpoint = "https://api.openai.com/v1/completions";
$openai_gpt_api_model = "text-davinci-003";
$openai_gpt_api_max_tokens = 100;
$openai_gpt_api_temperature = 0;

$valid_ai_field_types = [
    FIELD_TYPE_RADIO_BUTTONS,
    FIELD_TYPE_CHECK_BOX_LIST,
    FIELD_TYPE_DROP_DOWN_LIST,
    FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,
    FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
    FIELD_TYPE_TEXT_BOX_MULTI_LINE,
    FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE,
    FIELD_TYPE_WARNING_MESSAGE,
    FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR,
    ];

