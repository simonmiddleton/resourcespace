<?php
	/**
	 * @file collection_edit.php
	 *
	 * hook functions for page collection_edit.php go here.
	 */

	include_once __DIR__ . '/../include/model.php';
	include_once __DIR__ . '/../include/controller.php';

	/**
	 * Hook realizes "question-div" containing the option for registering all resources in a collection.
	 *
	 * @return bool
	 */
	function HookDoiCollection_editAdditionalfields2() {

		if (doi_check_user_access()) {

			global $ref; # collection ref

			$res_refs = get_collection_resources($ref);

			$total = count($res_refs);
			if ($total !== 0) {

				#collection not empty

				$resource_fields = get_resource_field_data_batch($res_refs); # get resource data in array batch

				$not_yet_immutable = 0;
				$doi_set_ours = 0;
				$doi_set_ext = 0;
				$no_title = 0;
				$ready_to_reg = [];

				# decide which resources to register
				foreach ($resource_fields as $res_ref => $fields) {
					$resource = get_resource_data($res_ref);
					if (!doi_check_resource_state($resource)) {
						$not_yet_immutable++;
					}
					else {
						$doi = doi_extract_doi($fields);
						if ($doi) {
							if (doi_is_ours($doi)) {
								$doi_set_ours++;
							}
							else {
								$doi_set_ext++;
							}
						}
						else {
							if (!doi_resource_has_title($resource, $fields)) {
								$no_title++;
							}
							# only save fields for relevant resources
							$ready_to_reg[$res_ref] = $fields;
						}
					}
				}

				$total = count($res_refs);
				$doi_already = $doi_set_ext + $doi_set_ours;

				global $lang, $doi_archive_state, $doi_pref_title_fields_default;

				# construct confirmation summary
				$sum = '';
				if ($doi_already > 0)
					$sum .= $doi_already . ' ' . $lang['doi_sum_of'] . " $total " . $lang['doi_sum_already_reg'] . '\n';
				if ($not_yet_immutable > 0)
					$sum .= $not_yet_immutable . ' ' . $lang['doi_sum_of'] . " $total $lang[doi_sum_not_yet_archived] " . strtolower($lang['status' . $doi_archive_state]) . " $lang[doi_sum_not_yet_archived_2]" . '\n';
				if ($no_title > 0)
					$sum .= $no_title . ' ' . $lang['doi_sum_of'] . " $total " . $lang['doi_sum_no_title'] . '\n <' . htmlspecialchars($lang['doi_datacite_unknown_info_codes'][$doi_pref_title_fields_default]) . '>\n' . $lang['doi_sum_no_title_2'] . '\n';
				if (count($ready_to_reg) > 0) {
					$sum .= count($ready_to_reg) . ' ' . $lang['doi_sum_of'] . " $total " . $lang['doi_sum_ready_for_reg'] . '\n\n   ';
					$sum .= str_replace('x', count($ready_to_reg), $lang['doi_sure_register_resource']);
				}

				global $baseurl_short;

				?>
				<!-- DOI -->
				<div class="Question">
					<label>DOI</label>

					<div class="Fixed">
						<a onclick="return confirm('<?php echo $sum; ?>')<?php if (count($ready_to_reg) == 0) echo ' && false'; ?>;"
							<?php if (count($ready_to_reg) > 0) echo 'href="' . $baseurl_short . 'pages/collection_edit.php?ref=' . $ref . '&registerdois=yes"'; ?>><?php echo $lang['doi_register_all']; ?>
							&gt;</a>
					</div>
					<div class="clearerleft"></div>
				</div>
				<!-- End DOI -->
				<?php

				# fetch registration flag
				$doi_state = getvalescaped('registerdois', '');

				if ($doi_state === 'yes') {

					$success = [];
					$titles = [];
					$metas = [];

					# registration
					foreach ($ready_to_reg as $res_ref => $fields) {
						$resource = get_resource_data($res_ref);
//						echo 'saved title: ' . $resource['title'];
						$meta = doi_construct_metadata($resource, $fields);
						$metas[$res_ref] = $meta;
						$titles[$res_ref] = $meta['title'];
						$success[$res_ref] = [];

						global $doi_current_ref;
						$doi_current_ref = $res_ref;

						$success[$res_ref]['xml'] = doi_post_xml($meta['xml']);
						$success[$res_ref]['url'] = doi_post_url($meta['doi'], $meta['url']);
						$success[$res_ref]['doi'] = $success[$res_ref]['xml'] && $success[$res_ref]['url'];

						$doi_current_ref = -1;

						if ($success[$res_ref]['doi']) {
							doi_update_resource_doi($res_ref, $meta['doi']);
						}
					}

					global $lang;

					# construct summary
					$summary = count($success) . ' ' . $lang['doi_successfully_registered_pl'] . '\n';

					$failed = count($ready_to_reg) - count($success);

					if($failed) {
						$summary .= $failed . ' ' . $lang['doi_not_successfully_registered_pl'];
					}

					global $doi_err_cache;

					if (!empty($doi_err_cache)) {
						# add errors to summary
						foreach ($doi_err_cache as $n => $err) {
							$summary .= '\n' . $err;
						}
						doi_clear_cache($doi_err_cache);
					}

					?>
					<script type="text/javascript">
						alert('<?php echo $summary; ?>');
						window.location.href = window.location.href.substring(0, window.location.href.indexOf('&')); // cut of get params and reload
					</script>
					<?php
				}
			}
		}

		return TRUE;
	}
