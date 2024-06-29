<?php


$lang["simpleldap_ldaptype"]='Poskytovateľ adresára';
$lang["ldapserver"]='LDAP server';
$lang["ldap_encoding"]='Kodiranje podatkov prejeto iz strežnika LDAP (nastavljeno, če ni UTF-8 in podatki se ne prikažejo pravilno - npr. prikazno ime)';
$lang["domain"]='AD doména, ak je viacero, oddelte ich bodkočiarkou';
$lang["emailsuffix"]='Koncovka e-mailu - použije se, pokud nejsou k dispozici žádná data atributu e-mailu';
$lang["basedn"]='Základný DN. Ak sú používatelia v rôznych DN, oddelte ich bodkočiarkou';
$lang["loginfield"]='Prihlasovacie pole';
$lang["usersuffix"]='Pripone používateľa (predpona bude pridaná pred pripone)';
$lang["groupfield"]='Skupinové pole';
$lang["createusers"]='Vytvoriť používateľov';
$lang["fallbackusergroup"]='Náhradná skupina používateľov';
$lang["ldaprsgroupmapping"]='Mapovanie skupiny LDAP-ResourceSpace';
$lang["ldapvalue"]='Hodnota LDAP';
$lang["rsgroup"]='Skupina v ResourceSpace-u';
$lang["addrow"]='Pridať riadok';
$lang["email_attribute"]='Atribút na použitie pre emailovú adresu';
$lang["phone_attribute"]='Atribút na použitie pre telefónne číslo';
$lang["simpleldap_telephone"]='Telefón';
$lang["simpleldap_unknown"]='neznámy';
$lang["simpleldap_update_group"]='Aktualizovať skupinu používateľov pri každom prihlásení. Ak sa nepoužívajú skupiny AD na určenie prístupu, nastavte to na false, aby bolo možné používateľov manuálne povýšiť';
$lang["simpleldappriority"]='Priorita (vyššie číslo bude mať prednosť)';
$lang["simpleldap_create_new_match_email"]='Email-zadetek: Preveri, ali se e-poštni naslov LDAP ujema z obstoječim e-poštnim naslovom računa RS in sprejmi ta račun. Delovalo bo tudi, če je možnost "Ustvari uporabnike" onemogočena';
$lang["simpleldap_allow_duplicate_email"]='Povolit vytváranie nových účtov, ak existujú už účty s rovnakou e-mailovou adresou? (toto sa prekryje, ak je nastavené zhodovanie e-mailov a nájdená je jedna zhoda)';
$lang["simpleldap_multiple_email_match_subject"]='ResourceSpace - pokus o prihlásenie s konfliktným emailom';
$lang["simpleldap_multiple_email_match_text"]='Nov LDAP uporabnik se je prijavil, vendar že obstaja več kot en račun z istim e-poštnim naslovom:';
$lang["simpleldap_notification_email"]='Adresa upozornenia, napr. ak sú zaregistrované duplicitné e-mailové adresy. Ak je prázdna, nebude odoslaná žiadna.';
$lang["simpleldap_duplicate_email_error"]='Existuje už existujúci účet s rovnakou emailovou adresou. Prosím, kontaktujte svojho administrátora.';
$lang["simpleldap_no_group_match_subject"]='ResourceSpace - nový používateľ bez priradenia do skupiny';
$lang["simpleldap_no_group_match"]='Nový používateľ sa prihlásil, ale neexistuje žiadna skupina ResourceSpace mapovaná na žiadnu skupinu adresára, ku ktorej patrí.';
$lang["simpleldap_usermemberof"]='Používateľ je členom nasledujúcich skupín adresára: -';
$lang["simpleldap_test"]='Testovanie konfigurácie LDAP';
$lang["simpleldap_testing"]='Testovanie konfigurácie LDAP';
$lang["simpleldap_connection"]='Pripojenie k LDAP serveru';
$lang["simpleldap_bind"]='Pripojiť sa k LDAP serveru';
$lang["simpleldap_username"]='Používateľské meno/Používateľské DN';
$lang["simpleldap_password"]='Heslo';
$lang["simpleldap_test_auth"]='Overenie testu';
$lang["simpleldap_domain"]='Doména';
$lang["simpleldap_displayname"]='Zobraziť meno';
$lang["simpleldap_memberof"]='Člen (of a group or organization)';
$lang["simpleldap_test_title"]='Test';
$lang["simpleldap_result"]='Výsledok';
$lang["simpleldap_retrieve_user"]='Získať detaily používateľa';
$lang["simpleldap_externsion_required"]='Modul PHP LDAP musí byť povolený pre správne fungovanie tohto pluginu';
$lang["simpleldap_usercomment"]='Vytvorené pomocou doplnku SimpleLDAP.';
$lang["simpleldap_usermatchcomment"]='Aktualizované na LDAP používateľa pomocou SimpleLDAP.';
$lang["origin_simpleldap"]='Jednoduchý doplnok SimpleLDAP';
$lang["simpleldap_LDAPTLS_REQCERT_never_label"]='Ne preverjajte FQDN strežnika proti CN certifikatu';
$lang["port"]='Vrata';