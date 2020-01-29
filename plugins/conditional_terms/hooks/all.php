<?php

function HookConditional_tersmAllInitialise()
    {
    global $conditional_terms_fieldvars;
    config_register_core_fieldvars("Conditional terms plugin",$conditional_terms_fieldvars);
    }