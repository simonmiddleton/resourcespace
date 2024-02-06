<?php

function consentmanager_get_consents($ref)
    {
    return ps_query("select consent.ref,consent.name,consent.expires,consent.consent_usage from consent join resource_consent on consent.ref=resource_consent.consent where resource_consent.resource= ? order by ref", ['i', $ref]);
    }
