<?php


$lang["checkmail_configuration"]='Konfigurasi Cek Email';
$lang["checkmail_install_php_imap_extension"]='Langkah Pertama: Pasang ekstensi php imap.';
$lang["checkmail_cronhelp"]='Plugin ini memerlukan beberapa pengaturan khusus agar sistem dapat masuk ke akun email yang didedikasikan untuk menerima file yang dimaksudkan untuk diunggah.<br /><br />Pastikan bahwa IMAP diaktifkan pada akun tersebut. Jika Anda menggunakan akun Gmail, Anda dapat mengaktifkan IMAP di Pengaturan->POP/IMAP->Aktifkan IMAP<br /><br />
Pada pengaturan awal, Anda mungkin akan menemukan sangat membantu untuk menjalankan plugins/checkmail/pages/cron_check_email.php secara manual pada baris perintah untuk memahami bagaimana cara kerjanya.
Setelah Anda terhubung dengan benar dan memahami cara kerja skrip, Anda harus menyiapkan pekerjaan cron untuk menjalankannya setiap satu atau dua menit.<br />Ini akan memindai kotak surat dan membaca satu email yang belum terbaca per jalannya.<br /><br />
Contoh pekerjaan cron yang berjalan setiap dua menit:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Akun IMAP Anda terakhir diperiksa pada [lastcheck].';
$lang["checkmail_cronjobprob"]='Cronjob checkmail Anda mungkin tidak berjalan dengan baik, karena sudah lebih dari 5 menit sejak terakhir kali dijalankan.<br /><br />
Contoh cron job yang berjalan setiap menit:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Server Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Sure, the translation of "Email" in Bahasa Indonesia is "Surel".';
$lang["checkmail_password"]='Kata sandi.';
$lang["checkmail_extension_mapping"]='Jenis Sumber Daya melalui Pemetaan Ekstensi Berkas.';
$lang["checkmail_default_resource_type"]='Jenis Sumber Daya Bawaan.';
$lang["checkmail_extension_mapping_desc"]='Setelah pemilih Tipe Sumber Daya Default, terdapat satu input di bawah untuk setiap Tipe Sumber Daya Anda. <br />Untuk memaksa file yang diunggah dari jenis yang berbeda ke dalam Tipe Sumber Daya tertentu, tambahkan daftar pemisah koma dari ekstensi file (contoh: jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(dari allowed_extensions)';
$lang["checkmail_subject_field"]='Bidang Subjek';
$lang["checkmail_body_field"]='Kolom Tubuh.';
$lang["checkmail_purge"]='Menghapus e-mail setelah diunggah?';
$lang["checkmail_confirm"]='Kirim email konfirmasi?';
$lang["checkmail_users"]='Pengguna yang Diizinkan.';
$lang["checkmail_blocked_users_label"]='Pengguna yang Diblokir';
$lang["checkmail_default_access"]='Akses Default.';
$lang["checkmail_default_archive"]='Status Bawaan';
$lang["checkmail_html"]='Izinkan Konten HTML? (eksperimental, tidak disarankan)';
$lang["checkmail_mail_skipped"]='Dilewatkan e-mail.';
$lang["checkmail_allow_users_based_on_permission_label"]='Apakah pengguna harus diizinkan untuk mengunggah berdasarkan izin?';
$lang["addresourcesviaemail"]='Tambahkan melalui E-mail.';
$lang["uploadviaemail"]='Tambahkan melalui E-mail.';
$lang["uploadviaemail-intro"]='Untuk mengunggah melalui e-mail, lampirkan file Anda dan alamatkan e-mail ke <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Pastikan untuk mengirimkannya dari <b>[fromaddress]</b>, atau akan diabaikan.</p><p>Perhatikan bahwa apa pun yang ada di SUBYEK e-mail akan masuk ke dalam kolom [subjectfield] di %applicationname%.</p><p> Juga perhatikan bahwa apa pun yang ada di BADAN e-mail akan masuk ke dalam kolom [bodyfield] di %applicationname%.</p> <p>Berkas-berkas yang diunggah akan dikelompokkan menjadi sebuah koleksi. Sumber daya Anda akan bawaan ke tingkat Akses <b>\'[access]\'</b>, dan status Arsip <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Anda akan menerima e-mail konfirmasi ketika e-mail Anda berhasil diproses. Jika e-mail Anda dilewati secara program untuk alasan apa pun (seperti jika dikirim dari alamat yang salah), administrator akan diberitahu bahwa ada e-mail yang memerlukan perhatian.';
$lang["yourresourcehasbeenuploaded"]='Sumber daya Anda telah diunggah.';
$lang["yourresourceshavebeenuploaded"]='Sumber daya Anda telah diunggah.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), dengan ID [user-ref] dan e-mail [user-email] tidak diizinkan untuk mengunggah melalui e-mail (periksa izin "c" atau "d" atau pengguna yang diblokir di halaman pengaturan checkmail). Terekam pada: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Dibuat dari plugin Periksa Email.';