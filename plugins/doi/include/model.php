<?php
    /**
     * @file model.php
     *
     * Business logic and database manipulation.
     */

    include_once __DIR__ . "/../../../include/log_functions.php";
//  

    # Consider "https://mds.datacite.org/static/apidoc" for further information on the underlying API.

    # Error handling & logging #################################################################################################################
    #
    # Logging GET-requests can be toggled in config/setup.
    # Generally all non-successful requests and requests with a HTTP method that is not 'GET' are logged.

    $doi_err_cache = [];

    $doi_current_ref = -1;

    function doi_clear_cache(&$cache) {
        $cache = [];
    }

    /**
     * @param $ref string resource reference
     * @param $doi string the new value for the resource's doi
     */
    function doi_update_resource_doi($ref, $doi) {

        global $doi_field_shortname;
        if (update_field($ref, $doi_field_shortname, $doi) === false) {
            // field does probably not exist: create and try again
            ps_query("INSERT INTO `resource_type_field` (`name`, `title`, `type`, `order_by`, `keywords_index`, `partial_index`, `resource_type`, `resource_column`, `display_field`, `use_for_similar`, `iptc_equiv`,
			`display_template`, `tab_name`, `required`, `smart_theme_name`, `exiftool_field`, `advanced_search`, `simple_search`, `help_text`, `display_as_dropdown`, `external_user_access`, `autocomplete_macro`, `hide_when_uploading`, `hide_when_restricted`,
			`value_filter`, exiftool_filter, `omit_when_copying`, `tooltip_text`, `regexp_filter`, `display_condition`)
			VALUES ('doi', 'DOI', '0', '130', '0', '0', '0', NULL, '1', '1', NULL, NULL, NULL, '0', NULL, 'XMP-prism:doi', '1', '0',
			'~de:Bezeichner eintragen, wie z.B. 10.1000/182 (für DOI-Handbuch). Ein Digital Object Identifier ist eine eindeutige und dauerhafte Bezeichnung einer digitalen Ressource (z. B. Forschungsdaten). Es ist eine Referenz, die durch doi.org aufgelöst werden kann.~en:Insert identifier, e.g., 10.1000/182 (for DOI handbook). A digital object identifier (DOI) is a serial code used to uniquely identify objects. It is resolveable by doi.org.', '0', '1', NULL, '0', '0', NULL, NULL, '0', NULL, NULL, NULL);");
            log_activity('DOI field created',LOG_CODE_CREATED,null,'resource_type_field','title','DOI',null,'');
            update_field($ref, $doi_field_shortname, $doi);
        }
    }

    # Metadata Construction functions ######################################################################################################

    /**
     *
     *
     * @param $fields
     *
     * @return bool
     */
    function doi_extract_doi(&$fields) {

        global $doi_field_shortname;

        return doi_extract_field_value($fields, $doi_field_shortname);
    }

    /**
     * @param $fields
     * @param $name
     *
     * @return bool
     */
    function doi_extract_field_value(&$fields, $name) {

        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                $value = $field['value'];
                if (doi_has_content($value)) {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Checks if a given String value is empty or nearly empty (see function body).
     *
     * @param  string $value The value to check.
     *
     * @return boolean          TRUE, if not (nearly) empty, otherwise FALSE.
     */
    function doi_has_content($value) {
        return isset($value) && $value && !empty($value) && ($value !== '') && ($value !== ',');
    }

    /**
     * Deducts a human readable version of a license given as URI.
     *
     * @param $rights_URI string the license url
     *
     * @return null|string The human readable version of the license if possible, otherwise null.
     */
    function doi_deduct_rights($rights_URI) {
        if (filter_var($rights_URI, FILTER_VALIDATE_URL, 0) !== false) {
            $uri_parts = explode('/', $rights_URI);
            if (
                count($uri_parts) > 3
                && stripos($uri_parts[2], 'creativecommons.org') !== false # CREATIVE COMMONS
                ) {

                    # http://creativecommons.org/licenses/by/3.0/ -> Creative Commons Attribution (CC BY 3.0)
                    # license parts:
                    # 2 => domain
                    # 3 => 'licenses'
                    # 4 => attributes
                    # 5 => version
                    # 6 => jurisdiction

                    $result = 'Creative Commons ';
                    if (count($uri_parts) > 4) {
                        $attributes = explode('-', $uri_parts[4]);

                        if (count($uri_parts) > 5) {
                            $version = $uri_parts[5];
                            foreach ($attributes as $a) {
                                switch ($a) {
                                    case 'by':
                                        $result .= 'Attribution ';
                                        break;
                                    case 'nc':
                                        $result .= 'Non-Commercial ';
                                        break;
                                    case 'nd':
                                        $result .= 'No Derivatives ';
                                        break;
                                    case 'sa':
                                        $result .= 'Share-Alike ';
                                        break;
                                }
                            }
                            $result .= '(CC ';
                            foreach ($attributes as $a) $result .= strtoupper($a) . ' ';
                            $result = trim($result) . " $version)";

                            return $result;
                        }
                    }
                # .. inspect other rights URIs
            }
        }

        return null;
    }

    /**
     * Constructs and metadata form a given resource and fields array.
     * A DOI, a URL, and XML are constructed. Xml corresponds to the Datacite-Metadata-Schema.
     *
     * @param array $resource The resource array.
     * @param array $fields   The resource's fields array.
     *
     * @return array An associative array holding doi, url, xml, and a title if found.
     */
    function doi_construct_metadata(array &$resource, array &$fields) {

        # configs
        global $doi_field_shortname, $doi_prefix, $doi_use_testprefix, $doi_test_prefix;

        # defaults
        global $doi_publisher;

        $ref = $resource['ref'];

        # declarations
        $identifier = ($doi_use_testprefix ? $doi_test_prefix : $doi_prefix) . "$ref"; # init, mandatory
        $creators = []; # mandatory
        $titles = []; # mandatory
        $publisher = htmlspecialchars($doi_publisher); # mandatory
        $publicationYear = ''; # mandatory
        $date_created = '';
        $subjects = [];
        $contributors = [];
        $contributorType = [];
        $dates = [];
        $dateType = [];
        $rights = '';
        $rightsURI = '';
        $use_rightsURI = false;
        $descriptions = [];
        $descriptionType = [];

        # BEGIN PARSE METADATA

        # collect data from $resource
        switch ($resource['resource_type']) {

            case 1: // Photo
                $resourceTypeGeneral = 'Image';
                $resourceType = 'Photo';
                break;

            case 2:
            default: // Document
                $resourceTypeGeneral = 'Text';
                $resourceType = 'Document';
                break;

            case 3: // Video
                $resourceTypeGeneral = 'Audiovisual';
                $resourceType = 'Video';
                break;

            case 4: // Audio
                $resourceTypeGeneral = 'Sound';
                $resourceType = 'Audio';
                break;
        }

        foreach ($resource as $resource_field => $value) {
            if (doi_has_content($value)) {
                switch ($resource_field) {
                    case 'file_modified':
                        break;

                    case 'title':
                        $titles[] = htmlspecialchars($value);
                        break;

                    case 'created_by':
                        break;

                    case 'creation_date':
                        $date_created = date('c', strtotime($value));
                        $dates['created'] = $date_created;
                        $dateType['created'] = 'Created';
                        break;
                }
            }
        }

        # collect data from $fields
        foreach ($fields as $field) {
            $value = $field['value'];

            if (doi_has_content($value)) {
                $value = htmlspecialchars($value);

                doi_debug($field);
                $rtf = $field['ref'];
                global $doi_pref_creator_fields, $doi_pref_title_fields, $doi_pref_publicationYear_fields;

                # first: search for compulsory DataCite-fields in preferred RS-fields
                if (in_array($rtf, $doi_pref_creator_fields) && !in_array($value, $creators)) {
                    $creators[] = $value;
                }
                elseif (in_array($rtf, $doi_pref_title_fields) && !in_array($value, $titles)) {
                    $titles[] = $value;
                }
                elseif (in_array($rtf, $doi_pref_publicationYear_fields) && !doi_has_content($publicationYear)) {
                    $publicationYear = date('Y', strtotime($date_created));
                }

                switch ($field['name']) {

                    case 'caption':
                    case 'title':
                    case 'credit':
                    # additional Metadata by name
                    case $doi_field_shortname:
                        break;

                    case 'extract': # TODO falls in englisch!!
                        # case 'text':
                        if (!in_array($value, $descriptions)) {
                            $descriptions[] = $value;
                            $descriptionType[] = 'Abstract';
                        }
                        break;

                    case 'kategorie':
                        break;

                    case 'keywords':
                    case 'schlagwort':
                    case 'schlagwort2kurzname':
                        $keywords = explode(',', preg_replace('%(, )|;|(; )%', ',', $value));
                        foreach ($keywords as $keyword) {
                            if (doi_has_content($keyword))
                                $subjects[] = trim($keyword);
                        }
                        break;

                    case 'licenseurl':
                        if (
                            filter_var($value, FILTER_VALIDATE_URL) !== false
                            && !$rightsURI
                            ) {
                            $rightsURI = $value;
                        }
                        break;

                    case 'person':
                        break;

                    case 'usageterms':
                        if (!$rights) {
                            $rights = $value;
                        }
                        break;
                }
            }
        }
        # END PARSE METADATA

        # METADATA POST-PROCESSING // DEFAULT BEHAVIOR

        global $doi_pref_creator_fields_default, $doi_pref_title_fields_default, $doi_pref_publicationYear_fields_default;

        if (empty($creators)) {
            # use uploader as creator
            $creators[] = $doi_pref_creator_fields_default;
        }

        # https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10
        # "... If that date cannot be determined, use the date of registration..."
        if (!doi_has_content($publicationYear)) {
            $publicationYear = date('Y'); // current year
        }

        if (empty($titles)) {
            $titles[] = $doi_pref_title_fields_default;
        }

        if ($rightsURI) {
            # deduct rights from rightsURI, but only if it's valid
            $deducted = doi_deduct_rights($rightsURI);
            if ($deducted) {
                $rights = $deducted;
                $use_rightsURI = true;
            }
        }

        # END METADATA POST-PROCESSING

        # BEGIN XML CONSTRUCTION

        $xml_template = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<resource xmlns='http://datacite.org/schema/kernel-3' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://datacite.org/schema/kernel-3 http://schema.datacite.org/meta/kernel-3/metadata.xsd'>
</resource>
XML;

        # resource
        $sxe_resource = new SimpleXMLElement($xml_template);

        # identifier
        $sxe_identifier = $sxe_resource->addChild('identifier', $identifier);
        $sxe_identifier->addAttribute('identifierType', 'DOI');

        # creators
        $sxe_creators = $sxe_resource->addChild('creators');
        foreach ($creators as $creator) {
            $sxe_creator = $sxe_creators->addChild('creator');
            /*$sxe_creatorName = */
            $sxe_creator->addChild('creatorName', $creator);
        }

        # titles
        $sxe_titles = $sxe_resource->addChild('titles');
        foreach ($titles as $title) {
            /*$sxe_title = */
            $sxe_titles->addChild('title', $title);
        }

        # publisher
        /*$sxe_publisher =*/
        $sxe_resource->addChild('publisher', $publisher);

        # publicationYear
        if (!doi_has_content($publicationYear)) {
            # if publicationYear could not be derived from RS-Metadata then use date_created
            $publicationYear = date('Y', strtotime($date_created));
        }

        /*$sxe_publicationYear =*/
        $sxe_resource->addChild('publicationYear', $publicationYear);

        # subjects
        if (!empty($subjects)) {
            $sxe_subjects = $sxe_resource->addChild('subjects');
            foreach ($subjects as $subject) {
                /*$sxe_subject =*/
                $sxe_subjects->addChild('subject', $subject);
            }
        }

        # contributors
        if (!empty($contributors)) {
            $sxe_contributors = $sxe_resource->addChild('contributors');
            foreach ($contributors as $key => $contributor) {
                $sxe_contributor = $sxe_contributors->addChild('contributor');
                $sxe_contributor->addAttribute('contributorType', $contributorType[$key]);
                $sxe_contributor->addChild('contributorName', $contributor);
            }
        }

        # dates
        if (!empty($dates)) {
            $sxe_dates = $sxe_resource->addChild('dates');
            foreach ($dates as $date_key => $date) {
                $sxe_date = $sxe_dates->addChild('date', $date);
                $sxe_date->addAttribute('dateType', $dateType[$date_key]);
            }
        }

        # resourceType
        $sxe_resourceType = $sxe_resource->addChild('resourceType', $resourceType);
        $sxe_resourceType->addAttribute('resourceTypeGeneral', $resourceTypeGeneral);

        # rightsList
        if ($rights) {
            $sxe_rightsList = $sxe_resource->addChild('rightsList');
            $sxe_rights = $sxe_rightsList->addChild('rights', $rights);
            if ($use_rightsURI) {
                $sxe_rights->addAttribute('rightsURI', $rightsURI);
            }
        }

        # descriptions
        if (!empty($descriptions)) {
            $sxe_descriptions = $sxe_resource->addChild('descriptions');
            foreach ($descriptions as $key => $description) {

                # decode description again,
                $description = htmlspecialchars_decode($description);
                $description = preg_replace('%' . chr(12) . '%', '', $description); # remove form feed (ff character)
                $description = htmlspecialchars($description);
                $description = preg_replace('%(\r?\n|\r\n?|)%', '<br />', $description); # replace any line breaks with html entity
                #               $description = htmlspecialchars($description);
                $sxe_description = $sxe_descriptions->addChild('description', $description);
                $sxe_description->addAttribute('descriptionType', $descriptionType[$key]);
            }
        }

        # use DOMDocument class for formatted xml output (line breaks, tabs..)
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($sxe_resource->asXML());
        # $dom->save('/tmp/doi_test_xml.xml'); # testing..
        $xml = $dom->saveXML();

        return [
            'doi'   => $identifier,
            'url'   => doi_make_resource_url($ref),
            'xml'   => $xml,
            'title' => $titles[0]
        ];
    }

    /**
     * Returns the URL under which this the resource identified by the id $ref can be found.
     *
     * @param string $ref The resource identifier $ref.
     *
     * @return string The URL.
     */
    function doi_make_resource_url($ref) {
        global $baseurl;

        return str_replace('rs-test', 'rs', "$baseurl/pages/view.php?ref=$ref");
    }

    # Datacite Metadata-Store connection functions ###########################################################################################
    #
    # Consider "https://mds.datacite.org/static/apidoc" for further information on the underlying API.

    # "DOI API" ##############################################################################################################################

    /**
     * Returns an URL associated with a given DOI.
     *
     * Implements "DOI API - GET"
     *
     * @param string $doi The given DOI.
     *
     * @return string The currently associated URL, NULL if an error occurred.
     */
    function doi_get_url($doi, $enable_log = true, $use_testmode = false) {
        global $doi_username, $doi_password, $doi_log_code;

        $op = "DOI: get URL from DataCite";

        # username must not contain colon, because it divides username and password in HTTP Basic authentication!
        if (strpos($doi_username, ':') !== false) {
            doi_log_uname_error();

            return null;
        }

        $host = "https://mds.datacite.org/doi/$doi" . ($use_testmode ? '?testMode=true' : '');

        # init
        $ch = curl_init();

        # opts
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, 1); # GET
        curl_setopt($ch, CURLOPT_USERPWD, "$doi_username:$doi_password");

        # content
        curl_setopt($ch, CURLOPT_URL, $host);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $msgs = [
            200 => "OK - operation successful",
            204 => "No Content - DOI is known to MDS, but is not minted (or not resolvable e.g. due to handle's latency)",
            401 => "Unauthorized - no login",
            403 => "Login problem or dataset belongs to another party",
            404 => "Not Found - DOI does not exist in our database",
            500 => "Internal Server Error - server internal error, try later and if problem persists please contact Datacite"
        ];

        $err = ($code != 200);

        if ($enable_log) {
            doi_log(doi_get_msg($code, $msgs), $doi_log_code, $op, $code, $err);
        }

        # cleanup
        curl_close($ch);

        # Datacite's MDS returns a status message here in an error case. As we already took care of such message at logging,
        # we want a Url to be returned, or otherwise an empty string.
        if (filter_var($response, FILTER_VALIDATE_URL) !== false) {
            return $response;
        }
        else {
            return null;
        }
    }

    /**
     * Returns a list of all DOIs for the requesting datacentre. There is no guaranteed order.
     *
     * Implements "DOI API GET (list all DOIs)"
     *
     * @return array An array of DOIs as strings, NULL if an error occurred.
     */
    function doi_get_all_dois($enable_log = false, $use_testmode = false) {
        global $doi_username, $doi_password, $doi_log_code;

        $op = "DOI: get all DOIs from DataCite";

        # username must not contain colon, because it divides username and password in HTTP Basic authentication!
        if (strpos($doi_username, ':') !== false) {
            doi_log_uname_error();

            return null;
        }

        $host = "https://mds.datacite.org/doi" . ($use_testmode ? '?testMode=true' : '');

        # init
        $ch = curl_init();

        # opts
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, 1); # GET
        curl_setopt($ch, CURLOPT_USERPWD, "$doi_username:$doi_password");

        # content
        curl_setopt($ch, CURLOPT_URL, $host);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $msgs = [
            200 => "OK - operation successful",
            204 => "No Content - no DOIs founds"
        ];

        if ($enable_log) {
            doi_log(doi_get_msg($code, $msgs), $doi_log_code, $op, $code);
        }

        # cleanup
        curl_close($ch);

        if ($response) {
            return preg_split('%(\r)?\n%', $response);
        }
        else {
            return null;
        }
    }

    /**
     * @param      $doi String
     * @param      $url String
     * @param bool $enable_log
     * @param bool $use_testmode
     *
     * @return bool TRUE, if request succeeded, otherwise false.
     */
    function doi_post_url($doi, $url, $enable_log = true, $use_testmode = false) {
        global $doi_username, $doi_password, $doi_log_code;

        $op = "DOI: URL reg. @ DataCite";

        # username must not contain colon, because it divides username and password in HTTP Basic authentication!
        if (strpos($doi_username, ':') !== false) {
            doi_log_uname_error();

            return false;
        }

        $host = "https://mds.datacite.org/doi" . ($use_testmode ? '?testMode=true' : '');
        $header = [
            "Content-Type: text/plain;",
            "charset=UTF-8"
        ];
        $body = [
            "doi=$doi",
            "url=$url"
        ];

        # init
        $ch = curl_init();

        # opts
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1); # POST
        curl_setopt($ch, CURLOPT_USERPWD, "$doi_username:$doi_password");

        # content
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode("\r\n", $body));

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $err = ($code != 201);

        if ($enable_log) {
            $response .= " ($url)";
            doi_log($response, $doi_log_code, $op, $code, $err);
        }

        # cleanup
        curl_close($ch);

        return !$err;
    }

    /**
     * Returns the most recent version of metadata associated with a given DOI.
     *
     * Implements "Metadata API - GET"
     *
     * @param string $doi The given DOI.
     *
     * @return string The metadata as XML-formatted string.
     */
    function doi_get_xml($doi, $enable_log = false, $use_testmode = false) {
        global $doi_username, $doi_password, $doi_log_code;

        $op = "DOI: get metadata-XML from DataCite";

        # username must not contain colon, because it divides username and password in HTTP Basic authentication!
        if (strpos($doi_username, ':') !== false) {
            doi_log_uname_error();

            return null;
        }

        $host = "https://mds.datacite.org/metadata/$doi" . ($use_testmode ? '?testMode=true' : '');
        $header = [
            "Accept:application/xml"
        ];

        # init
        $ch = curl_init();

        # opts
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, 1); # GET
        curl_setopt($ch, CURLOPT_USERPWD, "$doi_username:$doi_password");

        # content
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $msgs = [
            200 => "OK - operation successful",
            401 => "Unauthorized - no login",
            403 => "Forbidden - login problem or dataset belongs to another party",
            404 => "Not Found - DOI does not exist in our database",
            410 => "Gone - the requested dataset was marked inactive (using DELETE method)",
            500 => "Internal Server Error - server internal error, try later and if problem persists please contact us"
        ];

        $err = ($code != 200);

        if ($enable_log) {
            doi_log(doi_get_msg($code, $msgs), $doi_log_code, $op, $code, $err);
        }

        # cleanup
        curl_close($ch);

        if ($err) {
            return null;
        }

        return str_replace('\r\n', '\n', $response);
    }

    /**
     * Stores a new set of metadata associated with a given DOI in DataCite's Metadatastore.
     *
     * @param $xml String metadata
     *
     * @return bool TRUE if succeeded, otherwise false.
     */
    function doi_post_xml($xml, $enable_log = true, $use_testmode = false) {
        global $doi_username, $doi_password, $doi_log_code;

        $op = "DOI: metadata-XML reg. @ DataCite";

        # username must not contain colon, because it divides username and password in HTTP Basic authentication!
        if (strpos($doi_username, ':') !== false) {
            doi_log_uname_error();

            return false;
        }

        $host = "https://mds.datacite.org/metadata" . ($use_testmode ? '?testMode=true' : '');
        $header = [
            "Content-Type:application/xml",
            "charset=UTF-8"
        ];

        # init
        $ch = curl_init();

        # opts
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1); # POST
        curl_setopt($ch, CURLOPT_USERPWD, "$doi_username:$doi_password");

        # content
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $err = ($code != 201);

        if ($enable_log) {
            doi_log($response, $doi_log_code, $op, $code, $err);
        }

        # cleanup
        curl_close($ch);

        return !$err;
    }

    /**
     * @param        $code        integer Http-Code
     * @param        $msgs        array messages, accessable by code
     * @param string $default_msg message to be used, if array does not contain the code
     *
     * @return string
     */
    function doi_get_msg($code, &$msgs, $default_msg = '') {
        return array_key_exists($code, $msgs) ? $msgs[$code] : $default_msg;
    }

    function doi_log_uname_error() {
        global $doi_current_ref;

        $log_msg = "W: DataCite username must not contain a colon.";

        if ($doi_current_ref !== -1) {
            resource_log($doi_current_ref, LOG_CODE_LOGGED_IN, 0, $log_msg);
        }
        else {
            # test case
            log_activity('TEST: ' . $log_msg, LOG_CODE_LOGGED_IN);
        }
    }

    function doi_log($msg, $LOG_CODE = null, $op = 'DOI: ', $http_code = null, $err = false) {

        if ($http_code === null) {
            $http_code = '';
        }
        else {
            $http_code = ' ' . $http_code . ':';
        }

        global $debug_log, $doi_err_cache;

        $log_msg = trim(($err ? 'E: ' : '') . "[$op]$http_code $msg");

        if ($err) {
            $doi_err_cache[] = $log_msg;
        }

        if ($debug_log) {
            debug($log_msg);
        }

        global $doi_current_ref;

        if ($doi_current_ref !== -1) {
            resource_log($doi_current_ref, $LOG_CODE, 0, $log_msg);
        }
        else {
            # test case
            log_activity('TEST: ' . $log_msg, $LOG_CODE);
        }
    }

    function doi_debug_globals() {
        foreach($GLOBALS as $key => $value) {
            if(is_string($key) && stripos($key, "doi") !== false) {
                doi_debug("$key", 'a', false);
                doi_debug($value, 'a');
                doi_debug("\n", 'a', true);
            }
        }
    }

    function doi_debug($var, $mode = 'w', $print = false) {

        $cache = ob_get_contents();
        $buffer_was_active = ($cache !== false);
        if ($buffer_was_active) {
            ob_end_clean();
        }

        $f = fopen('/tmp/doi_plugin_debug.txt', $mode);
        ob_start();
        if($print) {
            echo "$var";
        } else {
            var_dump($var);
        }
        fwrite($f, ob_get_contents());
        ob_end_clean();

        if ($buffer_was_active) {
            # restore
            ob_start();
            echo $cache;
        }
    }
