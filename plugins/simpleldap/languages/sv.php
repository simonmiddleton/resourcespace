<?php


$lang["simpleldap_ldaptype"]='Katalogleverantör.';
$lang["ldapserver"]='LDAP-server';
$lang["ldap_encoding"]='Datakodning mottagen från LDAP-servern (sätts om det inte är UTF-8 och data inte visas korrekt - t.ex. visningsnamn)';
$lang["domain"]='AD-domän; om flera, separera med semikolon.';
$lang["emailsuffix"]='E-postfix - används om ingen e-postattributdata hittades.';
$lang["port"]='Port (Swedish translation): Port (This word is the same in both English and Swedish).';
$lang["basedn"]='Bas-DN. Om användare finns i flera DN, separera med semikolon.';
$lang["loginfield"]='Inloggningsfält';
$lang["usersuffix"]='Användarsuffix (en punkt kommer att läggas till framför suffixet)';
$lang["groupfield"]='Gruppfält';
$lang["createusers"]='Skapa användare.';
$lang["fallbackusergroup"]='Reservanvändargrupp';
$lang["ldaprsgroupmapping"]='LDAP-ResourceSpace Gruppavbildning';
$lang["ldapvalue"]='LDAP-värde';
$lang["rsgroup"]='ResourceSpace Grupp';
$lang["addrow"]='Lägg till rad.';
$lang["email_attribute"]='Attribut att använda för e-postadress.';
$lang["phone_attribute"]='Attribut att använda för telefonnummer.';
$lang["simpleldap_telephone"]='Telefon';
$lang["simpleldap_unknown"]='okänd';
$lang["simpleldap_update_group"]='Uppdatera användargrupp vid varje inloggning. Om inte AD-grupper används för att bestämma åtkomst, sätt detta till falskt så att användare kan befordras manuellt.';
$lang["simpleldappriority"]='Prioritet (högre nummer har företräde)';
$lang["simpleldap_create_new_match_email"]='E-postmatchning: Kontrollera om LDAP-e-posten matchar en befintlig RS-kontoe-post och anta det kontot. Kommer att fungera även om "Skapa användare" är inaktiverat.';
$lang["simpleldap_allow_duplicate_email"]='Tillåt nya konton att skapas om det finns befintliga konton med samma e-postadress? (detta åsidosätts om e-postmatchning är inställd ovan och en matchning hittas)';
$lang["simpleldap_multiple_email_match_subject"]='ResourceSpace - försök till inloggning med konfliktande e-postadress.';
$lang["simpleldap_multiple_email_match_text"]='En ny LDAP-användare har loggat in men det finns redan mer än ett konto med samma e-postadress:';
$lang["simpleldap_notification_email"]='Notifieringsadress t.ex. om dubbletter av e-postadresser registreras. Om det är tomt skickas ingen notis.';
$lang["simpleldap_duplicate_email_error"]='Det finns redan ett konto med samma e-postadress. Vänligen kontakta din administratör.';
$lang["simpleldap_no_group_match_subject"]='ResourceSpace - ny användare utan grupp-tilldelning.';
$lang["simpleldap_no_group_match"]='En ny användare har loggat in men det finns ingen ResourceSpace-grupp som är kopplad till någon kataloggrupp som de tillhör.';
$lang["simpleldap_usermemberof"]='Användaren är medlem i följande kataloggrupper: -';
$lang["simpleldap_test"]='Testa LDAP-konfigurationen';
$lang["simpleldap_testing"]='Testar LDAP-konfigurationen.';
$lang["simpleldap_connection"]='Anslutning till LDAP-server';
$lang["simpleldap_bind"]='Anslut till LDAP-server';
$lang["simpleldap_password"]='Lösenord.';
$lang["simpleldap_test_auth"]='Testautentisering.';
$lang["simpleldap_domain"]='Domän';
$lang["simpleldap_displayname"]='Visningsnamn';
$lang["simpleldap_memberof"]='Medlem av';
$lang["simpleldap_test_title"]='Test (Swedish): Test';
$lang["simpleldap_result"]='Resultat';
$lang["simpleldap_retrieve_user"]='Hämta användarinformation.';
$lang["simpleldap_externsion_required"]='PHP LDAP-modulen måste vara aktiverad för att denna plugin ska fungera.';
$lang["simpleldap_usercomment"]='Skapad av SimpleLDAP-pluginet.';
$lang["simpleldap_usermatchcomment"]='Uppdaterad till LDAP-användare av SimpleLDAP.';
$lang["origin_simpleldap"]='EnkelLDAP-tillägg.';
$lang["simpleldap_LDAPTLS_REQCERT_never_label"]='Kontrollera inte FQDN för servern mot CN i certifikatet.';
$lang["simpleldap_username"]='Användarnamn/Användar-DN.';