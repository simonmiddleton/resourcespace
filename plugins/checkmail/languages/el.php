<?php


$lang["checkmail_configuration"]='Ρύθμιση Ελέγχου Ηλεκτρονικού Ταχυδρομείου';
$lang["checkmail_install_php_imap_extension"]='Βήμα Πρώτο: Εγκαταστήστε την επέκταση php imap.';
$lang["checkmail_cronhelp"]='Αυτό το πρόσθετο απαιτεί μερικές ειδικές ρυθμίσεις για το σύστημα να συνδεθεί σε έναν λογαριασμό ηλεκτρονικού ταχυδρομείου που είναι αφιερωμένος στη λήψη αρχείων που προορίζονται για μεταφόρτωση.<br /><br />Βεβαιωθείτε ότι η υπηρεσία IMAP είναι ενεργοποιημένη στον λογαριασμό. Εάν χρησιμοποιείτε ένα λογαριασμό Gmail, μπορείτε να ενεργοποιήσετε το IMAP στις Ρυθμίσεις->POP/IMAP->Ενεργοποίηση IMAP<br /><br />
Κατά την αρχική ρύθμιση, μπορεί να σας βοηθήσει περισσότερο να εκτελέσετε το plugins/checkmail/pages/cron_check_email.php χειροκίνητα στη γραμμή εντολών για να κατανοήσετε πώς λειτουργεί.<br />Αφού συνδεθείτε σωστά και κατανοήσετε πώς λειτουργεί το σενάριο, πρέπει να ρυθμίσετε μια εργασία cron για να το εκτελείτε κάθε λεπτό ή δύο.<br />Θα σαρώνει το εισερχόμενο ταχυδρομείο και θα διαβάζει ένα αδιάβαστο μήνυμα ανά εκτέλεση.<br /><br />
Ένα παράδειγμα εργασίας cron που εκτελείται κάθε δύο λεπτά:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Ο λογαριασμός IMAP σας ελέγχθηκε τελευταία φορά στις [lastcheck].';
$lang["checkmail_cronjobprob"]='Ο κρονο-έλεγχος του ηλεκτρονικού ταχυδρομείου σας μπορεί να μην εκτελείται σωστά, επειδή έχουν περάσει περισσότερα από 5 λεπτά από την τελευταία εκτέλεσή του.<br /><br />
Ένα παράδειγμα κρον job που εκτελείται κάθε λεπτό:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Διακομιστής Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Ηλεκτρονικό Ταχυδρομείο (ΗΤΜ)';
$lang["checkmail_password"]='Κωδικός πρόσβασης';
$lang["checkmail_extension_mapping"]='Τύπος Πόρου μέσω Χαρτογράφησης Επέκτασης Αρχείου';
$lang["checkmail_default_resource_type"]='Προεπιλεγμένος Τύπος Πόρου';
$lang["checkmail_extension_mapping_desc"]='Μετά τον επιλογέα Προεπιλεγμένου Τύπου Πόρου, υπάρχει ένα πεδίο εισαγωγής κάτω από κάθε Τύπο Πόρου σας. <br /> Για να εξαναγκάσετε αρχεία που ανεβαίνουν διαφορετικού τύπου σε ένα συγκεκριμένο Τύπο Πόρου, προσθέστε λίστες διαχωρισμένες με κόμμα από επεκτάσεις αρχείων (π.χ. jpg, gif, png).';
$lang["checkmail_subject_field"]='Πεδίο Θέματος';
$lang["checkmail_body_field"]='Πεδίο Σώματος';
$lang["checkmail_purge"]='Να διαγραφούν τα e-mails μετά τη μεταφόρτωση;';
$lang["checkmail_confirm"]='Αποστολή e-mail επιβεβαίωσης;';
$lang["checkmail_users"]='Επιτρεπόμενοι Χρήστες.';
$lang["checkmail_blocked_users_label"]='Αποκλεισμένοι χρήστες.';
$lang["checkmail_default_access"]='Προεπιλεγμένη Πρόσβαση';
$lang["checkmail_default_archive"]='Προεπιλεγμένη Κατάσταση';
$lang["checkmail_html"]='Να επιτραπεί το περιεχόμενο HTML; (πειραματικό, δεν συνιστάται)';
$lang["checkmail_mail_skipped"]='Παραλειφθέν email.';
$lang["checkmail_allow_users_based_on_permission_label"]='Θα πρέπει να επιτρέπεται στους χρήστες να ανεβάζουν αρχεία βάσει των δικαιωμάτων που έχουν;';
$lang["addresourcesviaemail"]='Προσθήκη μέσω ηλεκτρονικού ταχυδρομείου.';
$lang["uploadviaemail"]='Προσθήκη μέσω ηλεκτρονικού ταχυδρομείου.';
$lang["uploadviaemail-intro"]='Για να ανεβάσετε μέσω email, συνημμένετε το ή τα αρχεία σας και απευθυνθείτε στο email <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Βεβαιωθείτε ότι το αποστέλλετε από το <b>[fromaddress]</b>, αλλιώς θα αγνοηθεί.</p><p>Σημειώστε ότι οτιδήποτε βρίσκεται στο ΘΕΜΑ του email θα μπει στο πεδίο [subjectfield] στο %applicationname%. </p><p> Επίσης, σημειώστε ότι οτιδήποτε βρίσκεται στο ΚΕΙΜΕΝΟ του email θα μπει στο πεδίο [bodyfield] στο %applicationname%. </p>  <p>Πολλαπλά αρχεία θα ομαδοποιηθούν σε μια συλλογή. Οι πόροι σας θα έχουν προεπιλεγμένο επίπεδο πρόσβασης <b>\'[access]\'</b>, και κατάσταση αρχειοθέτησης <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Θα λάβετε ένα e-mail επιβεβαίωσης όταν το e-mail σας επεξεργαστεί επιτυχώς. Εάν το e-mail σας παραλειφθεί προγραμματιστικά για οποιονδήποτε λόγο (όπως αν αποσταλεί από λανθασμένη διεύθυνση), ο διαχειριστής θα ενημερωθεί ότι υπάρχει ένα e-mail που απαιτεί προσοχή.';
$lang["yourresourcehasbeenuploaded"]='Ο πόρος σας έχει μεταφορτωθεί.';
$lang["yourresourceshavebeenuploaded"]='Οι πόροι σας έχουν μεταφορτωθεί.';
$lang["checkmail_not_allowed_error_template"]='Ο χρήστης [user-fullname] ([username]), με ID [user-ref] και ηλεκτρονική διεύθυνση [user-email] δεν επιτρέπεται να ανεβάζει μέσω ηλεκτρονικού ταχυδρομείου (ελέγξτε τα δικαιώματα "c" ή "d" ή τους αποκλεισμένους χρήστες στη σελίδα ρύθμισης ελέγχου ταχυδρομείου). Καταγράφηκε στις: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Δημιουργήθηκε από το πρόσθετο Ελέγχου Ηλεκτρονικού Ταχυδρομείου.';