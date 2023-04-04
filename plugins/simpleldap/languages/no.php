<?php


$lang["simpleldap_ldaptype"]='Katalogtilbyder';
$lang["ldapserver"]='LDAP-server';
$lang["ldap_encoding"]='Datakoding mottatt fra LDAP-serveren (settes hvis ikke UTF-8 og data ikke vises riktig - f.eks. visningsnavn)';
$lang["domain"]='AD-domene, hvis flere, skill med semikolon.';
$lang["emailsuffix"]='E-post-suffiks - brukes hvis ingen e-postattributdata er funnet.';
$lang["port"]='Port can have multiple meanings in the context of digital asset management software. Here are some possible translations:

- Port (noun): Port (in Norwegian: port) refers to a connection point through which data can be transferred between different systems or devices. For example, ResourceSpace may use a specific port number to communicate with other software or to serve its web interface. In this case, "port" can be translated as "portnummer" or "tilkoblingspunkt".
- Port (verb): Port (in Norwegian: overføre) can also be used as a verb to describe the action of transferring data from one system to another through a specific port. For example, you may need to port metadata from an old DAM system to ResourceSpace. In this case, "port" can be translated as "overføre" or "flytte over".
- Port (noun): Port (in Norwegian: havn) can also refer to a physical location where ships can dock and unload their cargo. This meaning is not directly related to digital asset management software, but it may be used metaphorically in some contexts. For example, you may say that ResourceSpace is the port where all your digital assets can safely dock and be organized. In this case, "port" can be translated as "havn" or "anløpshavn".

Please let me know if you need a more specific translation based on the context where "port" appears in ResourceSpace.';
$lang["basedn"]='Grundleggende DN. Hvis brukere er i flere DN-er, separer med semikolon.';
$lang["loginfield"]='Påloggingsfelt';
$lang["usersuffix"]='Bruker-suffiks (en prikk vil bli lagt til foran suffikset)';
$lang["groupfield"]='Gruppefelt';
$lang["createusers"]='Opprett brukere.';
$lang["fallbackusergroup"]='Alternativ brukergruppe.';
$lang["ldaprsgroupmapping"]='LDAP-ResourceSpace Gruppemapping.';
$lang["ldapvalue"]='LDAP-verdi';
$lang["rsgroup"]='ResourceSpace Gruppe';
$lang["addrow"]='Legg til rad.';
$lang["email_attribute"]='Attributt å bruke for e-postadresse.';
$lang["phone_attribute"]='Attributt å bruke for telefonnummer.';
$lang["simpleldap_telephone"]='Telefon.';
$lang["simpleldap_unknown"]='ukjent';
$lang["simpleldap_update_group"]='Oppdater brukergruppe ved hver pålogging. Hvis du ikke bruker AD-grupper for å bestemme tilgang, sett dette til "false" slik at brukere kan bli manuelt forfremmet.';
$lang["simpleldappriority"]='Prioritet (høyere tall vil ha forrang)';
$lang["simpleldap_create_new_match_email"]='E-post-samsvar: Sjekk om LDAP-e-posten samsvarer med en eksisterende RS-konto-e-post og overta den kontoen. Vil fungere selv om "Opprett brukere" er deaktivert.';
$lang["simpleldap_allow_duplicate_email"]='Tillat opprettelse av nye kontoer hvis det finnes eksisterende kontoer med samme e-postadresse? (dette overstyrer hvis e-post-samsvar er satt over og det finnes ett samsvar)';
$lang["simpleldap_multiple_email_match_subject"]='ResourceSpace - forsøk på pålogging med konfliktende e-postadresse.';
$lang["simpleldap_multiple_email_match_text"]='En ny LDAP-bruker har logget inn, men det finnes allerede mer enn én konto med samme e-postadresse:';
$lang["simpleldap_notification_email"]='Varslingsadresse, f.eks. hvis dupliserte e-postadresser er registrert. Hvis feltet er tomt, vil ingen varsler bli sendt.';
$lang["simpleldap_duplicate_email_error"]='Det finnes allerede en konto med samme e-postadresse. Vennligst kontakt administratoren din.';
$lang["simpleldap_no_group_match_subject"]='ResourceSpace - ny bruker uten gruppekobling.';
$lang["simpleldap_no_group_match"]='En ny bruker har logget på, men det finnes ingen ResourceSpace-gruppe som er kartlagt til noen kataloggruppe som de tilhører.';
$lang["simpleldap_usermemberof"]='Brukeren er medlem av følgende kataloggrupper: -';
$lang["simpleldap_test"]='Test LDAP-konfigurasjon';
$lang["simpleldap_testing"]='Testing av LDAP-konfigurasjon';
$lang["simpleldap_connection"]='Tilkobling til LDAP-server';
$lang["simpleldap_bind"]='Koble til LDAP-server';
$lang["simpleldap_username"]='Brukernavn/Bruker DN.';
$lang["simpleldap_password"]='Passord';
$lang["simpleldap_test_auth"]='Test autentisering.';
$lang["simpleldap_domain"]='Domene.';
$lang["simpleldap_displayname"]='Visningsnavn';
$lang["simpleldap_memberof"]='Medlem av';
$lang["simpleldap_test_title"]='Test can be translated to "Test" in Norsk.';
$lang["simpleldap_result"]='Resultat';
$lang["simpleldap_retrieve_user"]='Hente brukerdetaljer.';
$lang["simpleldap_externsion_required"]='PHP LDAP-modulen må være aktivert for at denne pluginen skal fungere.';
$lang["simpleldap_usercomment"]='Opprettet av SimpleLDAP-tillegget.';
$lang["simpleldap_usermatchcomment"]='Oppdatert til LDAP-bruker av SimpleLDAP.';
$lang["origin_simpleldap"]='EnkelLDAP-tillegg';
$lang["simpleldap_LDAPTLS_REQCERT_never_label"]='Ikke sjekk FQDN-en til serveren mot CN-en til sertifikatet.';