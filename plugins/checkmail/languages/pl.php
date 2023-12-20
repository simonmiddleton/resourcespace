<?php


$lang["checkmail_configuration"]='Konfiguracja poczty elektronicznej.';
$lang["checkmail_install_php_imap_extension"]='Krok pierwszy: Zainstaluj rozszerzenie php imap.';
$lang["checkmail_cronhelp"]='Ten plugin wymaga specjalnej konfiguracji, aby system mógł zalogować się do konta e-mailowego dedykowanego do odbierania plików przeznaczonych do przesłania.<br /><br />Upewnij się, że IMAP jest włączone na koncie. Jeśli korzystasz z konta Gmail, włącz IMAP w Ustawienia->POP/IMAP->Włącz IMAP<br /><br />
Podczas początkowej konfiguracji, najbardziej pomocne może być ręczne uruchomienie plugins/checkmail/pages/cron_check_email.php z wiersza poleceń, aby zrozumieć, jak działa.<br />Gdy już poprawnie się połączysz i zrozumiesz, jak działa skrypt, musisz skonfigurować zadanie cron, aby uruchamiać go co minutę lub dwie.<br />Skanuje on skrzynkę pocztową i odczytuje jedną nieprzeczytaną wiadomość na jedno uruchomienie.<br /><br />
Przykładowe zadanie cron, które uruchamia się co dwie minuty:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Twoje konto IMAP zostało ostatnio sprawdzone w dniu [lastcheck].';
$lang["checkmail_cronjobprob"]='Twój cronjob checkmail może nie działać poprawnie, ponieważ minęło więcej niż 5 minut od ostatniego uruchomienia.<br /><br />
Przykładowy cron job, który uruchamia się co minutę:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Serwer Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Email (poczta elektroniczna)';
$lang["checkmail_password"]='Hasło';
$lang["checkmail_extension_mapping"]='Typ zasobu poprzez mapowanie rozszerzeń plików.';
$lang["checkmail_default_resource_type"]='Domyślny typ zasobu.';
$lang["checkmail_extension_mapping_desc"]='Po selektorze Domyślnego typu zasobu znajduje się jedno pole wejściowe poniżej dla każdego z typów zasobów. <br />Aby wymusić przesłanie plików różnych typów do określonego typu zasobu, dodaj oddzielone przecinkami listy rozszerzeń plików (np. jpg, gif, png).';
$lang["checkmail_subject_field"]='Pole tematu';
$lang["checkmail_body_field"]='Pole treści.';
$lang["checkmail_purge"]='Usuń e-maile po przesłaniu?';
$lang["checkmail_confirm"]='Wysłać e-maile potwierdzające?';
$lang["checkmail_users"]='Dozwoleni użytkownicy.';
$lang["checkmail_blocked_users_label"]='Zablokowani użytkownicy.';
$lang["checkmail_default_access"]='Domyślny dostęp.';
$lang["checkmail_default_archive"]='Domyślny status.';
$lang["checkmail_html"]='Zezwolić na zawartość HTML? (eksperymentalne, niezalecane)';
$lang["checkmail_mail_skipped"]='Pominięty e-mail.';
$lang["checkmail_allow_users_based_on_permission_label"]='Czy użytkownikom powinno być pozwolone na przesyłanie plików na podstawie uprawnień?';
$lang["addresourcesviaemail"]='Dodaj za pośrednictwem e-maila.';
$lang["uploadviaemail"]='Dodaj za pośrednictwem e-maila.';
$lang["uploadviaemail-intro"]='Aby przesłać pliki pocztą e-mail, załącz je do wiadomości i wyślij na adres <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Upewnij się, że wysyłasz z <b>[fromaddress]</b>, w przeciwnym razie zostanie zignorowane.</p><p>Zwróć uwagę, że cokolwiek znajduje się w TEMACIE wiadomości e-mail zostanie umieszczone w polu [subjectfield] w %applicationname%. </p><p> Również cokolwiek znajduje się w TREŚCI wiadomości e-mail zostanie umieszczone w polu [bodyfield] w %applicationname%. </p>  <p>Wiele plików zostanie zgrupowanych w kolekcję. Twoje zasoby będą domyślnie miały poziom dostępu <b>\'[access]\'</b> i status archiwum <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Otrzymasz potwierdzenie e-mailowe, gdy Twój e-mail zostanie pomyślnie przetworzony. Jeśli Twój e-mail zostanie pominięty z powodu jakiejkolwiek przyczyny programowej (np. jeśli zostanie wysłany z niewłaściwego adresu), administrator zostanie powiadomiony, że istnieje e-mail wymagający uwagi.';
$lang["yourresourcehasbeenuploaded"]='Twój zasób został przesłany.';
$lang["yourresourceshavebeenuploaded"]='Twoje zasoby zostały przesłane.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), o ID [user-ref] i adresie e-mail [user-email], nie ma uprawnień do przesyłania plików za pomocą poczty e-mail (sprawdź uprawnienia "c" lub "d" lub zablokowanych użytkowników na stronie konfiguracji poczty e-mail). Zapisano w: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Utworzono z wtyczki Sprawdź pocztę.';