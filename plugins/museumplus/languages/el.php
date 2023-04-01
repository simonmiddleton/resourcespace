<?php


$lang["museumplus_configuration"]='Ρύθμιση του MuseumPlus';
$lang["museumplus_top_menu_title"]='MuseumPlus: μη έγκυρες συσχετίσεις.';
$lang["museumplus_api_settings_header"]='Λεπτομέρειες API';
$lang["museumplus_host"]='Φιλοξενητής';
$lang["museumplus_host_api"]='Κεντρικός Φιλοξενητής API (μόνο για κλήσεις API, συνήθως ίδιος με τον παραπάνω)';
$lang["museumplus_application"]='Όνομα εφαρμογής.';
$lang["user"]='Χρήστης';
$lang["museumplus_api_user"]='Χρήστης';
$lang["password"]='Κωδικός πρόσβασης';
$lang["museumplus_api_pass"]='Κωδικός πρόσβασης';
$lang["museumplus_RS_settings_header"]='Ρυθμίσεις του ResourceSpace';
$lang["museumplus_mpid_field"]='Πεδίο μεταδεδομένων που χρησιμοποιείται για την αποθήκευση του αναγνωριστικού του MuseumPlus (MpID).';
$lang["museumplus_module_name_field"]='Πεδίο μεταδεδομένων που χρησιμοποιείται για να κρατήσει το όνομα των ενοτήτων για τις οποίες ισχύει το MpID. Εάν δεν έχει οριστεί, το πρόσθετο θα επιστρέψει στη διαμόρφωση της ενότητας "Αντικείμενο".';
$lang["museumplus_secondary_links_field"]='Πεδίο μεταδεδομένων που χρησιμοποιείται για την αποθήκευση δευτερεύοντων συνδέσμων προς άλλα modules. Το ResourceSpace θα δημιουργήσει ένα URL MuseumPlus για κάθε σύνδεσμο. Οι σύνδεσμοι θα έχουν ένα ειδικό συντακτικό μορφής: module_name:ID (π.χ. "Object:1234").';
$lang["museumplus_object_details_title"]='Λεπτομέρειες MuseumPlus.';
$lang["museumplus_script_header"]='Ρυθμίσεις σεναρίου.';
$lang["museumplus_last_run_date"]='<div class="Question">
    <label>
        <strong>Τελευταία εκτέλεση σεναρίου</strong>
    </label>
    <input name="script_last_ran" type="text" value="%script_last_ran" disabled style="width: 420px;">
