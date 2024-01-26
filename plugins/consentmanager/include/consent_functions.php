<?php

function consentmanager_get_consents($ref)
    {
    return ps_query("select consent.* from consent join resource_consent on consent.ref=resource_consent.consent where resource_consent.resource= ? order by ref", ['i', $ref]);
    }
