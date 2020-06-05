<?php
	/**
	 * @file setup.php
	 *
	 * Plugin setup paged based on the Resource Space template. Lets admins config plugins configs and defaults.
	 * Additionally contains a TEST-Button that runs the registration functions testwise and reports the results.
	 */

	#
	# Setup page template
	#
	# You can 'crib' from this code to easily create a setup.php file for a new plugin.
	#

	// Do the include and authorization checking ritual -- don't change this section.
	include '../../../include/db.php';
		include '../../../include/authenticate.php';
	if (!checkperm('a')) {
		exit ($lang['error-permissiondenied']);
	}
	include_once '../../../include/language_functions.php';
	include '../../../plugins/doi/include/model.php';

	// Specify the name of this plugin, the heading to display for the page and the
	// optional introductory text. Set $page_intro to "" for no intro text
	// Change to match your plugin.

	$plugin_name = 'doi';                        // Usually a string literal
	$page_heading = $lang['doi_plugin_heading']; // Usually a $lang[] string

	$page_intro = <<<HTML
	<div class="Question">
		<form id="doi_test" name="doi_test" method="post">
			<input type="hidden" name="doi_state" value="test"/>
			<input type="submit" style="resize: none; height: 32px;"
			       value="{$lang['doi_setup_test_plugin']}">
		</form>
	</div>
	<div class="clearerleft"></div>
HTML;

	# buffer
	ob_start();
?>
	<div class="Question">
		<br>
		<h2><?php echo $lang['doi_resource_conditions_title']; ?></h2>
	</div>
	<ul><?php echo str_replace('{status}', strtolower($lang['status' . $doi_archive_state]), $lang['doi_resource_conditions']); ?></ul>

	<div class="Question">
		<br>
		<h2><?php echo $lang['doi_further_information']; ?>: </h2>
	</div>
	<ul>
		<li><?php echo $lang['doi_info_link']; ?></li>
		<li><?php echo $lang['doi_info_metadata_schema']; ?></li>
		<li><?php echo $lang['doi_info_mds_api']; ?></li>
		<!--		<li>--><?php //echo $lang['doi_info_prefix']; ?><!--</li>-->
		<!--		<li>--><?php //echo $lang['doi_info_testmode']; ?><!--</li>-->
		<!--		<li>--><?php //echo $lang['doi_info_testprefix']; ?><!--</li>-->
		<!--		<li>--><?php //echo $lang['doi_info_publisher']; ?><!--</li>-->
	</ul>
<?php

	$page_def[] = config_add_html(ob_get_contents());
	ob_end_clean();

	$page_def[] = config_add_text_input('doi_prefix', $lang['doi_setup_doi_prefix']);
	$page_def[] = config_add_boolean_select('doi_use_testprefix', $lang['doi_setup_use_testprefix'], $lang['doi_no_yes']);
	$page_def[] = config_add_text_input('doi_publisher', $lang['doi_setup_publisher']);

	$rtfs = get_resource_type_fields([0], 'title');

	# get right translation for titles
	foreach ($rtfs as &$rtf) {
		foreach ($rtf as $key => &$value) {
			if ($key == 'title') {
				$value = i18n_get_translated($value);
			}
		}
	}

	# then sort again lexicographically
	usort($rtfs, function (array $a, array $b) {
		return strcasecmp($a['title'], $b['title']);
	});


	# default behavier config section for mandatory fields
	$page_def[] = config_add_section_header($lang['doi_setup_pref_fields_header']);

	$pref_field = 'doi_pref_creator_fields';
	$page_def[] = config_add_db_multi_select($pref_field, $lang[$pref_field], $rtfs, 'ref', 'title', '', '', 420);
	$page_def[] = config_add_single_select($pref_field . '_default', $lang['doi_setup_default'], $lang['doi_datacite_unknown_info_codes'], true);

	$pref_field = 'doi_pref_title_fields';
	$page_def[] = config_add_db_multi_select($pref_field, $lang[$pref_field], $rtfs, 'ref', 'title', '', '', 420);
	$page_def[] = config_add_single_select($pref_field . '_default', $lang['doi_setup_default'], $lang['doi_datacite_unknown_info_codes'], true);

	$pref_field = 'doi_pref_publicationYear_fields';
	$page_def[] = config_add_db_multi_select($pref_field, $lang[$pref_field], $rtfs, 'ref', 'title', '', '', 420);
	# https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10
	# "... If that date cannot be determined, use the date of registration..."
	# $page_def[] = config_add_single_select($pref_field . '_default', $lang['doi_setup_default'], $lang['doi_datacite_unknown_info_codes'], true);

