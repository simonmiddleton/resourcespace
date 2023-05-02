<?php


$lang["csv_user_import_batch_user_import"]='Import partii użytkowników.';
$lang["csv_user_import_import"]='Import - Importuj';
$lang["csv_user_import"]='Import użytkowników z pliku CSV.';
$lang["csv_user_import_intro"]='Użyj tej funkcji, aby zaimportować partię użytkowników do ResourceSpace. Proszę zwrócić szczególną uwagę na format pliku CSV i postępować zgodnie ze standardami poniżej:';
$lang["csv_user_import_upload_file"]='Wybierz plik.';
$lang["csv_user_import_processing_file"]='PRZETWARZANIE PLIKU...';
$lang["csv_user_import_error_found"]='Błąd(y) znaleziono - przerywanie.';
$lang["csv_user_import_move_upload_file_failure"]='Wystąpił błąd podczas przenoszenia przesłanego pliku. Spróbuj ponownie lub skontaktuj się z administratorem.';
$lang["csv_user_import_condition1"]='Upewnij się, że plik CSV jest zakodowany w <b>UTF-8</b>.';
$lang["csv_user_import_condition2"]='Plik CSV musi mieć wiersz nagłówka.';
$lang["csv_user_import_condition3"]='Kolumny, które będą zawierać wartości zawierające <b>przecinki (,)</b>, upewnij się, że sformatujesz je jako typ <b>tekstowy</b>, aby nie musieć dodawać cudzysłowów (""). Przy zapisywaniu pliku .csv, upewnij się, że zaznaczysz opcję cytowania komórek typu tekstowego.';
$lang["csv_user_import_condition4"]='Dozwolone kolumny: *nazwa użytkownika, *adres e-mail, hasło, pełna nazwa, wygaśnięcie konta, komentarze, ograniczenie adresów IP, język. Uwaga: pola obowiązkowe są oznaczone gwiazdką (*).';
$lang["csv_user_import_condition5"]='Język użytkownika zostanie automatycznie ustawiony na wartość domyślną określoną w opcji konfiguracyjnej "$defaultlanguage", jeśli kolumna "lang" nie zostanie znaleziona lub nie będzie miała wartości.';