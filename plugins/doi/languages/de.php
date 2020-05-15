<?php
	/**
	 * @file de.php
	 *
	 * adds german strings to $lang array.
	 */

	$lang['status4'] = "Unveränderlich";
	$lang['doi_info_wikipedia'] = "https://de.wikipedia.org/wiki/Digital_Object_Identifier";
	$lang['doi_info_link'] = 'zu <a target="_blank" href="https://de.wikipedia.org/wiki/Digital_Object_Identifier">DOIs generell</a>.';
	$lang['doi_info_metadata_schema'] = 'zur Registrierung von DOIs bei DataCite finden sie in der <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Datacite-Metadaten-Schema-Dokumentation</a>.';
	$lang['doi_info_mds_api'] = 'zur unterliegenden API von DataCite finden sie in der <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite-API-Dokumentation</a>.';

	$lang['doi_plugin_heading'] = 'Dieses Plugin erzeugt <a target="_blank" href="https://de.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> für unveränderliche Sammlungsobjekte und Kollektionen und registriert diese bei <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
	$lang['doi_further_information'] = 'Weiterführende Informationen';

	$lang['doi_setup_doi_prefix'] = '<a target="_blank" href="https://de.wikipedia.org/wiki/Digital_Object_Identifier#Format">Präfix</a> für die Erzeugung von DOIs';
	$lang['doi_info_prefix'] = 'zu <a target="_blank" href="https://de.wikipedia.org/wiki/Digital_Object_Identifier#Format">DOI Präfixen</a>.';

	$lang['doi_setup_use_testmode'] = '<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">Testmodus</a> verwenden';
	$lang['doi_info_testmode'] = 'zum <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">Testmodus</a>.';

	$lang['doi_setup_use_testprefix'] = 'Stattdessen den <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">Testpräfix (10.5072)</a> verwenden';
	$lang['doi_info_testprefix'] = 'zum <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">Testpräfix</a>.';

	$lang['doi_setup_publisher'] = '<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Publisher</a>';
	$lang['doi_info_publisher'] = 'zum <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Publisher</a>-Feld.';

	$lang['doi_resource_conditions_title'] = 'Eine Ressource muss die folgenden Bedingungen erfüllen, damit für sie ein DOI registriert werden kann:';
	$lang['doi_resource_conditions'] = <<<HTML
<li>Das Projekt, in dem die Ressource liegt, muss offen sein, also einen &oumlffentlichen Bereich haben.</li>
<li>Die Ressource selbst muss als öffentlich zugänglich, also <strong>offen</strong> gekennzeichnet sein.</li>
<li>Sie muss einen <strong>Titel</strong> haben.</li>
<li>Sie muss mithilfe des Status als <strong>{status}</strong> markiert sein.</li>
<li>Die Registrierung kann dann nur von einem <strong>Administrator</strong> des Projekts durchgeführt werden.</li>
HTML;

//	$lang['doi_setup_perms_needed'] = 'Benötigte Zugriffsrechte';
//	$lang['doi_setup_field_shortname'] = 'Für das Plugin verwendeter Kurzname';

	$lang['doi_setup_username'] = 'DataCite-Username';
	$lang['doi_setup_password'] = 'DataCite-Passwort';

	$lang['doi_setup_general_config'] = 'Generelle Konfiguration';
	$lang['doi_setup_pref_fields_header'] = 'Bevorzugte Suchfelder für die Zusammenstellung der Metadaten';