//	global $doi_pref_creator_fields, $doi_pref_title_fields, $doi_pref_publicationYear_fields, $doi_pref_creator_fields, $doi_pref_title_fields, $doi_pref_publicationYear_fields;
//	doi_debug($doi_pref_creator_fields, 'w');
//	doi_debug($doi_pref_creator_fields, 'a');
//	doi_debug($doi_pref_title_fields, 'a');
//	doi_debug($doi_pref_title_fields, 'a');
//	doi_debug($doi_pref_publicationYear_fields, 'a');
//	doi_debug($doi_pref_publicationYear_fields, 'a');
//	doi_debug_globals();

	$page_def[] = config_add_section_header('DataCite Credentials');
	$page_def[] = config_add_text_input('doi_username', $lang['doi_setup_username']);
	$page_def[] = config_add_text_input('doi_password', $lang['doi_setup_password'], TRUE);

	// Do the page generation ritual -- don't change this section.
	$upload_status = config_gen_setup_post($page_def, $plugin_name);
	include '../../../include/header.php';
	config_gen_setup_html($page_def, $plugin_name, $upload_status, $page_heading, $page_intro);

	# "onclick" of test button
	$doi_state = getvalescaped('doi_state', '');
	if ($doi_state == 'test') {

		# perform Test

		global $doi_test_prefix;

		# generate simple test data
		$ref = '42';
		$doi = $doi_test_prefix . $ref;
		$url = doi_make_resource_url($ref);

		$xml = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<resource xmlns='http://datacite.org/schema/kernel-3' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://datacite.org/schema/kernel-3 http://schema.datacite.org/meta/kernel-3/metadata.xsd'>
	<identifier identifierType='DOI'>$doi</identifier>
	<creators>
		<creator>
			<creatorName>Test Creator</creatorName>
		</creator>
	</creators>
	<titles>
		<title>Test Title</title>
	</titles>
	<publisher>Test Publisher</publisher>
	<publicationYear>2015</publicationYear>
</resource>
XML;

		# format xml using DOMDocument class
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = FALSE;
		$dom->formatOutput = TRUE;
		$dom->loadXML($xml);
		$xml = $dom->saveXML();

		$success = [];
		$meta = ['doi' => $doi, 'url' => $url, 'xml' => $xml];

		global $doi_current_ref;
		$doi_current_ref = -1; // TEST-case

		# check if there is test xml metadata in DataCite's MDS already
		$fetched_xml = (bool)doi_get_xml($doi, FALSE, FALSE);

		# use testmode for xml upload accordingly
		$success['xml'] = doi_post_xml($xml, TRUE, $fetched_xml);
		$success['url'] = doi_post_url($doi, $url, TRUE, TRUE);
		$success['doi'] = $success['url'] && $success['xml'];

		global $lang;

		$summary = '';
		if ($success['doi']) {
			$summary .= $lang['doi_setup_test_succeeded'];
		}
		else {
			$summary .= $lang['doi_setup_test_failed'];
		}

		foreach ($success as $key => $value) {
			$summary .= '\n' . "[$key] ";
			if ($value) {
				$summary .= $lang['doi_successfully_registered'];
			}
			else {
				$summary .= $lang['doi_not_successfully_registered'];
			}
			if ($key != 'xml')
				$summary .= ': ' . htmlspecialchars($meta[$key]);
			else
				$summary .= '.';
		}

		global $doi_err_cache;
		if (isset($doi_err_cache) && !is_null($doi_err_cache) && !empty($doi_err_cache)) {
			foreach ($doi_err_cache as $err) {
				$summary .= '\n' . $err;
			}
		}

		doi_clear_cache($doi_err_cache);

		?>
		<script type="text/javascript">
			alert('<?php echo htmlspecialchars($summary);?>');
		</script>
		<?php
	}

	# set specific entries readonly
	foreach (['doi_prefix'] as $value) {
		?>
		<script type="text/javascript">
			document.getElementById('<?php echo $value; ?>').readOnly = true;
		</script>
		<?php
	}

	include '../../../include/footer.php';
