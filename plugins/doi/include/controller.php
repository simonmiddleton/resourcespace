<?php
	/**
	 * @file controller.php
	 *
	 * Permission and accessability checking.
	 */

	/**
	 * Checks a given doi against a given prefix.
	 *
	 * @param $doi String The doi.
	 * @param $prefix String The prefix to check against.
	 *
	 * @return bool TRUE, if doi is prefixed by the prefix, otherwise FALSE.
	 */
	function doi_has_prefix($doi, $prefix) {
		return substr($doi, 0, strlen($prefix)) === $prefix;
	}

	/**
	 * Checks a given doi against the prefix and the test-prefix, saved in the configuration.
	 *
	 * @param $doi String The doi.
	 *
	 * @return bool TRUE, if doi is prefixed by one of these prefixes.
	 */
	function doi_is_ours($doi) {
		global $doi_test_prefix, $doi_prefix;

		return doi_has_prefix($doi, $doi_test_prefix) || doi_has_prefix($doi, $doi_prefix);
	}

	/**
	 * Checks the needed permissions saved in config against the actual user permission using the checkperm()-function.
	 * Return true if the user has all permissions needed for working with dois, as configured in this plugins config.
	 *
	 * @return boolean TRUE, if permissions are fulfilled, otherwise FALSE.
	 */
	function doi_check_user_access() {

		global $doi_perms_needed;

		# External access support (authenticate only if no key provided, or if invalid access key provided)
		$k=getvalescaped("k","");if (($k=="") || (!check_access_key(getvalescaped("ref",""),$k))) {include_once __DIR__ . "/../../../include/authenticate.php";}

		# Check preconfigured permissions
		for ($n = 0; $n < count($doi_perms_needed); ++$n) {
			if(!checkperm($doi_perms_needed[$n])) return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checks if a given resource has the archive state desired for DOI registration, saved in config.
	 *
	 * @param  array $resource The resource array.
	 *
	 * @return boolean         TRUE, if so, otherwise FALSE.
	 */
	function doi_check_resource_state(&$resource) {

		global $doi_archive_state, $anonymous_login;

		# return $resource['archive'] == $doi_archive_state && isset($anonymous_login) && $resource['access'] == 0;#  FIXME temporary
		return $resource['archive'] == $doi_archive_state && $resource['access'] == 0;
	}

	function doi_resource_has_title(&$resource, &$fields) {

		$hasTitle = doi_has_content($resource['title']);
		if(!$hasTitle) {
			global $doi_pref_title_fields;
			foreach ($fields as $field) {
				if(in_array($field['ref'], $doi_pref_title_fields)) {
					$hasTitle = $hasTitle || doi_has_content($field['value']);
				}
			}
		}

		return $hasTitle;
	}
