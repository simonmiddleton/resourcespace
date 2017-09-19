<?php

# Important - turn on global config option that enables empty collections to be shared, so that collections containing only external resources can also be shared.
$collection_allow_empty_share=true;

$resourceconnect_link_name="View matches in the Affiliate Network"; # i18n
# - you may use the i18n syntax for multi-lingual names, e.g. ~en:Link name~sv:Länknamn
$resourceconnect_title="Search the Affiliate Network"; # i18n
# - you may use the i18n syntax for multi-lingual titles, e.g. ~en:Title~sv:Titel

$resourceconnect_user=1;
$resourceconnect_pagesize=48;
$resourceconnect_pagesize_expanded=32;
$resourceconnect_treat_local_system_as_affiliate=false; # For testing - causes the local system itself to work like an external affiliate

# Which field bindings should be 'honoured' when searching cross systems, in other words, which fields are equivalent across affiliate systems?
# Fields not in this array will be stripped back to plain text searches so will match on any field - this is useful when affiliates use completely different
# metadata fields/options.
# The default field (3) is the country field on out of the box systems.
# Fields in this array must be the same across all affiliate systems.
$resourceconnect_bind_fields=array(3);

# Affiliate list
# - you may use the i18n syntax for multi-lingual names, e.g. ~en:This System~sv:Det här systemet
$resourceconnect_affiliates=array
        (
        array
                (
                "name"=>"This System", # i18n
                "baseurl"=>"http://my.system",
                "accesskey"=>"x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0" # From external system's plugin setup page
                ),
        array
                (
                "name"=>"Remote System A", # i18n
                "baseurl"=>"http://remote.system.a",
                "accesskey"=>"x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0"
                ),
        array
                (
                "name"=>"Remote System B", # i18n
                "baseurl"=>"http://remote.system.b",
                "accesskey"=>"x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0x0"
                )
        );
        
$resourceconnect_fullredir_pages=array("terms","download","view","preview");
