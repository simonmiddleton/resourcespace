<?php


$lang["status4"]='Tidak dapat diubah.';
$lang["doi_info_link"]='pada <a target="_blank" href="https://id.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>.';
$lang["doi_info_metadata_schema"]='Pada registrasi DOI di DataCite.org dijelaskan dalam <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Dokumentasi Skema Metadata Datacite</a>.';
$lang["doi_info_mds_api"]='Pada DOI-API yang digunakan oleh plugin ini dijelaskan dalam <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Dokumentasi API Datacite</a>.';
$lang["doi_plugin_heading"]='Plugin ini membuat <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> untuk objek dan koleksi yang tidak dapat diubah sebelum mendaftarkannya di <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Informasi lebih lanjut.';
$lang["doi_setup_doi_prefix"]='Awalan untuk pembuatan DOI.';
$lang["doi_info_prefix"]='pada <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">awalan doi</a>.';
$lang["doi_setup_use_testmode"]='Gunakan <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">mode uji coba</a>';
$lang["doi_info_testmode"]='pada <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">mode uji coba</a>.';
$lang["doi_setup_use_testprefix"]='Gunakan awalan tes <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">(10.5072)</a> sebagai gantinya.';
$lang["doi_info_testprefix"]='pada <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">awalan uji coba</a>.';
$lang["doi_setup_publisher"]='Penerbit';
$lang["doi_info_publisher"]='pada kolom <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">penerbit</a>.';
$lang["doi_resource_conditions_title"]='Sebuah sumber daya harus memenuhi prasyarat berikut untuk memenuhi syarat untuk pendaftaran DOI:';
$lang["doi_resource_conditions"]='<li>Proyek Anda harus bersifat publik, yaitu memiliki area publik.</li>
<li>Sumber daya harus dapat diakses secara publik, yaitu memiliki akses yang diatur sebagai <strong>terbuka</strong>.</li>
<li>Sumber daya harus memiliki <strong>judul</strong>.</li>
<li>Harus ditandai {status}, yaitu memiliki status yang diatur menjadi <strong>{status}</strong>.</li>
<li>Kemudian, hanya seorang <strong>admin</strong> yang diizinkan untuk memulai proses registrasi.</li>';
$lang["doi_setup_general_config"]='Konfigurasi Umum';
$lang["doi_setup_pref_fields_header"]='Preferensi bidang pencarian untuk konstruksi metadata.';
$lang["doi_setup_username"]='Nama pengguna DataCite.';
$lang["doi_setup_password"]='Kata sandi DataCite.';
$lang["doi_pref_publicationYear_fields"]='Cari <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Tahun Publikasi</a> di:<br>(Jika tidak ada nilai yang ditemukan, tahun pendaftaran akan digunakan.)';
$lang["doi_pref_creator_fields"]='Cari <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Pembuat</a> di:';
$lang["doi_pref_title_fields"]='Cari <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Judul</a> di:';
$lang["doi_setup_default"]='Jika tidak ada nilai yang ditemukan, gunakan <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">kode standar</a>:';
$lang["doi_setup_test_plugin"]='Uji plugin..';
$lang["doi_setup_test_succeeded"]='Berhasil diuji!';
$lang["doi_setup_test_failed"]='Gagal uji coba!';
$lang["doi_alert_text"]='Perhatian! Setelah DOI dikirim ke DataCite, pendaftaran tidak dapat dibatalkan.';
$lang["doi_title_compulsory"]='Harap tetapkan judul sebelum melanjutkan registrasi DOI.';
$lang["doi_register"]='Mendaftar';
$lang["doi_cancel"]='Membatalkan.';
$lang["doi_sure"]='Perhatian! Setelah DOI dikirim ke DataCite, pendaftaran tidak dapat dibatalkan. Informasi yang sudah terdaftar di DataCite Metadata Store mungkin akan ditimpa.';
$lang["doi_already_set"]='sudah diatur';
$lang["doi_not_yet_set"]='belum diatur';
$lang["doi_already_registered"]='sudah terdaftar';
$lang["doi_not_yet_registered"]='belum terdaftar';
$lang["doi_successfully_registered"]='berhasil terdaftar';
$lang["doi_successfully_registered_pl"]='Sumber daya telah berhasil terdaftar.';
$lang["doi_not_successfully_registered"]='Tidak dapat terdaftar dengan benar.';
$lang["doi_not_successfully_registered_pl"]='Tidak dapat terdaftar dengan benar.';
$lang["doi_reload"]='Muat ulang.';
$lang["doi_successfully_set"]='telah diatur.';
$lang["doi_not_successfully_set"]='Belum diatur.';
$lang["doi_sum_of"]='dari';
$lang["doi_sum_already_reg"]='Sumber daya sudah memiliki DOI.';
$lang["doi_sum_not_yet_archived"]='sumber daya tidak ditandai';
$lang["doi_sum_not_yet_archived_2"]='Namun aksesnya belum dibuka.';
$lang["doi_sum_ready_for_reg"]='Sumber daya sudah siap untuk didaftarkan.';
$lang["doi_sum_no_title"]='Sumber daya masih perlu judul. Menggunakan:';
$lang["doi_sum_no_title_2"]='Sebagai judul.';
$lang["doi_register_all"]='Mendaftarkan DOI untuk semua sumber daya dalam koleksi ini.';
$lang["doi_sure_register_resource"]='Lanjutkan mendaftarkan x sumber daya?';
$lang["doi_hide_meta"]='Sembunyikan metadata DOI.';
$lang["doi_fetched_xml_from_MDS"]='Metadata XMl saat ini berhasil diambil dari toko metadata DataCite.';
$lang["doi_show_meta"]='Tampilkan metadata DOI.';