function metadataReport(ref,context) {
	jQuery('#' + context + 'metadata_report').load(
		baseurl + "/pages/ajax/metadata_report.php?ref="+ref+"&context=" + context
		);
	}