</div>
<div class="clearerleft"></div>';
$lang["museumplus_enable_script"]='Ενεργοποίηση του script του MuseumPlus.';
$lang["museumplus_interval_run"]='Εκτέλεση script στο παρακάτω διάστημα (π.χ. +1 ημέρα, +2 εβδομάδες, δεκαπενθήμερο). Αφήστε κενό και θα εκτελεστεί κάθε φορά που τρέχει το cron_copy_hitcount.php.';
$lang["museumplus_log_directory"]='Κατάλογος για την αποθήκευση αρχείων καταγραφής σεναρίων. Εάν αυτό αφεθεί κενό ή είναι μη έγκυρο, τότε δεν θα γίνεται καμία καταγραφή.';
$lang["museumplus_integrity_check_field"]='Πεδίο έλεγχου ακεραιότητας';
$lang["museumplus_modules_configuration_header"]='Διαμόρφωση Ενοτήτων (Modules configuration)';
$lang["museumplus_module"]='Μονάδα (Monada)';
$lang["museumplus_add_new_module"]='Προσθήκη νέου module MuseumPlus.';
$lang["museumplus_mplus_field_name"]='Όνομα πεδίου MuseumPlus.';
$lang["museumplus_rs_field"]='Πεδίο ResourceSpace';
$lang["museumplus_view_in_museumplus"]='Προβολή στο MuseumPlus.';
$lang["museumplus_confirm_delete_module_config"]='Είστε σίγουροι ότι θέλετε να διαγράψετε αυτήν τη διαμόρφωση του μοντέλου; Αυτή η ενέργεια δεν μπορεί να αναιρεθεί!';
$lang["museumplus_module_setup"]='Ρύθμιση Ενότητας (Module setup)';
$lang["museumplus_module_name"]='Όνομα ενότητας του MuseumPlus.';
$lang["museumplus_mplus_id_field"]='Όνομα πεδίου αναγνωριστικού MuseumPlus.';
$lang["museumplus_mplus_id_field_helptxt"]='Αφήστε κενό για να χρησιμοποιήσετε το τεχνικό αναγνωριστικό \'__id\' (προεπιλογή)';
$lang["museumplus_rs_uid_field"]='Πεδίο UID του ResourceSpace.';
$lang["museumplus_applicable_resource_types"]='Εφαρμόσιμοι τύποι πόρων.';
$lang["museumplus_field_mappings"]='Αντιστοιχίσεις πεδίων MuseumPlus - ResourceSpace.';
$lang["museumplus_add_mapping"]='Προσθήκη αντιστοίχισης.';
$lang["museumplus_error_bad_conn_data"]='Μη έγκυρα δεδομένα σύνδεσης MuseumPlus.';
$lang["museumplus_error_unexpected_response"]='Λάβαμε μη αναμενόμενο κωδικό απόκρισης από το MuseumPlus - %code.';
$lang["museumplus_error_no_data_found"]='Δε βρέθηκαν δεδομένα στο MuseumPlus για αυτό το MpID - %mpid.';
$lang["museumplus_warning_script_not_completed"]='ΠΡΟΕΙΔΟΠΟΙΗΣΗ: Το σενάριο του MuseumPlus δεν έχει ολοκληρωθεί από τότε που εκτελέστηκε τελευταία φορά στις \'%script_last_ran\'.
Μπορείτε να αγνοήσετε αυτήν την προειδοποίηση μόνο αν στη συνέχεια λάβατε ειδοποίηση για μια επιτυχή ολοκλήρωση του σεναρίου.';
$lang["museumplus_error_script_failed"]='Το σενάριο του MuseumPlus απέτυχε να εκτελεστεί επειδή υπήρχε κλείδωμα διεργασίας. Αυτό υποδηλώνει ότι η προηγούμενη εκτέλεση δεν ολοκληρώθηκε. Εάν χρειάζεστε να απαλείψετε το κλείδωμα μετά από μια αποτυχημένη εκτέλεση, εκτελέστε το σενάριο ως εξής: php museumplus_script.php --clear-lock.';
$lang["museumplus_php_utility_not_found"]='Η επιλογή διαμόρφωσης $php_path ΠΡΕΠΕΙ να οριστεί ώστε η λειτουργία του προγραμματιστή εργασιών (cron) να εκτελεστεί με επιτυχία!';
$lang["museumplus_error_not_deleted_module_conf"]='Αδυναμία διαγραφής της ζητούμενης διαμόρφωσης του αρθρώματος.';
$lang["museumplus_error_unknown_type_saved_config"]='Το \'museumplus_modules_saved_config\' είναι άγνωστου τύπου!';
$lang["museumplus_error_invalid_association"]='Μη έγκυρη συσχέτιση ενοτήτων. Βεβαιωθείτε ότι έχετε εισαγάγει το σωστό ID Ενότητας και/ή Εγγραφής!';
$lang["museumplus_id_returns_multiple_records"]='Βρέθηκαν πολλαπλές εγγραφές - παρακαλώ εισαγάγετε το τεχνικό ID αντί αυτού.';
$lang["museumplus_error_module_no_field_maps"]='Αδυναμία συγχρονισμού δεδομένων από το MuseumPlus. Αιτία: το αντικείμενο \'%name\' δεν έχει διαμορφωθεί μεταφορά πεδίων.';