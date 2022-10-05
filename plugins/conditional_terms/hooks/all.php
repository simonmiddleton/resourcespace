<?php

function HookConditional_termsAllInitialise()
    {
    global $conditional_terms_fieldvars;
    config_register_core_fieldvars("Conditional terms plugin",$conditional_terms_fieldvars);
    }


function HookConditional_termsAllExtra_warn_checks()
    {
    global $lang;

    if(!conditional_terms_config_check())
        {
        return [[
            'name' => 'conditional_terms',
            'info' => $lang['conditional_terms_plugin_misconfigured'],
        ]];
        }

    return false;
    }