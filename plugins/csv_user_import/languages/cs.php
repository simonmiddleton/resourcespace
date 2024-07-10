<?php


$lang["csv_user_import_batch_user_import"]='Hromadný import uživatelů';
$lang["csv_user_import_import"]='Importovat';
$lang["csv_user_import"]='Import uživatelů z CSV';
$lang["csv_user_import_intro"]='Použijte tuto funkci k importu dávky uživatelů do ResourceSpace. Věnujte prosím zvláštní pozornost formátu vašeho CSV souboru a dodržujte níže uvedené standardy:';
$lang["csv_user_import_upload_file"]='Vyberte soubor';
$lang["csv_user_import_processing_file"]='ZPRACOVÁVÁNÍ SOUBORU...';
$lang["csv_user_import_error_found"]='Nalezeny chyby - přerušení';
$lang["csv_user_import_move_upload_file_failure"]='Došlo k chybě při přesunu nahraného souboru. Zkuste to prosím znovu nebo kontaktujte administrátory.';
$lang["csv_user_import_condition1"]='Ujistěte se, že soubor CSV je kódován pomocí <b>UTF-8</b>';
$lang["csv_user_import_condition2"]='Soubor CSV musí mít řádek záhlaví';
$lang["csv_user_import_condition3"]='Sloupce, které budou obsahovat hodnoty s <b>čárkami( , )</b>, ujistěte se, že je formátujete jako typ <b>text</b>, abyste nemuseli přidávat uvozovky (""). Při ukládání jako .csv soubor se ujistěte, že máte zaškrtnutou možnost citování buněk typu text';
$lang["csv_user_import_condition4"]='Povolené sloupce: *uživatelské jméno, *email, heslo, celé jméno, platnost účtu, komentáře, omezení IP, jazyk. Poznámka: povinná pole jsou označena *';
$lang["csv_user_import_condition5"]='Jazyk uživatele se vrátí zpět na ten, který je nastaven pomocí konfigurační volby "$defaultlanguage", pokud sloupec lang není nalezen nebo nemá hodnotu';