//	$lang['doi_pref_creator_fields'] = 'Suche für \'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a>\' in:<br>(Falls dort kein Wert gefunden wird, wird die UploaderIn als Creator verwendet.)';
//	$lang['doi_pref_title_fields'] = 'Suche für \'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Title</a>\' in: <br>(Falls dort kein Wert gefunden wird, erscheint eine Fehlermeldung und die Ressource wird nichr registriert.)';
//	$lang['doi_pref_publicationYear_fields'] = 'Suche für \'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a>\' in:<br>(Falls dort kein Wert gefunden wird, wird das Upload-Jahr verwendet.)';

	$lang['doi_pref_creator_fields'] = 'Suche für <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a> in:';
	$lang['doi_pref_title_fields'] = 'Suche für <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Title</a> in:';
	$lang['doi_pref_publicationYear_fields'] = 'Suche für <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a> in:<br>(Falls dort kein Wert gefunden wird, wird das Jahr der Registrierung verwendet.)';

	# see: https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38
	$lang['doi_datacite_unknown_info_codes'] = [
		'(:unac)' => 'vorübergehend nicht abrufbar',
		'(:unal)' => 'unerlaubt, Wert bewusst nicht angegeben',
		'(:unap)' => 'nicht sinnvoll anwendbar',
		'(:unas)' => 'kein Wert zugewiesen (z.B. nicht betitelte Ressourcen)',
		'(:unav)' => 'Wert generell nicht verfügbar, möglicherweise unbekannt',
		'(:unkn)' => 'offiziell unbekannt (e.g., Anonymous, Inconnue)',
		'(:none)' => 'hatte noch nie einen Wert und wird in Zukunft auch nie(!) einen haben',
		'(:null)' => 'explizit und bewusst leer lassen',
		'(:tba)'  => 'wird nachgereicht',
//		'(:etal)' => '"et al: zu viele richtige Werte"'
	];

	$lang['doi_setup_default'] = "Falls kein Wert gefunden wurde, verwende <a href=\"https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38\" target=\"_blank\">Standard-Code</a>:";

	$lang['doi_setup_test_plugin'] = 'Plugin testen..';
	$lang['doi_setup_test_succeeded'] = 'Test erfolgreich!';
	$lang['doi_setup_test_failed'] = 'Test fehlgeschlagen!';

	$lang['doi_no_yes'] = ['Nein', 'Ja'];

	$lang['doi_title_compulsory'] = "Bitte geben sie der Ressource einen Titel, bevor sie mit der DOI-Registrierung fortfahren.";

	$lang['doi_register'] = 'Registrieren';
	$lang['doi_cancel'] = 'Abbrechen';
	$lang['doi_sure'] = 'Achtung! Die Registrierung kann nicht rückgängig gemacht werden. Bereits registrierte Metadaten im Metadatenspeicher von DataCite werden ggf. überschrieben.';
	$lang['doi_already_set'] = 'bereits gesetzt';
	$lang['doi_not_yet_set'] = 'noch nicht gesetzt';
	$lang['doi_already_registered'] = 'bereits registriert';
	$lang['doi_not_yet_registered'] = 'noch nicht registriert';
	$lang['doi_successfully_registered'] = 'wurde erfolgreich registriert';
	$lang['doi_successfully_registered_pl'] = 'Ressource(n) wurde(n) erfolgreich registriert.';
	$lang['doi_not_successfully_registered'] = 'konnte nicht registriert werden';
	$lang['doi_not_successfully_registered_pl'] = 'konnte/n nicht registriert werden.';

	$lang['doi_reload'] = "Neu laden";


	$lang['doi_successfully_set'] = 'wurde erfolgreich gesetzt.';
	$lang['doi_not_successfully_set'] = 'wurde nicht gesetzt.';

	$lang['doi_sum_of'] = 'von';
	$lang['doi_sum_already_reg'] = 'Ressource(n) besitzt/besitzen bereits einen DOI.';
	$lang['doi_sum_not_yet_archived'] = 'Ressource(n) ist/sind noch nicht als';
	$lang['doi_sum_not_yet_archived_2'] = 'markiert oder noch nicht öffentlich zugänglich.';
	$lang['doi_sum_ready_for_reg'] = 'Ressource(n) steht/stehen zur Registrierung bereit.';
	$lang['doi_sum_no_title'] = 'Ressource(n) fehlt noch ein Titel. Als Titel wird dann ';
	$lang['doi_sum_no_title_2'] = ' verwendet.';

	$lang['doi_register_all'] = 'Für alle Ressourcen dieser Kollektion DOIs registrieren';
	$lang['doi_sure_register_resource'] = 'Fortfahren und x Ressource(n) registrieren?';

	$lang['doi_show_meta'] = 'DOI Metadaten anzeigen';
	$lang['doi_hide_meta'] = 'DOI Metadaten verbergen';

	$lang['doi_fetched_xml_from_MDS'] = 'Aktuelle XMl-Metadaten konnten vom Metadata-Store von DataCite geladen werden.';
?>
