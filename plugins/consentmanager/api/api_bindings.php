<?php

function api_consentmanager_get_consents($ref)
    {
    return consentmanager_get_consents($ref);
    }

function api_consentmanager_delete_consent($ref)
    {
    return consentmanager_delete_consent($ref);
    }

function api_consentmanager_batch_link_unlink($consent,$collection,$unlink)
    {
    return consentmanager_batch_link_unlink($consent,$collection,$unlink);
    }

function api_consentmanager_link_consent($consent,$resource)
    {
    return consentmanager_link_consent($consent,$resource);
    }

function api_consentmanager_unlink_consent($consent,$resource)
    {
    return consentmanager_unlink_consent($consent,$resource);
    }

function api_consentmanager_create_consent($name, $email, $telephone, $consent_usage, $notes="", $expires=null)
    {
    return consentmanager_create_consent($name, $email, $telephone, $consent_usage, $notes, $expires);
    }

function api_consentmanager_get_consent($consent)
    {
    return consentmanager_get_consent($consent);
    }

function api_consentmanager_update_consent($consent, $name, $email, $telephone, $consent_usage, $notes="", $expires=null)
    {
    return consentmanager_update_consent($consent, $name, $email, $telephone, $consent_usage, $notes, $expires);
    }

function api_consentmanager_get_all_consents($findtext="")
    {
    return consentmanager_get_all_consents($findtext);
    }

function api_consentmanager_get_all_consents_by_collection($collection)
    {
    return consentmanager_get_all_consents_by_collection($collection);
    }
