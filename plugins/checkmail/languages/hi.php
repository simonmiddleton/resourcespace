<?php


$lang["checkmail_configuration"]='मेल कॉन्फ़िगरेशन जांचें';
$lang["checkmail_install_php_imap_extension"]='चरण एक: php imap एक्सटेंशन स्थापित करें।';
$lang["checkmail_cronhelp"]='इस प्लगइन के लिए सिस्टम को एक ई-मेल खाते में लॉग इन करने के लिए कुछ विशेष सेटअप की आवश्यकता होती है, जो अपलोड के लिए फाइलें प्राप्त करने के लिए समर्पित है।<br /><br />सुनिश्चित करें कि IMAP खाते पर सक्षम है। यदि आप एक Gmail खाता उपयोग कर रहे हैं, तो आप IMAP को Settings->POP/IMAP->Enable IMAP में सक्षम कर सकते हैं।<br /><br />
प्रारंभिक सेटअप पर, आप इसे समझने के लिए कमांड लाइन पर plugins/checkmail/pages/cron_check_email.php को मैन्युअल रूप से चलाना सबसे सहायक पा सकते हैं कि यह कैसे काम करता है।
एक बार जब आप सही तरीके से कनेक्ट हो रहे हों और स्क्रिप्ट कैसे काम करती है, इसे समझ लें, तो आपको इसे हर एक या दो मिनट में चलाने के लिए एक क्रोन जॉब सेट अप करना होगा।<br />यह मेलबॉक्स को स्कैन करेगा और प्रति रन एक अपठित ई-मेल पढ़ेगा।<br /><br />
हर दो मिनट में चलने वाले क्रोन जॉब का एक उदाहरण:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='आपके IMAP खाते की अंतिम जाँच [lastcheck] को की गई थी।';
$lang["checkmail_cronjobprob"]='आपका चेकमेल क्रोनजॉब सही से नहीं चल रहा हो सकता है, क्योंकि इसे चले हुए 5 मिनट से अधिक हो गए हैं।<br /><br />
हर मिनट चलने वाले क्रोन जॉब का एक उदाहरण:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='इमाप सर्वर<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='ईमेल';
$lang["checkmail_password"]='पासवर्ड';
$lang["checkmail_extension_mapping"]='फ़ाइल एक्सटेंशन मैपिंग के माध्यम से संसाधन प्रकार';
$lang["checkmail_default_resource_type"]='डिफ़ॉल्ट संसाधन प्रकार';
$lang["checkmail_extension_mapping_desc"]='डिफ़ॉल्ट संसाधन प्रकार चयनकर्ता के बाद, आपके प्रत्येक संसाधन प्रकार के लिए नीचे एक इनपुट है। <br />विभिन्न प्रकार की अपलोड की गई फ़ाइलों को एक विशिष्ट संसाधन प्रकार में मजबूर करने के लिए, फ़ाइल एक्सटेंशन की अल्पविराम से अलग की गई सूचियाँ जोड़ें (उदा. jpg,gif,png)।';
$lang["checkmail_resource_type_population"]='(अनुमत एक्सटेंशन से)';
$lang["checkmail_subject_field"]='विषय क्षेत्र';
$lang["checkmail_body_field"]='शरीर फ़ील्ड';
$lang["checkmail_purge"]='अपलोड के बाद ई-मेल्स को हटाएं?';
$lang["checkmail_confirm"]='पुष्टिकरण ई-मेल भेजें?';
$lang["checkmail_users"]='अनुमत उपयोगकर्ता';
$lang["checkmail_blocked_users_label"]='अवरोधित उपयोगकर्ता';
$lang["checkmail_default_access"]='डिफ़ॉल्ट एक्सेस';
$lang["checkmail_default_archive"]='डिफ़ॉल्ट स्थिति';
$lang["checkmail_html"]='HTML सामग्री की अनुमति दें? (प्रायोगिक, अनुशंसित नहीं)';
$lang["checkmail_mail_skipped"]='छोड़ा गया ई-मेल';
$lang["checkmail_allow_users_based_on_permission_label"]='क्या उपयोगकर्ताओं को अनुमति के आधार पर अपलोड करने की अनुमति दी जानी चाहिए?';
$lang["addresourcesviaemail"]='ई-मेल के माध्यम से जोड़ें';
$lang["uploadviaemail"]='ई-मेल के माध्यम से जोड़ें';
$lang["uploadviaemail-intro"]='ई-मेल के माध्यम से अपलोड करने के लिए, अपनी फ़ाइल(फ़ाइलें) संलग्न करें और ई-मेल को <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> पर भेजें।</p> <p>सुनिश्चित करें कि इसे <b>[fromaddress]</b> से भेजा गया है, अन्यथा इसे अनदेखा कर दिया जाएगा।</p><p>ध्यान दें कि ई-मेल के विषय में जो कुछ भी होगा वह [applicationname] में [subjectfield] फ़ील्ड में जाएगा। </p><p> यह भी ध्यान दें कि ई-मेल के बॉडी में जो कुछ भी होगा वह [applicationname] में [bodyfield] फ़ील्ड में जाएगा। </p>  <p>एकाधिक फ़ाइलें एक संग्रह में समूहित की जाएंगी। आपके संसाधन डिफ़ॉल्ट रूप से एक्सेस स्तर <b>\'[access]\'</b> और आर्काइव स्थिति <b>\'[archive]\'</b> पर होंगे।</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='जब आपका ई-मेल सफलतापूर्वक संसाधित हो जाएगा, तो आपको एक पुष्टि ई-मेल प्राप्त होगा। यदि किसी कारणवश (जैसे कि गलत पते से भेजा गया हो) आपका ई-मेल प्रोग्रामेटिकली छोड़ दिया जाता है, तो प्रशासक को सूचित किया जाएगा कि एक ई-मेल ध्यान देने की आवश्यकता है।';
$lang["yourresourcehasbeenuploaded"]='आपका संसाधन अपलोड हो गया है';
$lang["yourresourceshavebeenuploaded"]='आपके संसाधन अपलोड हो गए हैं';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), आईडी [user-ref] और ई-मेल [user-email] के साथ ई-मेल के माध्यम से अपलोड करने की अनुमति नहीं है (अनुमतियों "c" या "d" या चेकमेल सेटअप पृष्ठ में अवरुद्ध उपयोगकर्ताओं की जाँच करें)। दर्ज किया गया: [datetime].';
$lang["checkmail_createdfromcheckmail"]='चेक मेल प्लगइन से बनाया गया';
$lang["plugin-checkmail-title"]='मेल जांचें';
$lang["plugin-checkmail-desc"]='[Advanced] ई-मेल संलग्नकों के इनजेशन की अनुमति देता है';