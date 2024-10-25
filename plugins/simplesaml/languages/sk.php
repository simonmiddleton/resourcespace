<?php


$lang["simplesaml_configuration"]='Konfigurácia SimpleSAML';
$lang["simplesaml_main_options"]='Možnosti uporabe';
$lang["simplesaml_site_block"]='Použite SAML na úplné blokovanie prístupu na stránku. Ak je nastavené na hodnotu "true", nikto nemôže získať prístup na stránku, ani anonymne, bez autentifikácie';
$lang["simplesaml_allow_public_shares"]='Ak je stránka blokovaná, umožni verejné zdieľanie obísť SAML autentifikáciu?';
$lang["simplesaml_allowedpaths"]='Zoznam ďalších povolených ciest, ktoré môžu obísť požiadavku SAML';
$lang["simplesaml_allow_standard_login"]='Povolit užívateľom prihlásenie pomocou štandardných účtov, ako aj pomocou SAML SSO? VAROVANIE: Vypnutie tejto možnosti môže spôsobiť riziko uzamknutia všetkých užívateľov z systému, ak zlyhá SAML autentifikácia';
$lang["simplesaml_use_sso"]='Použite SSO na prihlásenie sa';
$lang["simplesaml_idp_configuration"]='Konfigurácia IdP (Identity Provider)';
$lang["simplesaml_idp_configuration_description"]='Použite nasledujúce nastavenia pre konfiguráciu pluginu, aby pracoval s vaším IdP';
$lang["simplesaml_username_attribute"]='Atribút(y) použité pre používateľské meno. Ak ide o zreťazenie dvoch atribútov, oddelte ich čiarkou';
$lang["simplesaml_username_separator"]='Ak používate spojovacie polia pre používateľské meno, použite tento znak ako oddeľovač';
$lang["simplesaml_fullname_attribute"]='Atribút(y) použité pre celé meno. Ak ide o zreťazenie dvoch atribútov, prosím oddelite ich čiarkou';
$lang["simplesaml_fullname_separator"]='"Ak používate spojenie polí pre celé meno, použite tento znak ako oddeľovač."';
$lang["simplesaml_email_attribute"]='Atribút na použitie pre emailovú adresu';
$lang["simplesaml_group_attribute"]='Atribút na použitie na určenie členstva v skupine';
$lang["simplesaml_username_suffix"]='Prípona, ktorá sa pridáva k vytvoreným používateľským menám na odlišenie od štandardných účtov v ResourceSpace';
$lang["simplesaml_update_group"]='Aktualizovať skupinu používateľov pri každom prihlásení. Ak sa nepoužíva atribút SSO skupiny na určenie prístupu, nastavte ho na false, aby bolo možné používateľov manuálne presúvať medzi skupinami';
$lang["simplesaml_groupmapping"]='SAML - Mapovanie skupín ResourceSpace';
$lang["simplesaml_fallback_group"]='Predvolená skupina používateľov, ktorá bude použitá pre novovytvorených používateľov';
$lang["simplesaml_samlgroup"]='SAML skupina';
$lang["simplesaml_rsgroup"]='Skupina v ResourceSpace-u';
$lang["simplesaml_priority"]='Priorita (vyššie číslo bude mať prednosť)';
$lang["simplesaml_addrow"]='Pridať mapovanie';
$lang["simplesaml_service_provider"]='Názov miestneho poskytovateľa služieb (SP)';
$lang["simplesaml_prefer_standard_login"]='Uprednostniť štandardné prihlásenie (predvolene presmerovať na prihlasovaciu stránku)';
$lang["simplesaml_sp_configuration"]='Konfigurácia simplesaml SP musí byť dokončená, aby bolo možné použiť tento plugin. Pre viac informácií sa pozrite do článku v báze znalostí';
$lang["simplesaml_custom_attributes"]='Používateľské atribúty na zaznamenávanie do záznamu používateľa';
$lang["simplesaml_custom_attribute_label"]='Atribút SSO -';
$lang["simplesaml_usercomment"]='Vytvorené pomocou doplnku SimpleSAML';
$lang["origin_simplesaml"]='Plugin SimpleSAML';
$lang["simplesaml_lib_path_label"]='Cesta knižnice SAML (prosím, uveďte úplnú cestu na serveri)';
$lang["simplesaml_login"]='Použite SAML poverenia na prihlásenie do ResourceSpace? (Toto je relevantné iba ak je vyššie uvedená možnosť povolená)';
$lang["simplesaml_create_new_match_email"]='Zhoda e-mailov: Pred vytvorením nových používateľov skontrolujte, či sa e-mail SAML používateľa zhoduje s existujúcim e-mailom RS účtu. Ak sa zhoda nájde, SAML používateľ prevezme tento účet';
$lang["simplesaml_allow_duplicate_email"]='Povolit vytváranie nových účtov, ak existujú už existujúce účty v ResourceSpace s rovnakou e-mailovou adresou? (toto sa prekryje, ak je nastavené zhodovanie e-mailov a nájde sa jedna zhoda)';
$lang["simplesaml_multiple_email_match_subject"]='ResourceSpace SAML - konfliktný pokus o prihlásenie cez email';
$lang["simplesaml_multiple_email_match_text"]='Nový SAML používateľ získal prístup k systému, ale už existuje viac ako jeden účet s rovnakou e-mailovou adresou.';
$lang["simplesaml_multiple_email_notify"]='E-mailová adresa pre upozornenie v prípade konfliktu e-mailových správ';
$lang["simplesaml_duplicate_email_error"]='Existuje už existujúci účet s rovnakou emailovou adresou. Prosím, kontaktujte svojho administrátora.';
$lang["simplesaml_usermatchcomment"]='Aktualizované na SAML používateľa pomocou SimpleSAML pluginu.';
$lang["simplesaml_usercreated"]='Vytvorený nový SAML používateľ';
$lang["simplesaml_duplicate_email_behaviour"]='Správa duplicitných účtov';
$lang["simplesaml_duplicate_email_behaviour_description"]='Táto sekcia riadi, čo sa stane, ak sa nový SAML používateľ prihlási a vytvorí konflikt s existujúcim účtom';
$lang["simplesaml_authorisation_rules_header"]='Pravidlo autorizácie';
$lang["simplesaml_authorisation_rules_description"]='Povolite konfiguráciu ResourceSpace s dodatočným miestnym overovaním používateľov na základe ďalšieho atribútu (tj. tvrdenia/claimu) v odpovedi od IdP. Toto tvrdenie bude použité pluginom na určenie, či má používateľ povolenie prihlásiť sa do ResourceSpace alebo nie.';
$lang["simplesaml_authorisation_claim_name_label"]='Názov atribútu (tvrdenie/nárok)';
$lang["simplesaml_authorisation_claim_value_label"]='Hodnota atribútu (tvrdenie/hlásenie)';
$lang["simplesaml_authorisation_login_error"]='Nemáte prístup k tejto aplikácii! Prosím, kontaktujte administrátora vášho účtu!';
$lang["simplesaml_authorisation_version_error"]='DÔLEŽITÉ: Vaša konfigurácia SimpleSAML musí byť aktualizovaná. Pre viac informácií sa pozrite do sekcie „Migrácia SP na použitie konfigurácie ResourceSpace“ v báze znalostí na adrese \'<a href=\'https://www.resourcespace.com/knowledge-base/plugins/simplesaml#saml_instructions_migrate\' target=\'_blank\'>\'';
$lang["simplesaml_healthcheck_error"]='Chyba pluginu SimpleSAML';
$lang["simplesaml_rsconfig"]='Použite štandardné konfiguračné súbory ResourceSpace na nastavenie konfigurácie SP a metadát? Ak je to nastavené na false, potom je potrebné manuálne upravovať súbory';
$lang["simplesaml_sp_generate_config"]='Vytvoriť konfiguráciu SP';
$lang["simplesaml_sp_config"]='Konfigurácia poskytovateľa služieb (SP)';
$lang["simplesaml_sp_data"]='Informácie poskytovateľa služieb (SP)';
$lang["simplesaml_idp_section"]='IdP (Identity Provider) - Poskytovateľ identity';
$lang["simplesaml_idp_metadata_xml"]='Vložiť IdP Metadata XML';
$lang["simplesaml_sp_cert_path"]='Cesta k súboru certifikátu SP (nechajte prázdne na vygenerovanie, ale vyplňte podrobnosti o certifikáte nižšie)';
$lang["simplesaml_sp_key_path"]='Cesta k SP kľúčovému súboru (.pem) (nechajte prázdne pre vygenerovanie)';
$lang["simplesaml_sp_idp"]='Identifikátor IdP (nevyplňujte, ak spracovávate XML)';
$lang["simplesaml_saml_config_output"]='Vložte toto do konfiguračného súboru ResourceSpace';
$lang["simplesaml_sp_cert_info"]='Informácie o certifikáte (vyžadované)';
$lang["simplesaml_sp_cert_countryname"]='Kód krajiny (iba 2 znaky)';
$lang["simplesaml_sp_cert_stateorprovincename"]='Názov štátu, okresu alebo provincie';
$lang["simplesaml_sp_cert_localityname"]='Lokalita (napr. mesto)';
$lang["simplesaml_sp_cert_organizationname"]='Názov organizácie';
$lang["simplesaml_sp_cert_organizationalunitname"]='Organizačná jednotka / oddelenie';
$lang["simplesaml_sp_cert_commonname"]='Bežné meno (napr. sp.acme.org)';
$lang["simplesaml_sp_cert_emailaddress"]='E-mailová adresa';
$lang["simplesaml_sp_cert_invalid"]='Neplatné informácie o certifikáte';
$lang["simplesaml_sp_cert_gen_error"]='Nemožno vygenerovať certifikát';
$lang["simplesaml_sp_samlphp_link"]='Navštívte testovaciu stránku SimpleSAMLphp';
$lang["simplesaml_sp_technicalcontact_name"]='Meno technického kontaktu';
$lang["simplesaml_sp_technicalcontact_email"]='Technický kontaktný e-mail';
$lang["simplesaml_sp_auth.adminpassword"]='Heslo správcu testovacej stránky SP';
$lang["simplesaml_acs_url"]='ACS URL / Reply URL - ACS URL / Odpoved URL';
$lang["simplesaml_entity_id"]='Identifikátor entity/metadata URL';
$lang["simplesaml_single_logout_url"]='Jednotná URL adresa pro odhlášení';
$lang["simplesaml_start_url"]='Začiatočná URL adresa/Prihlásenie sa na URL adresu';
$lang["simplesaml_existing_config"]='Sledujte pokyny v báze znalostí pre migráciu vašej existujúcej SAML konfigurácie';
$lang["simplesaml_test_site_url"]='URL testovacej stránky SimpleSAML';
$lang["plugin-simplesaml-title"]='Jednoduchý SAML';
$lang["plugin-simplesaml-desc"]='[Pokročilo] Zahtevaj SAML overjanje za dostop do ResourceSpace';
$lang["simplesaml_idp_certs"]='SAML IdP certifikáty';
$lang["simplesaml_idp_cert_expiring"]='IdP %idpname certifikat poteče ob %expiretime';
$lang["simplesaml_idp_cert_expired"]='IdP %idpname certifikat je potekel ob %expiretime';
$lang["simplesaml_idp_cert_expires"]='IdP %idpname certifikat poteče ob %expiretime';