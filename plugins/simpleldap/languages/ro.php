<?php


$lang["simpleldap_ldaptype"]='Furnizor de directorii.';
$lang["ldapserver"]='Server LDAP.';
$lang["ldap_encoding"]='Codificarea datelor primită de la serverul LDAP (setată în cazul în care nu este UTF-8 și datele nu sunt afișate corect - de exemplu, numele afișat).';
$lang["domain"]='Domeniu AD, dacă sunt mai multe separate prin punct şi virgulă.';
$lang["emailsuffix"]='Sufixul de e-mail - utilizat dacă nu se găsesc date de atribut de e-mail.';
$lang["port"]='Port - Port (referring to a digital asset management software context)';
$lang["basedn"]='DN de bază. Dacă utilizatorii se află în mai multe DN-uri, separați-le cu punct și virgulă.';
$lang["loginfield"]='Câmp de autentificare.';
$lang["usersuffix"]='Sufix utilizator (un punct va fi adăugat în fața sufixului)';
$lang["groupfield"]='Câmp de grup.';
$lang["createusers"]='Creați utilizatori.';
$lang["fallbackusergroup"]='Grup de utilizatori de rezervă';
$lang["ldaprsgroupmapping"]='Asocierea grupurilor LDAP-ResourceSpace';
$lang["ldapvalue"]='Valoare LDAP';
$lang["rsgroup"]='Grupul ResourceSpace.';
$lang["addrow"]='Adăugați rândul.';
$lang["email_attribute"]='Atribut de utilizat pentru adresa de email.';
$lang["phone_attribute"]='Atribut de utilizat pentru numărul de telefon.';
$lang["simpleldap_telephone"]='Telefon.';
$lang["simpleldap_unknown"]='necunoscut';
$lang["simpleldap_update_group"]='Actualizare grup utilizator la fiecare autentificare. Dacă nu se utilizează grupuri AD pentru a determina accesul, setați aceasta la fals, astfel încât utilizatorii să poată fi promovați manual.';
$lang["simpleldappriority"]='Prioritate (un număr mai mare va avea prioritate)';
$lang["simpleldap_create_new_match_email"]='Verificare potrivire email: Verificați dacă adresa de email LDAP se potrivește cu adresa de email a unui cont RS existent și adoptați acel cont. Va funcționa chiar dacă opțiunea "Creați utilizatori" este dezactivată.';
$lang["simpleldap_allow_duplicate_email"]='Permiteți crearea de conturi noi dacă există conturi existente cu aceeași adresă de email? (aceasta este anulată dacă se setează potrivirea prin email mai sus și se găsește o potrivire)';
$lang["simpleldap_multiple_email_match_subject"]='ResourceSpace - Tentativă de autentificare cu e-mail în conflict.';
$lang["simpleldap_multiple_email_match_text"]='Un nou utilizator LDAP s-a autentificat, dar există deja mai mult de un cont cu aceeași adresă de email:';
$lang["simpleldap_notification_email"]='Adresă de notificare, de exemplu, dacă sunt înregistrate adrese de e-mail duplicate. Dacă este lăsat necompletat, nicio notificare nu va fi trimisă.';
$lang["simpleldap_duplicate_email_error"]='Există deja un cont existent cu aceeași adresă de email. Vă rugăm să contactați administratorul dumneavoastră.';
$lang["simpleldap_no_group_match_subject"]='ResourceSpace - utilizator nou fără asignare la un grup.';
$lang["simpleldap_no_group_match"]='Un nou utilizator s-a conectat, dar nu există nicio grupă ResourceSpace asociată cu nicio grupă de directorii din care face parte.';
$lang["simpleldap_usermemberof"]='Utilizatorul este membru al următoarelor grupuri de directoare: -';
$lang["simpleldap_test"]='Testarea configurației LDAP.';
$lang["simpleldap_testing"]='Testarea configurației LDAP.';
$lang["simpleldap_connection"]='Conexiune la serverul LDAP.';
$lang["simpleldap_bind"]='Conectare la serverul LDAP.';
$lang["simpleldap_username"]='Nume de utilizator/Nume distingativ al utilizatorului (DN)';
$lang["simpleldap_password"]='Parolă.';
$lang["simpleldap_test_auth"]='Autentificare de test.';
$lang["simpleldap_domain"]='Domeniu.';
$lang["simpleldap_displayname"]='Nume afișat.';
$lang["simpleldap_memberof"]='Membru al';
$lang["simpleldap_test_title"]='Test.';
$lang["simpleldap_result"]='Rezultat';
$lang["simpleldap_retrieve_user"]='Obțineți detalii despre utilizator.';
$lang["simpleldap_externsion_required"]='Modulul PHP LDAP trebuie să fie activat pentru ca acest plugin să funcționeze.';
$lang["simpleldap_usercomment"]='Creat de pluginul SimpleLDAP.';
$lang["simpleldap_usermatchcomment"]='Actualizat la utilizator LDAP de SimpleLDAP.';
$lang["origin_simpleldap"]='Pluginul SimpleLDAP';
$lang["simpleldap_LDAPTLS_REQCERT_never_label"]='Nu verificați FQDN-ul serverului cu CN-ul certificatului.';