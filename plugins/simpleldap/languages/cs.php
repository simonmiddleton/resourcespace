<?php


$lang["simpleldap_ldaptype"]='Poskytovatel adresáře';
$lang["ldapserver"]='LDAP server';
$lang["ldap_encoding"]='Kódování dat přijatých ze serveru LDAP (nastavte, pokud není UTF-8 a data se nezobrazují správně - např. zobrazované jméno)';
$lang["domain"]='Doména AD, pokud je více, oddělte středníky';
$lang["emailsuffix"]='Přípona e-mailu - použito, pokud nebyla nalezena žádná data atributu e-mailu';
$lang["port"]='Port';
$lang["basedn"]='Základní DN. Pokud jsou uživatelé ve více DN, oddělte je středníky';
$lang["loginfield"]='Přihlašovací pole';
$lang["usersuffix"]='Přípona uživatele (před příponu bude přidána tečka)';
$lang["groupfield"]='Skupinové pole';
$lang["createusers"]='Vytvořit uživatele';
$lang["fallbackusergroup"]='Záložní uživatelská skupina';
$lang["ldaprsgroupmapping"]='Mapování skupin LDAP-ResourceSpace';
$lang["ldapvalue"]='Hodnota LDAP';
$lang["rsgroup"]='Skupina ResourceSpace';
$lang["addrow"]='Přidat řádek';
$lang["email_attribute"]='Atribut pro použití e-mailové adresy';
$lang["phone_attribute"]='Atribut pro použití telefonního čísla';
$lang["simpleldap_telephone"]='Telefon';
$lang["simpleldap_unknown"]='neznámý';
$lang["simpleldap_update_group"]='Aktualizovat uživatelskou skupinu při každém přihlášení. Pokud nepoužíváte AD skupiny k určení přístupu, nastavte toto na false, aby uživatelé mohli být ručně povýšeni';
$lang["simpleldappriority"]='Priorita (vyšší číslo bude mít přednost)';
$lang["simpleldap_create_new_match_email"]='Shoda e-mailu: Zkontrolujte, zda e-mail LDAP odpovídá e-mailu existujícího účtu RS a převezměte tento účet. Bude fungovat i v případě, že je \'Vytváření uživatelů\' zakázáno';
$lang["simpleldap_allow_duplicate_email"]='Povolit vytvoření nových účtů, pokud již existují účty se stejnou e-mailovou adresou? (toto je přepsáno, pokud je výše nastaveno shodování e-mailů a nalezena jedna shoda)';
$lang["simpleldap_multiple_email_match_subject"]='ResourceSpace - konfliktní pokus o přihlášení e-mailem';
$lang["simpleldap_multiple_email_match_text"]='Nový uživatel LDAP se přihlásil, ale již existuje více než jeden účet se stejnou e-mailovou adresou:';
$lang["simpleldap_notification_email"]='Adresa pro oznámení např. pokud jsou registrovány duplicitní e-mailové adresy. Pokud je prázdné, žádné nebudou odeslány.';
$lang["simpleldap_duplicate_email_error"]='Existuje již účet se stejnou e-mailovou adresou. Kontaktujte prosím svého administrátora.';
$lang["simpleldap_no_group_match_subject"]='ResourceSpace - nový uživatel bez přiřazení do skupiny';
$lang["simpleldap_no_group_match"]='Nový uživatel se přihlásil, ale žádná skupina ResourceSpace není namapována na žádnou adresářovou skupinu, do které patří.';
$lang["simpleldap_usermemberof"]='Uživatel je členem následujících skupin adresáře: -';
$lang["simpleldap_test"]='Otestovat konfiguraci LDAP';
$lang["simpleldap_testing"]='Testování konfigurace LDAP';
$lang["simpleldap_connection"]='Připojení k LDAP serveru';
$lang["simpleldap_bind"]='Připojit k LDAP serveru';
$lang["simpleldap_username"]='Uživatelské jméno/Uživatelské DN';
$lang["simpleldap_password"]='Heslo';
$lang["simpleldap_test_auth"]='Testovat ověření';
$lang["simpleldap_domain"]='Doména';
$lang["simpleldap_displayname"]='Zobrazované jméno';
$lang["simpleldap_memberof"]='Člen';
$lang["simpleldap_test_title"]='Test';
$lang["simpleldap_result"]='Výsledek';
$lang["simpleldap_retrieve_user"]='Získat podrobnosti o uživateli';
$lang["simpleldap_externsion_required"]='Modul PHP LDAP musí být povolen, aby tento plugin fungoval';
$lang["simpleldap_usercomment"]='Vytvořeno pluginem SimpleLDAP.';
$lang["simpleldap_usermatchcomment"]='Aktualizováno na uživatele LDAP pomocí SimpleLDAP.';
$lang["origin_simpleldap"]='Plugin SimpleLDAP';
$lang["simpleldap_LDAPTLS_REQCERT_never_label"]='Nekontrolujte FQDN serveru proti CN certifikátu';