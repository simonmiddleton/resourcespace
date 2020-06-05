<?php
	/**
	 * @file en.php
	 *
	 * adds english strings to $lang array.
	 */

	$lang['status4'] = "Immutable";
	$lang['doi_info_wikipedia'] = "https://en.wikipedia.org/wiki/Digital_Object_Identifier";
	$lang['doi_info_link'] = 'on <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a>.';
	$lang['doi_info_metadata_schema'] = 'on the DOI registration at DataCite.org are stated in the <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Datacite Metadata Schema Documentation</a>.';
	$lang['doi_info_mds_api'] = 'on the DOI-API used by this plugin are stated in the <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API Documentation</a>.';

	$lang['doi_plugin_heading'] = 'This Plugin creates <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> for immutable objects and collections before registering them at <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
	$lang['doi_further_information'] = 'Further information';

	$lang['doi_setup_doi_prefix'] = '<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">Prefix</a> for <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> generation';
	$lang['doi_info_prefix'] = 'on <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">doi prefixes</a>.';

	$lang['doi_setup_use_testmode'] = 'Use <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmode</a>';
	$lang['doi_info_testmode'] = 'on the <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmode</a>.';

	$lang['doi_setup_use_testprefix'] = 'Use <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefix (10.5072)</a> instead';
	$lang['doi_info_testprefix'] = 'on the <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefix</a>.';

	$lang['doi_setup_publisher'] = '<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Publisher</a>';
	$lang['doi_info_publisher'] = 'on the <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">publisher</a> field.';

	$lang['doi_resource_conditions_title'] = 'A resource needs to fulfill the following preconditions to qualify for DOI registration:';
	$lang['doi_resource_conditions'] = <<<HTML
<li>Your Project needs to be public, that is, having a public area.</li>
<li>The resource must be publicly accessable, that is, having its access set to <strong>open</strong>.</li>
<li>The resource must have a <strong>title</strong>.</li>
<li>It must be marked {status}, that is, having its state set to <strong>{status}</strong>.</li>
<li>Then, only an <strong>admin</strong> is allowed to initiate the registration process.</li>
HTML;
//
//	$lang['doi_setup_perms_needed'] = 'necessary user permissions for registration';
//	$lang['doi_setup_field_shortname'] = 'plugin\'s shortname';

	$lang['doi_setup_general_config'] = 'General Configuration';
	$lang['doi_setup_pref_fields_header'] = 'Preferred search fields for metadata construction';

	$lang['doi_setup_username'] = 'DataCite username';
	$lang['doi_setup_password'] = 'DataCite password';

//	$lang['doi_pref_creator_fields'] = 'Look for \'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a>\' in:<br>(In case no value could be found, the uploader is used as the creator.)';
//	$lang['doi_pref_title_fields'] = 'Look for \'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Title</a>\' in:<br>(In case no value could be found, an error message will pop up, and the resource is not going to be registered.)';
	$lang['doi_pref_publicationYear_fields'] = 'Look for <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a> in:<br>(In case no value could be found, the year of registration will be used.)';

	$lang['doi_pref_creator_fields'] = 'Look for <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a> in:';
	$lang['doi_pref_title_fields'] = 'Look for <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Title</a> in:';
//	$lang['doi_pref_publicationYear_fields'] = 'Look for \'<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a>\' in:';


	# see: https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38
	$lang['doi_datacite_unknown_info_codes'] = [
		'(:unac)' => 'temporarily inaccessible',
		'(:unal)' => 'unallowed, suppressed intentionally',
		'(:unap)' => 'not applicable, makes no sense',
		'(:unas)' => 'value unassigned (e.g., Untitled)',
		'(:unav)' => 'value unavailable, possibly unknown',
		'(:unkn)' => 'known to be unknown (e.g., Anonymous, Inconnue)',
		'(:none)' => 'never had a value, never will',
		'(:null)' => 'explicitly and meaningfully empty',
		'(:tba)'  => 'to be assigned or announced later',
//		'(:etal)' => '"too numerous to list (et alia)"'
	];

	$lang['doi_setup_default'] = 'If no value could be found, use <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standard code</a>:';

	$lang['doi_setup_test_plugin'] = 'Test plugin..';
	$lang['doi_setup_test_succeeded'] = 'Test succeeded!';
	$lang['doi_setup_test_failed'] = 'Test failed!';

	$lang['doi_no_yes'] = ['no', 'yes'];

	$lang['doi_alert_text'] = 'Attention! Once the DOI is sent ot DataCite, the registration cannot be undone.';

	$lang['doi_title_compulsory'] = "Please set a title before continuing the DOI registration.";

	$lang['doi_register'] = 'Register';
	$lang['doi_cancel'] = 'Cancel';
	$lang['doi_sure'] = 'Attention! Once the DOI is sent off to DataCite, the registration cannot be undone. Information already registered in DataCite\\\'s Metadata Store will possibly be overwritten.';
	$lang['doi_already_set'] = 'already set';
	$lang['doi_not_yet_set'] = 'not yet set';
	$lang['doi_already_registered'] = 'already registered';
	$lang['doi_not_yet_registered'] = 'not yet registered';
	$lang['doi_successfully_registered'] = 'was successfully registered';
	$lang['doi_successfully_registered_pl'] = ' resource(s) was/were successfully registered.';
	$lang['doi_not_successfully_registered'] = 'could not be registered correctly';
	$lang['doi_not_successfully_registered_pl'] = 'could not be registered correctly.';

	$lang['doi_reload'] = "Reload";

	$lang['doi_successfully_set'] = 'has been set.';
	$lang['doi_not_successfully_set'] = 'has not been set.';

	$lang['doi_sum_of'] = 'of';
	$lang['doi_sum_already_reg'] = 'resource(s) already has/have a DOI.';
	$lang['doi_sum_not_yet_archived'] = 'resource(s) is/are not marked';
	$lang['doi_sum_not_yet_archived_2'] = 'yet or its/their access is not set to open.';
	$lang['doi_sum_ready_for_reg'] = 'resource(s) is/are ready for registration.';
	$lang['doi_sum_no_title'] = 'resource(s) still need a title. Using ';
	$lang['doi_sum_no_title_2'] = ' as a title instead then.';

	$lang['doi_register_all'] = 'Register DOIs for all ressources in this collection';
	$lang['doi_sure_register_resource'] = 'Proceed registering x resource(s)?';

	$lang['doi_show_meta'] = 'Show DOI metadata';
	$lang['doi_hide_meta'] = 'Hide DOI metadata';

	$lang['doi_fetched_xml_from_MDS'] = 'Current XMl metadata could be successfully fetched from DataCite\\\'s metadata store.';
?>
