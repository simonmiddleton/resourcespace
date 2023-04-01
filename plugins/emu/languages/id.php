<?php


$lang["emu_configuration"]='Konfigurasi EMu.';
$lang["emu_api_settings"]='Pengaturan server API.';
$lang["emu_api_server"]='Alamat server (misalnya http://[alamat.server])';
$lang["emu_api_server_port"]='Port server.';
$lang["emu_resource_types"]='Pilih jenis sumber daya yang terhubung dengan EMu.';
$lang["emu_email_notify"]='Alamat e-mail yang akan digunakan oleh skrip untuk mengirimkan pemberitahuan. Biarkan kosong untuk menggunakan alamat pemberitahuan sistem secara default.';
$lang["emu_script_failure_notify_days"]='Jumlah hari setelahnya untuk menampilkan peringatan dan mengirim e-mail jika skrip belum selesai.';
$lang["emu_script_header"]='Aktifkan skrip yang akan secara otomatis memperbarui data EMu setiap kali ResourceSpace menjalankan tugas terjadwalnya (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>Terakhir kali skrip dijalankan</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Mode skrip.';
$lang["emu_script_mode_option_1"]='Impor metadata dari EMu.';
$lang["emu_script_mode_option_2"]='Tarik semua catatan EMu dan pertahankan sinkronisasi antara RS dan EMu.';
$lang["emu_enable_script"]='Aktifkan skrip EMu.';
$lang["emu_interval_run"]='Jalankan skrip pada interval berikut (misalnya +1 hari, +2 minggu, dua minggu). Biarkan kosong dan skrip akan dijalankan setiap kali cron_copy_hitcount.php dijalankan.';
$lang["emu_log_directory"]='Direktori untuk menyimpan log skrip. Jika ini dikosongkan atau tidak valid, maka tidak akan ada pencatatan log.';
$lang["emu_created_by_script_field"]='Bidang metadata yang digunakan untuk menyimpan apakah sumber daya telah dibuat oleh skrip EMu.';
$lang["emu_settings_header"]='Pengaturan EMu.';
$lang["emu_irn_field"]='Bidang metadata yang digunakan untuk menyimpan pengenal EMu (IRN)';
$lang["emu_search_criteria"]='Kriteria pencarian untuk sinkronisasi EMu dengan ResourceSpace.';
$lang["emu_rs_mappings_header"]='Aturan pemetaan EMu - ResourceSpace.';
$lang["emu_module"]='Modul EMu.';
$lang["emu_column_name"]='Kolom modul EMu.';
$lang["emu_rs_field"]='Kolom ResourceSpace.';
$lang["emu_add_mapping"]='Tambahkan pemetaan.';
$lang["emu_upload_emu_field_label"]='IRN EMu adalah singkatan dari "Internal Registration Number" pada perangkat lunak manajemen koleksi EMu. IRN digunakan untuk mengidentifikasi setiap objek dalam koleksi dan memastikan bahwa setiap objek memiliki nomor unik yang terdaftar dalam sistem.';
$lang["emu_confirm_upload_nodata"]='Silakan centang kotak untuk mengkonfirmasi bahwa Anda ingin melanjutkan dengan pengunggahan.';
$lang["emu_test_script_title"]='Uji/ Jalankan skrip';
$lang["emu_run_script"]='Proses';
$lang["emu_script_problem"]='PERINGATAN - skrip EMu tidak berhasil diselesaikan dalam %days% hari terakhir. Waktu terakhir dijalankan:';
$lang["emu_no_resource"]='Tidak ada ID sumber daya yang ditentukan!';
$lang["emu_upload_nodata"]='Tidak ditemukan data EMu untuk IRN ini:';
$lang["emu_nodata_returned"]='Tidak ditemukan data EMu untuk IRN yang ditentukan.';
$lang["emu_createdfromemu"]='Dibuat dari plugin EMU.';