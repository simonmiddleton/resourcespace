<?php
// OpenAI key from https://openai.com/api/
$openai_gpt_api_key = "";
$openai_gpt_model = "gpt-3.5-turbo";
$openai_gpt_system_message = "You are a formal API required to extract or convert information from the data provided. For the provided %%IN_TYPE%% input, you will respond with %%OUT_TYPE%%";
$openai_gpt_message_text = "text";
$openai_gpt_message_input_JSON = "JSON";
$openai_gpt_message_output_json = "a JSON formatted list";

$openai_gpt_temperature = 0;

// The following can't be changed from the plugin setup page
$openai_gpt_endpoint = "https://api.openai.com/v1/chat/completions";
$openai_gpt_max_tokens = 1000;
$openai_gpt_max_data_length = 10000;

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

