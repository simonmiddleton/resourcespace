<?php
	/**
	 * @file view.php
	 *
	 * hook functions for view.php page go here.
	 */

	include __DIR__ . "/../include/controller.php";
	include __DIR__ . "/../include/model.php";

	/**
	 * hook function that realizes an option in the resource tool panel called "> DOI". Pressing it will reload the
	 * page shifting the so called "doi_state". Being set, a resource panel containing all metainformation for DOI
	 * regsitration will be shown (see HookDoiViewCustompanels()).
	 *
	 * @return bool
	 */
	function HookDoiViewAfterresourceactions() {

		# fetch registration step
		$doi_state = getvalescaped('doi_state', 'anchor');

		$summary = getvalescaped('doi_sum', '');

		if ($summary) {
			$summary = str_replace('\'', '"', $summary);
			$summary = str_replace('<br>', '\n', $summary);

			?>
			<!-- DOI summary -->
			<script type="text/javascript">
				window.alert('<?php echo str_replace('\'', '"', htmlspecialchars_decode($summary)); ?>');
			</script>
			<!-- End DOI summary -->
			<?php
		}

		global $resource;

		if (doi_check_user_access() && doi_check_resource_state($resource)) {

			global $fields, $ref;

			$doi = doi_extract_doi($fields);
			if (!$doi || doi_is_ours($doi)) {

				if ($doi_state == 'anchor' || $doi_state == 'submit') {

					?>
					<!-- DOI resource action -->
					<li>
						<a href="view.php?ref=<?php echo $ref; ?>&doi_state=metadata#doiRecordBox">
							<i class="fa fa-archive" aria-hidden="true"></i>&nbsp;DOI</a>
					</li>
					<!-- End DOI resource action --><?php
				}
				if ($doi_state == 'submit') {

					$meta['doi'] = trim(htmlspecialchars_decode(getvalescaped('doi_doi', '')));
					$meta['url'] = trim(htmlspecialchars_decode(getvalescaped('doi_url', '')));
					$meta['xml'] = trim(htmlspecialchars_decode(getval('doi_xml', '')));

					$reconstructed_meta = NULL;

					foreach ($meta as $key => $value) {
						if ($value == '') {
							# error getting post value
							if (!$reconstructed_meta) {
								$reconstructed_meta = doi_construct_metadata($resource, $fields);
							}
							$meta[$key] = $reconstructed_meta[$key];
						}
						else {
							unset($_POST[$key]);
						}
					}

					global $doi_current_ref;

					$doi_current_ref = $ref;

					#register
					$success['url'] = doi_post_xml($meta['xml']);
					$success['xml'] = doi_post_url($meta['doi'], $meta['url']);
					$success['doi'] = $success['url'] && $success['xml'];

					$doi_current_ref = -1;

					$summary = '';

					global $lang;

					foreach ($success as $key => $value) {
						$summary .= "[$key] ";
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
						$summary .= '\n';
					}

					global $doi_err_cache;
					if (empty($doi_err_cache)) {

						# only save doi if registration succeeded completely
						if ($success['doi']) {
							doi_update_resource_doi($ref, $meta['doi']);
						}
					}
					else {

						# registration did not succeed
						foreach ($doi_err_cache as $n => $err) {
							$summary .= '\n' . $err;
						}
						doi_clear_cache($doi_err_cache);
					}

					global $baseurl;

					$summary = str_replace('\n', '<br>', $summary);

					?>
					<!-- DOI page reload -->
					<script type="text/javascript">
						window.location.href = '<?php echo "$baseurl/pages/view.php?ref=$ref&doi_sum=" . $summary; ?>';
					</script>
					<!-- End DOI page reload -->
					<?php
				}
			}
		}

		return TRUE;
	}

	/**
	 * hook function that realizes a resource panel containing all metainformation for DOI registration and further
	 * buttons to submit or cancel registration.
	 *
	 * @return bool
	 */
	function HookDoiViewCustompanels() {

		$doi_state = getvalescaped('doi_state', 'anchor');

		global $resource;

		if (doi_check_user_access() && doi_check_resource_state($resource)) {

			if ($doi_state == 'metadata') {

				global $ref, $fields;

				$doi = doi_extract_doi($fields);
				$url = '';
				$xml = '';

				if ($doi && !doi_is_ours($doi)) return TRUE;

				$meta = doi_construct_metadata($resource, $fields);
				$doi = $meta['doi'];
				$xml = $meta['xml'];
				$url = $meta['url'];

				global $lang, $baseurl, $edit_show_save_clear_buttons_at_top;

				$xml_line_count = count(explode("\n", $xml)) + 1;

				$form = <<<HTML
					<form id="submitdoimetadata" method="POST">
						<input type="hidden" name="doi_state" value='submit'/>
						<input id="btn_submit" type="submit"
						       onClick="return confirm('{$lang['doi_sure']}');"
						       value="{$lang['doi_register']}"/>
						<a target="_blank" href="../plugins/doi/pages/setup.php"><input type="button" value="{$lang['options']}"/></a>
						<a onclick="location.reload();	"><input type="button" value="{$lang['doi_reload']}"/></a>
						<input id="btn_cancel" type="button"
						       onClick="window.location.href='$baseurl/pages/view.php?ref=$ref';"
						       value="{$lang['doi_cancel']}"/>
					</form>
HTML;

				?>
				<!-- DOI record box -->
				<script type="text/javascript">

					function doiShowHideXml() {
						var div = document.getElementById("divDoiXml");
						var anchor = document.getElementById("doiShowHideXml");
						if (div.style.display == "none") {
							div.style.display = "block";
							anchor.innerHTML = "<?php echo '&gt; ' . $lang['doi_hide_meta']?>";
						} else if (div.style.display == "block") {
							div.style.display = "none";
							anchor.innerHTML = "<?php echo '&gt; ' . $lang['doi_show_meta']?>";
						}
					}
				</script>
				<div class="RecordBox" id="doiRecordBox">
					<div class="RecordPanel dark" style="background-color: #F0F0E8">
						<div class="Title">DOI</div>
						<div id="doiMetadata">
							<?php if($edit_show_save_clear_buttons_at_top) echo $form;?>
							<br>
							<br>
							<label for="doiField">DOI </label>
							<br>
							<textarea id="doiField" name="doi_doi" form="submitdoimetadata"
							          style="width: 100%; resize: none;"
							          readonly><?php echo $doi; ?></textarea>
							<br><br>

							<label for="urlField">URL</label>
							<br>
							<textarea id="urlField" name="doi_url" form="submitdoimetadata"
							          style="width: 100%; resize: none;"
							          readonly><?php echo $url; ?></textarea>
							<br>
<!--							<a href="#" onclick="doiShowHideXml();"-->
<!--							   id="doiShowHideXml">--><?php //echo '&gt; ' . $lang['doi_show_meta']; ?><!--</a><br>-->
							<div id="divDoiXml" style="display: block">
								<br>
								<label for="editDoiXml">XML</label>
			        				<textarea style="width: 100%; resize: none" id="editDoiXml" name="doi_xml" readonly
							                  form="submitdoimetadata" wrap="soft"
							                  rows="<?php echo $xml_line_count; ?>"><?php echo $xml; ?></textarea>
							</div>
							<br>
							<?php echo $form;?>
						</div>
						<br>
					</div>
					<div class="PanelShadow"></div>
				</div>
				<!-- End DOI record box -->
				<?php
			}
		}

		return TRUE;
	}

