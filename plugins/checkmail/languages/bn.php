<?php


$lang["checkmail_configuration"]='চেকমেইল কনফিগারেশন';
$lang["checkmail_install_php_imap_extension"]='ধাপ এক: php imap এক্সটেনশন ইনস্টল করুন।';
$lang["checkmail_cronhelp"]='এই প্লাগইনটির জন্য সিস্টেমকে একটি ই-মেইল অ্যাকাউন্টে লগ ইন করার জন্য কিছু বিশেষ সেটআপ প্রয়োজন, যা আপলোডের জন্য ফাইল গ্রহণের জন্য নিবেদিত।<br /><br />অ্যাকাউন্টে IMAP সক্রিয় আছে তা নিশ্চিত করুন। আপনি যদি একটি Gmail অ্যাকাউন্ট ব্যবহার করেন তবে সেটিংস->POP/IMAP->IMAP সক্রিয় করুন এ যান<br /><br />প্রাথমিক সেটআপে, আপনি সম্ভবত কমান্ড লাইনে plugins/checkmail/pages/cron_check_email.php ম্যানুয়ালি চালিয়ে এটি কীভাবে কাজ করে তা বোঝার জন্য সবচেয়ে সহায়ক পাবেন। সঠিকভাবে সংযোগ স্থাপন করার পরে এবং স্ক্রিপ্টটি কীভাবে কাজ করে তা বোঝার পরে, আপনাকে এটি প্রতি এক বা দুই মিনিটে চালানোর জন্য একটি ক্রন কাজ সেট আপ করতে হবে।<br />এটি মেইলবক্স স্ক্যান করবে এবং প্রতি রান-এ একটি অপঠিত ই-মেইল পড়বে।<br /><br />প্রতি দুই মিনিটে চালানো একটি ক্রন কাজের উদাহরণ:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='আপনার IMAP অ্যাকাউন্ট সর্বশেষ [lastcheck] তারিখে পরীক্ষা করা হয়েছিল।';
$lang["checkmail_cronjobprob"]='আপনার চেকমেইল ক্রনজবটি সঠিকভাবে চলতে নাও পারে, কারণ এটি শেষবার চালানোর পর ৫ মিনিটের বেশি সময় পেরিয়ে গেছে।<br /><br />
প্রতি মিনিটে চলা একটি উদাহরণ ক্রনজব:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='ইমাপ সার্ভার<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='ইমেইল';
$lang["checkmail_password"]='পাসওয়ার্ড';
$lang["checkmail_extension_mapping"]='ফাইল এক্সটেনশনের মাধ্যমে রিসোর্স টাইপ ম্যাপিং';
$lang["checkmail_default_resource_type"]='ডিফল্ট রিসোর্স প্রকার';
$lang["checkmail_extension_mapping_desc"]='ডিফল্ট রিসোর্স টাইপ নির্বাচকের পরে, আপনার প্রতিটি রিসোর্স টাইপের জন্য নিচে একটি ইনপুট রয়েছে। <br />বিভিন্ন ধরনের আপলোড করা ফাইলকে একটি নির্দিষ্ট রিসোর্স টাইপে বাধ্য করতে, ফাইল এক্সটেনশনের কমা দ্বারা পৃথক তালিকা যোগ করুন (যেমন jpg,gif,png)।';
$lang["checkmail_resource_type_population"]='<br />(অনুমোদিত_এক্সটেনশন থেকে)';
$lang["checkmail_subject_field"]='বিষয় ক্ষেত্র';
$lang["checkmail_body_field"]='বডি ফিল্ড';
$lang["checkmail_purge"]='আপলোডের পর ই-মেইল মুছে ফেলবেন?';
$lang["checkmail_confirm"]='নিশ্চিতকরণ ই-মেইল পাঠাবেন?';
$lang["checkmail_users"]='অনুমোদিত ব্যবহারকারীরা';
$lang["checkmail_blocked_users_label"]='ব্লক করা ব্যবহারকারীরা';
$lang["checkmail_default_access"]='ডিফল্ট অ্যাক্সেস';
$lang["checkmail_default_archive"]='ডিফল্ট অবস্থা';
$lang["checkmail_html"]='এইচটিএমএল কন্টেন্ট অনুমোদন করবেন? (পরীক্ষামূলক, সুপারিশকৃত নয়)';
$lang["checkmail_mail_skipped"]='এড়ানো ই-মেইল';
$lang["checkmail_allow_users_based_on_permission_label"]='ব্যবহারকারীদের অনুমতির ভিত্তিতে আপলোড করার অনুমতি দেওয়া উচিত কি?';
$lang["addresourcesviaemail"]='ই-মেইল এর মাধ্যমে যোগ করুন';
$lang["uploadviaemail"]='ই-মেইল এর মাধ্যমে যোগ করুন';
$lang["uploadviaemail-intro"]='ই-মেইলের মাধ্যমে আপলোড করতে, আপনার ফাইল(গুলি) সংযুক্ত করুন এবং ই-মেইলটি <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> ঠিকানায় পাঠান।</p> <p>এটি অবশ্যই <b>[fromaddress]</b> থেকে পাঠাতে হবে, অন্যথায় এটি উপেক্ষা করা হবে।</p><p>মনে রাখবেন যে ই-মেইলের বিষয় (SUBJECT) এ যা কিছু থাকবে তা [applicationname]-এর [subjectfield] ক্ষেত্রে যাবে। </p><p> এছাড়াও মনে রাখবেন যে ই-মেইলের বডি (BODY) তে যা কিছু থাকবে তা [applicationname]-এর [bodyfield] ক্ষেত্রে যাবে। </p> <p>একাধিক ফাইল একটি সংগ্রহে (collection) গোষ্ঠীভুক্ত হবে। আপনার রিসোর্সগুলির ডিফল্ট অ্যাক্সেস স্তর হবে <b>\'[access]\'</b>, এবং আর্কাইভ স্ট্যাটাস হবে <b>\'[archive]\'</b>।</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='আপনার ই-মেইল সফলভাবে প্রক্রিয়াকৃত হলে আপনি একটি নিশ্চিতকরণ ই-মেইল পাবেন। যদি কোনো কারণে আপনার ই-মেইল প্রোগ্রাম্যাটিকভাবে এড়িয়ে যাওয়া হয় (যেমন এটি ভুল ঠিকানা থেকে পাঠানো হলে), তাহলে প্রশাসককে জানানো হবে যে একটি ই-মেইল মনোযোগ প্রয়োজন।';
$lang["yourresourcehasbeenuploaded"]='আপনার রিসোর্স আপলোড করা হয়েছে';
$lang["yourresourceshavebeenuploaded"]='আপনার রিসোর্সগুলি আপলোড করা হয়েছে';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), যার আইডি [user-ref] এবং ই-মেইল [user-email], ই-মেইলের মাধ্যমে আপলোড করার অনুমতি নেই (অনুমতিগুলি "c" বা "d" অথবা চেকমেইল সেটআপ পৃষ্ঠায় ব্লক করা ব্যবহারকারীদের পরীক্ষা করুন)। রেকর্ড করা হয়েছে: [datetime]।';
$lang["checkmail_createdfromcheckmail"]='চেক মেইল প্লাগইন থেকে তৈরি করা হয়েছে';