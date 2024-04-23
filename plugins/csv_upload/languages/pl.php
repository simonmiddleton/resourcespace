<?php


$lang["csv_upload_nav_link"]='Przesyłanie pliku CSV';
$lang["csv_upload_intro"]='Ten plugin umożliwia tworzenie lub aktualizowanie zasobów poprzez przesyłanie pliku CSV. Format pliku CSV jest ważny';
$lang["csv_upload_condition1"]='Upewnij się, że plik CSV jest zakodowany w <b>UTF-8 bez BOM</b>.';
$lang["csv_upload_condition2"]='CSV musi mieć wiersz nagłówka';
$lang["csv_upload_condition3"]='Aby móc później przesłać pliki zasobów za pomocą funkcji wsadowej zamiany, powinna istnieć kolumna o nazwie "Oryginalna nazwa pliku", a każdy plik powinien mieć unikalną nazwę pliku';
$lang["csv_upload_condition4"]='Wszystkie obowiązkowe pola dla nowo utworzonych zasobów muszą być obecne w pliku CSV';
$lang["csv_upload_condition5"]='Dla kolumn, które zawierają wartości z <b>przecinkami (,)</b>, upewnij się, że formatujesz je jako typ <b>tekstowy</b>, aby nie musieć dodawać cudzysłowów (""). Przy zapisywaniu pliku csv, upewnij się, że zaznaczasz opcję cytowania komórek typu tekstowego.';
$lang["csv_upload_condition6"]='Możesz pobrać przykładowy plik CSV, klikając na <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Aby zaktualizować istniejące dane zasobu, możesz pobrać plik CSV z istniejącymi metadanymi, klikając opcję "Eksport CSV - metadane" z menu działań kolekcji lub wyników wyszukiwania';
$lang["csv_upload_condition8"]='Możesz ponownie użyć wcześniej skonfigurowanego pliku mapowania CSV, klikając na "Prześlij plik konfiguracji CSV"';
$lang["csv_upload_error_no_permission"]='Nie masz odpowiednich uprawnień do przesyłania pliku CSV';
$lang["check_line_count"]='Znaleziono co najmniej dwa wiersze w pliku CSV';
$lang["csv_upload_file"]='Wybierz plik CSV';
$lang["csv_upload_default"]='Domyślny';
$lang["csv_upload_error_no_header"]='Nie znaleziono wiersza nagłówka w pliku';
$lang["csv_upload_update_existing"]='Zaktualizować istniejące zasoby? Jeśli to pole jest odznaczone, nowe zasoby zostaną utworzone na podstawie danych CSV';
$lang["csv_upload_update_existing_collection"]='Tylko zaktualizuj zasoby w określonej kolekcji?';
$lang["csv_upload_process"]='Proces';
$lang["csv_upload_add_to_collection"]='Dodać nowo utworzone zasoby do bieżącej kolekcji?';
$lang["csv_upload_step1"]='Krok 1 - Wybierz plik';
$lang["csv_upload_step2"]='Krok 2 - Domyślne opcje zasobów';
$lang["csv_upload_step3"]='Krok 3 - Przypisz kolumny do pól metadanych';
$lang["csv_upload_step4"]='Krok 4 - Sprawdzanie danych CSV';
$lang["csv_upload_step5"]='Krok 5 - Przetwarzanie pliku CSV';
$lang["csv_upload_update_existing_title"]='Zaktualizuj istniejące zasoby';
$lang["csv_upload_update_existing_notes"]='Wybierz wymagane opcje, aby zaktualizować istniejące zasoby';
$lang["csv_upload_create_new_title"]='Utwórz nowe zasoby';
$lang["csv_upload_create_new_notes"]='Wybierz wymagane opcje, aby utworzyć nowe zasoby';
$lang["csv_upload_map_fields_notes"]='Dopasuj kolumny w pliku CSV do wymaganych pól metadanych. Kliknięcie przycisku "Dalej" sprawdzi plik CSV bez zmiany danych';
$lang["csv_upload_map_fields_auto_notes"]='Pola metadanych zostały wcześniej wybrane na podstawie nazw lub tytułów, ale proszę sprawdzić, czy są one poprawne';
$lang["csv_upload_workflow_column"]='Wybierz kolumnę zawierającą identyfikator stanu przepływu pracy';
$lang["csv_upload_workflow_default"]='Domyślny stan przepływu pracy, jeśli nie wybrano kolumny lub nie znaleziono poprawnego stanu w kolumnie';
$lang["csv_upload_access_column"]='Wybierz kolumnę zawierającą poziom dostępu (0=Otwarty, 1=Ograniczony, 2=Poufny)';
$lang["csv_upload_access_default"]='Domyślny poziom dostępu, jeśli nie zostanie wybrana żadna kolumna lub jeśli nie zostanie znaleziony żaden prawidłowy dostęp w kolumnie';
$lang["csv_upload_resource_type_column"]='Wybierz kolumnę zawierającą identyfikator typu zasobu';
$lang["csv_upload_resource_type_default"]='Domyślny typ zasobu, jeśli nie wybrano kolumny lub jeśli w kolumnie nie znaleziono poprawnego typu';
$lang["csv_upload_resource_match_column"]='Wybierz kolumnę zawierającą identyfikator zasobu';
$lang["csv_upload_match_type"]='Dopasować zasób na podstawie identyfikatora zasobu lub wartości pola metadanych?';
$lang["csv_upload_multiple_match_action"]='Akcja do podjęcia, jeśli znaleziono wiele zasobów pasujących do kryteriów wyszukiwania';
$lang["csv_upload_validation_notes"]='Sprawdź poniższe komunikaty walidacyjne przed kontynuacją. Kliknij Przetwórz, aby zatwierdzić zmiany';
$lang["csv_upload_upload_another"]='Prześlij kolejny plik CSV';
$lang["csv_upload_mapping config"]='Ustawienia mapowania kolumn CSV';
$lang["csv_upload_download_config"]='Pobierz ustawienia mapowania CSV jako plik';
$lang["csv_upload_upload_config"]='Prześlij plik mapowania CSV';
$lang["csv_upload_upload_config_question"]='Prześlij plik mapowania CSV? Użyj tego, jeśli wcześniej przesłałeś podobny plik CSV i zapisałeś konfigurację';
$lang["csv_upload_upload_config_set"]='Konfiguracja zestawu CSV';
$lang["csv_upload_upload_config_clear"]='Wyczyść konfigurację mapowania CSV';
$lang["csv_upload_mapping_ignore"]='NIE UŻYWAJ';
$lang["csv_upload_mapping_header"]='Nagłówek kolumny';
$lang["csv_upload_mapping_csv_data"]='Przykładowe dane z pliku CSV';
$lang["csv_upload_using_config"]='Używanie istniejącej konfiguracji CSV';
$lang["csv_upload_process_offline"]='Przetworzyć plik CSV w trybie offline? Powinno to być używane dla dużych plików CSV. Otrzymasz powiadomienie za pośrednictwem wiadomości ResourceSpace, gdy przetwarzanie zostanie zakończone';
$lang["csv_upload_oj_created"]='Utworzono zadanie przesyłania pliku CSV o identyfikatorze zadania # %%JOBREF%%. <br/>Otrzymasz wiadomość systemową ResourceSpace po zakończeniu zadania';
$lang["csv_upload_oj_complete"]='Zadanie przesyłania pliku CSV zostało zakończone. Kliknij link, aby wyświetlić pełny plik dziennika';
$lang["csv_upload_oj_failed"]='Niepowodzenie zadania przesyłania pliku CSV';
$lang["csv_upload_processing_x_meta_columns"]='Przetwarzanie %count kolumn metadanych';
$lang["csv_upload_processing_complete"]='Przetwarzanie zakończone o %%TIME%% (%%HOURS%% godzin, %%MINUTES%% minut, %%SECONDS%% sekund)';
$lang["csv_upload_error_in_progress"]='Przetwarzanie przerwane - ten plik CSV jest już przetwarzany';
$lang["csv_upload_error_file_missing"]='Błąd - brak pliku CSV: %%FILE%%';
$lang["csv_upload_full_messages_link"]='Wyświetlanie tylko pierwszych 1000 linii, aby pobrać pełny plik dziennika, kliknij <a href=\'%%LOG_URL%%\' target=\'_blank\'>tutaj</a>';
$lang["csv_upload_ignore_errors"]='Zignoruj błędy i przetwórz plik mimo wszystko';
$lang["csv_upload_process_offline_quick"]='Pominąć walidację i przetworzyć plik CSV offline? Powinno to być używane tylko dla dużych plików CSV, gdy testowanie na mniejszych plikach zostało zakończone. Otrzymasz powiadomienie za pośrednictwem wiadomości ResourceSpace, gdy przesyłanie zostanie zakończone';
$lang["csv_upload_force_offline"]='Ten duży plik CSV może wymagać długiego czasu przetwarzania, więc zostanie uruchomiony w trybie offline. Otrzymasz powiadomienie za pośrednictwem wiadomości w ResourceSpace, gdy przetwarzanie zostanie zakończone';
$lang["csv_upload_recommend_offline"]='Ten duży plik CSV może wymagać bardzo długiego czasu przetwarzania. Zaleca się włączenie zadań offline, jeśli chcesz przetwarzać duże pliki CSV';
$lang["csv_upload_createdfromcsvupload"]='Utworzono za pomocą wtyczki CSV Upload';