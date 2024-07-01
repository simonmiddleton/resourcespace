<?php


$lang["status4"]='अपरिवर्तनीय';
$lang["doi_info_wikipedia"]='https://en.wikipedia.org/wiki/Digital_Object_Identifier';
$lang["doi_info_link"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> पर।';
$lang["doi_info_metadata_schema"]='DataCite.org पर DOI पंजीकरण <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Datacite मेटाडेटा स्कीमा दस्तावेज़</a> में उल्लिखित हैं।';
$lang["doi_info_mds_api"]='इस प्लगइन द्वारा उपयोग किए गए DOI-API के बारे में <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API दस्तावेज़</a> में बताया गया है।';
$lang["doi_plugin_heading"]='यह प्लगइन अपरिवर्तनीय वस्तुओं और संग्रहों के लिए <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> बनाता है और उन्हें <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a> पर पंजीकृत करता है।';
$lang["doi_further_information"]='अधिक जानकारी';
$lang["doi_setup_doi_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">डीओआई</a> उत्पन्न करने के लिए <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">प्रिफिक्स</a>';
$lang["doi_info_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">doi उपसर्गों</a> पर।';
$lang["doi_setup_use_testmode"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">टेस्टमोड</a> का उपयोग करें';
$lang["doi_info_testmode"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">परीक्षण मोड</a> पर।';
$lang["doi_setup_use_testprefix"]='इसके बजाय <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">परीक्षण उपसर्ग (10.5072)</a> का उपयोग करें';
$lang["doi_info_testprefix"]='<a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">परीक्षण उपसर्ग</a> पर।';
$lang["doi_setup_publisher"]='<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">प्रकाशक</a>';
$lang["doi_info_publisher"]='<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">प्रकाशक</a> क्षेत्र में';
$lang["doi_resource_conditions_title"]='एक संसाधन को DOI पंजीकरण के लिए योग्य होने के लिए निम्नलिखित पूर्व शर्तों को पूरा करना आवश्यक है:';
$lang["doi_resource_conditions"]='<li>आपकी परियोजना को सार्वजनिक होना चाहिए, अर्थात्, एक सार्वजनिक क्षेत्र होना चाहिए।</li>
<li>संसाधन को सार्वजनिक रूप से सुलभ होना चाहिए, अर्थात्, इसकी पहुँच <strong>खुली</strong> होनी चाहिए।</li>
<li>संसाधन का एक <strong>शीर्षक</strong> होना चाहिए।</li>
<li>इसे {status} के रूप में चिह्नित किया जाना चाहिए, अर्थात्, इसकी स्थिति <strong>{status}</strong> होनी चाहिए।</li>
<li>तब, केवल एक <strong>प्रशासक</strong> को पंजीकरण प्रक्रिया शुरू करने की अनुमति है।</li>';
$lang["doi_setup_general_config"]='सामान्य विन्यास';
$lang["doi_setup_pref_fields_header"]='मेटाडेटा निर्माण के लिए पसंदीदा खोज क्षेत्र';
$lang["doi_setup_username"]='DataCite उपयोगकर्ता नाम';
$lang["doi_setup_password"]='DataCite पासवर्ड';
$lang["doi_pref_publicationYear_fields"]='देखें <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">प्रकाशन वर्ष</a> में:<br>(यदि कोई मान नहीं मिल सका, तो पंजीकरण का वर्ष उपयोग किया जाएगा।)';
$lang["doi_pref_creator_fields"]='देखें <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">निर्माता</a> में:';
$lang["doi_pref_title_fields"]='देखें <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">शीर्षक</a> में:';
$lang["doi_setup_default"]='यदि कोई मान नहीं मिला, तो <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">मानक कोड</a> का उपयोग करें:';
$lang["doi_setup_test_plugin"]='परीक्षण प्लगइन..';
$lang["doi_setup_test_succeeded"]='परीक्षण सफल हुआ!';
$lang["doi_setup_test_failed"]='परीक्षण विफल!';
$lang["doi_alert_text"]='ध्यान दें! एक बार DOI DataCite को भेज दिया गया, तो पंजीकरण को पूर्ववत नहीं किया जा सकता।';
$lang["doi_title_compulsory"]='कृपया DOI पंजीकरण जारी रखने से पहले एक शीर्षक सेट करें।';
$lang["doi_register"]='पंजीकरण करें';
$lang["doi_cancel"]='रद्द करें';
$lang["doi_sure"]='ध्यान दें! एक बार DOI DataCite को भेज दिया गया, तो पंजीकरण को पूर्ववत नहीं किया जा सकता। DataCite के मेटाडेटा स्टोर में पहले से पंजीकृत जानकारी संभवतः अधिलेखित हो सकती है।';
$lang["doi_already_set"]='पहले से सेट';
$lang["doi_not_yet_set"]='अभी तक सेट नहीं किया गया';
$lang["doi_already_registered"]='पहले से पंजीकृत';
$lang["doi_not_yet_registered"]='अभी तक पंजीकृत नहीं';
$lang["doi_successfully_registered"]='सफलतापूर्वक पंजीकृत किया गया';
$lang["doi_successfully_registered_pl"]='संसाधन सफलतापूर्वक पंजीकृत किया गया।';
$lang["doi_not_successfully_registered"]='सही तरीके से पंजीकृत नहीं किया जा सका';
$lang["doi_not_successfully_registered_pl"]='सही तरीके से पंजीकृत नहीं किया जा सका।';
$lang["doi_reload"]='पुनः लोड करें';
$lang["doi_successfully_set"]='सेट कर दिया गया है';
$lang["doi_not_successfully_set"]='सेट नहीं किया गया है';
$lang["doi_sum_of"]='का';
$lang["doi_sum_already_reg"]='संसाधन(ओं) के पास पहले से ही एक DOI है।';
$lang["doi_sum_not_yet_archived"]='संसाधन चिह्नित नहीं है/हैं';
$lang["doi_sum_not_yet_archived_2"]='अभी या इसकी/उनकी पहुँच खुली नहीं है।';
$lang["doi_sum_ready_for_reg"]='संसाधन पंजीकरण के लिए तैयार है/हैं।';
$lang["doi_sum_no_title"]='संसाधन(संसाधनों) को अभी भी एक शीर्षक की आवश्यकता है।';
$lang["doi_sum_no_title_2"]='फिर शीर्षक के रूप में।';
$lang["doi_register_all"]='इस संग्रह में सभी संसाधनों के लिए DOIs पंजीकृत करें';
$lang["doi_sure_register_resource"]='क्या आप x संसाधन(ओं) का पंजीकरण जारी रखना चाहते हैं?';
$lang["doi_show_meta"]='DOI मेटाडाटा दिखाएं';
$lang["doi_hide_meta"]='DOI मेटाडेटा छुपाएं';
$lang["doi_fetched_xml_from_MDS"]='वर्तमान XMl मेटाडेटा को DataCite के मेटाडेटा स्टोर से सफलतापूर्वक प्राप्त किया जा सकता है।';