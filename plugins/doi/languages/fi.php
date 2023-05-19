<?php


$lang["status4"]='Muuttumaton.';
$lang["doi_info_link"]='DOIn (Digital Object Identifier) kohdalla.';
$lang["doi_info_metadata_schema"]='DOI-rekisteröinnissä DataCite.org-sivustolla ilmoitetaan <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">DataCite Metadata Schema -dokumentaatiossa</a>.';
$lang["doi_info_mds_api"]='DOI-API:ta, jota tämä liitännäinen käyttää, on kuvattu <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API -dokumentaatiossa</a>.';
$lang["doi_plugin_heading"]='Tämä lisäosa luo <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI-tunnisteita</a> muuttumattomille objekteille ja kokoelmille ennen niiden rekisteröintiä <a target="_blank" href="https://www.datacite.org/about-datacite">DataCitessa</a>.';
$lang["doi_further_information"]='Lisätietoja';
$lang["doi_setup_doi_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">Etuliite</a> DOI-generointia varten.';
$lang["doi_info_prefix"]='doi-etuliitteistä.';
$lang["doi_setup_use_testmode"]='Käytä <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testitilaa</a>.';
$lang["doi_info_testmode"]='testitilassa.';
$lang["doi_setup_use_testprefix"]='Käytä sen sijaan <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testi-etuliitettä (10.5072)</a>.';
$lang["doi_info_testprefix"]='testi-etuliitteellä.';
$lang["doi_setup_publisher"]='Julkaisija';
$lang["doi_info_publisher"]='kustantaja-kentässä <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10"> (publisher)</a>.';
$lang["doi_resource_conditions_title"]='Resurssin on täytettävä seuraavat edellytykset, jotta se voidaan rekisteröidä DOI:ksi:';
$lang["doi_resource_conditions"]='<li>Projektisi täytyy olla julkinen, eli sillä täytyy olla julkinen alue.</li>
<li>Resurssin täytyy olla julkisesti saatavilla, eli sen käyttöoikeuden täytyy olla asetettu <strong>avoin</strong>.</li>
<li>Resurssilla täytyy olla <strong>otsikko</strong>.</li>
<li>Sen täytyy olla merkitty {status}, eli sen tilan täytyy olla asetettu <strong>{status}</strong>.</li>
<li>Tämän jälkeen vain <strong>ylläpitäjä</strong> voi aloittaa rekisteröintiprosessin.</li>';
$lang["doi_setup_general_config"]='Yleinen määritys.';
$lang["doi_setup_pref_fields_header"]='Suositellut hakukentät metatietojen rakentamiseen.';
$lang["doi_setup_username"]='DataCite käyttäjätunnus';
$lang["doi_setup_password"]='DataCite-salasana';
$lang["doi_pref_publicationYear_fields"]='Etsi <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Julkaisuvuosi</a> kohteesta:<br>(Mikäli arvoa ei löydy, käytetään rekisteröintivuotta.)';
$lang["doi_pref_creator_fields"]='Etsi <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Luoja</a> kohteesta:';
$lang["doi_pref_title_fields"]='Etsi <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Otsikko</a> kohteesta:';
$lang["doi_setup_default"]='Jos arvoa ei löydy, käytä <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standardikoodia</a>:';
$lang["doi_setup_test_plugin"]='Testi lisäosa.';
$lang["doi_setup_test_succeeded"]='Testi onnistui!';
$lang["doi_setup_test_failed"]='Testi epäonnistui!';
$lang["doi_alert_text"]='Huomio! Kun DOI on lähetetty DataCitelle, rekisteröintiä ei voi peruuttaa.';
$lang["doi_title_compulsory"]='Ole hyvä ja aseta otsikko ennen kuin jatkat DOI-rekisteröintiä.';
$lang["doi_register"]='Rekisteröidy.';
$lang["doi_cancel"]='Peruuta.';
$lang["doi_sure"]='Huomio! Kun DOI on lähetetty DataCitelle, rekisteröintiä ei voi peruuttaa. DataCiten Metadata Storeen jo rekisteröity tieto saattaa mahdollisesti ylikirjoittua.';
$lang["doi_already_set"]='Jo asetettu.';
$lang["doi_not_yet_set"]='Ei vielä asetettu.';
$lang["doi_already_registered"]='jo rekisteröity.';
$lang["doi_not_yet_registered"]='ei vielä rekisteröitynyt';
$lang["doi_successfully_registered"]='rekisteröitiin onnistuneesti';
$lang["doi_successfully_registered_pl"]='Resurssi(t) on/onnistuneesti rekisteröity.';
$lang["doi_not_successfully_registered"]='Ei voitu rekisteröidä oikein.';
$lang["doi_not_successfully_registered_pl"]='Ei voitu rekisteröidä oikein.';
$lang["doi_reload"]='Lataa uudelleen.';
$lang["doi_successfully_set"]='on asetettu.';
$lang["doi_not_successfully_set"]='Ei ole asetettu.';
$lang["doi_sum_already_reg"]='Resurssilla tai resursseilla on jo DOI.';
$lang["doi_sum_not_yet_archived"]='Resurssia ei ole merkitty.';
$lang["doi_sum_not_yet_archived_2"]='Mutta sen/tämän resurssin saatavuus ei ole asetettu avoimeksi.';
$lang["doi_sum_ready_for_reg"]='Resurssit ovat valmiita rekisteröintiä varten.';
$lang["doi_sum_no_title"]='Resurssilla(t) ei ole vielä otsikkoa. Käytetään...';
$lang["doi_sum_no_title_2"]='"nimikkeenä sen sijaan"';
$lang["doi_register_all"]='Rekisteröi DOIt kaikille tämän kokoelman resursseille.';
$lang["doi_sure_register_resource"]='Jatketaanko x resurssin rekisteröintiä?';
$lang["doi_show_meta"]='Näytä DOI:n metatiedot.';
$lang["doi_hide_meta"]='Piilota DOI metatiedot.';
$lang["doi_fetched_xml_from_MDS"]='Nykyinen XML-metatieto saatiin haettua onnistuneesti DataCiten metatietovarastosta.';