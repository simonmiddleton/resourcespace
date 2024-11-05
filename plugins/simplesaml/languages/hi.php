<?php


$lang["simplesaml_configuration"]='SimpleSAML कॉन्फ़िगरेशन';
$lang["simplesaml_main_options"]='उपयोग विकल्प';
$lang["simplesaml_site_block"]='साइट तक पूरी तरह से पहुँच को अवरुद्ध करने के लिए SAML का उपयोग करें, यदि इसे सत्य पर सेट किया गया है तो कोई भी साइट तक पहुँच नहीं कर सकता, यहाँ तक कि गुमनाम रूप से भी, बिना प्रमाणीकरण के';
$lang["simplesaml_allow_public_shares"]='यदि साइट को अवरुद्ध कर रहे हैं, तो सार्वजनिक शेयरों को SAML प्रमाणीकरण को बायपास करने की अनुमति दें?';
$lang["simplesaml_allowedpaths"]='अतिरिक्त अनुमत पथों की सूची जो SAML आवश्यकता को बायपास कर सकते हैं';
$lang["simplesaml_allow_standard_login"]='उपयोगकर्ताओं को मानक खातों के साथ-साथ SAML SSO का उपयोग करके लॉग इन करने की अनुमति दें? चेतावनी: इसे अक्षम करने से SAML प्रमाणीकरण विफल होने पर सभी उपयोगकर्ताओं को सिस्टम से बाहर करने का जोखिम हो सकता है';
$lang["simplesaml_use_sso"]='SSO का उपयोग करके लॉग इन करें';
$lang["simplesaml_idp_configuration"]='IdP कॉन्फ़िगरेशन';
$lang["simplesaml_idp_configuration_description"]='अपने IdP के साथ प्लगइन को काम करने के लिए निम्नलिखित का उपयोग करें';
$lang["simplesaml_username_attribute"]='उपयोगकर्ता नाम के लिए उपयोग करने वाले गुण। यदि यह दो गुणों का संयोजन है, तो कृपया उन्हें अल्पविराम से अलग करें';
$lang["simplesaml_username_separator"]='यदि उपयोगकर्ता नाम के लिए फ़ील्ड्स को जोड़ रहे हैं तो इस अक्षर का उपयोग विभाजक के रूप में करें';
$lang["simplesaml_fullname_attribute"]='पूर्ण नाम के लिए उपयोग करने वाले गुण। यदि यह दो गुणों का संयोजन है तो कृपया उन्हें अल्पविराम से अलग करें';
$lang["simplesaml_fullname_separator"]='यदि पूर्ण नाम के लिए फ़ील्ड्स को जोड़ रहे हैं तो इस अक्षर का उपयोग विभाजक के रूप में करें';
$lang["simplesaml_email_attribute"]='ईमेल पते के लिए उपयोग करने वाला गुण';
$lang["simplesaml_group_attribute"]='समूह सदस्यता निर्धारित करने के लिए उपयोग करने वाला गुण';
$lang["simplesaml_username_suffix"]='सामान्य ResourceSpace खातों से अलग करने के लिए बनाए गए उपयोगकर्ता नामों में जोड़ने के लिए प्रत्यय';
$lang["simplesaml_update_group"]='प्रत्येक लॉगिन पर उपयोगकर्ता समूह को अपडेट करें। यदि एक्सेस निर्धारित करने के लिए SSO समूह विशेषता का उपयोग नहीं कर रहे हैं, तो इसे गलत पर सेट करें ताकि उपयोगकर्ताओं को मैन्युअल रूप से समूहों के बीच स्थानांतरित किया जा सके';
$lang["simplesaml_groupmapping"]='SAML - ResourceSpace समूह मैपिंग';
$lang["simplesaml_fallback_group"]='नए बनाए गए उपयोगकर्ताओं के लिए उपयोग किया जाने वाला डिफ़ॉल्ट उपयोगकर्ता समूह';
$lang["simplesaml_samlgroup"]='SAML समूह';
$lang["simplesaml_rsgroup"]='रिसोर्सस्पेस समूह';
$lang["simplesaml_priority"]='प्राथमिकता (उच्च संख्या को प्राथमिकता दी जाएगी)';
$lang["simplesaml_addrow"]='मैपिंग जोड़ें';
$lang["simplesaml_service_provider"]='स्थानीय सेवा प्रदाता (SP) का नाम';
$lang["simplesaml_prefer_standard_login"]='मानक लॉगिन को प्राथमिकता दें (डिफ़ॉल्ट रूप से लॉगिन पृष्ठ पर पुनर्निर्देशित करें)';
$lang["simplesaml_sp_configuration"]='इस प्लगइन का उपयोग करने के लिए simplesaml SP कॉन्फ़िगरेशन को पूरा करना आवश्यक है। अधिक जानकारी के लिए कृपया नॉलेज बेस लेख देखें।';
$lang["simplesaml_custom_attributes"]='उपयोगकर्ता रिकॉर्ड के खिलाफ रिकॉर्ड करने के लिए कस्टम विशेषताएँ';
$lang["simplesaml_custom_attribute_label"]='एसएसओ विशेषता - ';
$lang["simplesaml_usercomment"]='SimpleSAML प्लगइन द्वारा निर्मित';
$lang["origin_simplesaml"]='सिंपलसैमएल प्लगइन';
$lang["simplesaml_lib_path_label"]='SAML लाइब्रेरी पथ (कृपया पूरा सर्वर पथ निर्दिष्ट करें)';
$lang["simplesaml_login"]='ResourceSpace में लॉगिन करने के लिए SAML क्रेडेंशियल्स का उपयोग करें? (यह केवल तभी प्रासंगिक है जब ऊपर का विकल्प सक्षम हो)';
$lang["simplesaml_create_new_match_email"]='ईमेल-मिलान: नए उपयोगकर्ताओं को बनाने से पहले, जांचें कि SAML उपयोगकर्ता ईमेल मौजूदा RS खाते के ईमेल से मेल खाता है या नहीं। यदि मेल पाया जाता है तो SAML उपयोगकर्ता उस खाते को \'अपनाएगा\'';
$lang["simplesaml_allow_duplicate_email"]='क्या मौजूदा ResourceSpace खातों के साथ समान ईमेल पते होने पर नए खाते बनाए जाने की अनुमति दी जाए? (यदि ऊपर ईमेल-मिलान सेट है और एक मेल पाया जाता है तो इसे ओवरराइड किया जाएगा)';
$lang["simplesaml_multiple_email_match_subject"]='ResourceSpace SAML - विरोधाभासी ईमेल लॉगिन प्रयास';
$lang["simplesaml_multiple_email_match_text"]='एक नए SAML उपयोगकर्ता ने सिस्टम का उपयोग किया है, लेकिन पहले से ही एक ही ईमेल पते के साथ एक से अधिक खाते मौजूद हैं।';
$lang["simplesaml_multiple_email_notify"]='यदि कोई ईमेल संघर्ष पाया जाता है तो सूचित करने के लिए ईमेल पता';
$lang["simplesaml_duplicate_email_error"]='इसी ईमेल पते के साथ एक मौजूदा खाता है। कृपया अपने प्रशासक से संपर्क करें।';
$lang["simplesaml_usermatchcomment"]='SimpleSAML प्लगइन द्वारा SAML उपयोगकर्ता में अद्यतन किया गया।';
$lang["simplesaml_usercreated"]='नया SAML उपयोगकर्ता बनाया गया';
$lang["simplesaml_duplicate_email_behaviour"]='डुप्लिकेट खाता प्रबंधन';
$lang["simplesaml_duplicate_email_behaviour_description"]='यह अनुभाग नियंत्रित करता है कि यदि एक नया SAML उपयोगकर्ता लॉगिन करता है और एक मौजूदा खाता के साथ संघर्ष करता है तो क्या होता है';
$lang["simplesaml_authorisation_rules_header"]='प्राधिकरण नियम';
$lang["simplesaml_authorisation_rules_description"]='ResourceSpace को अतिरिक्त स्थानीय प्राधिकरण के साथ उपयोगकर्ताओं को कॉन्फ़िगर करने के लिए सक्षम करें, जो IdP से प्रतिक्रिया में एक अतिरिक्त विशेषता (जैसे कि दावा/अभिकथन) पर आधारित हो। इस अभिकथन का उपयोग प्लगइन द्वारा यह निर्धारित करने के लिए किया जाएगा कि उपयोगकर्ता को ResourceSpace में लॉग इन करने की अनुमति है या नहीं।';
$lang["simplesaml_authorisation_claim_name_label"]='गुण (दावा/ कथन) नाम';
$lang["simplesaml_authorisation_claim_value_label"]='गुण (दावा/ कथन) मान';
$lang["simplesaml_authorisation_login_error"]='आपको इस एप्लिकेशन का उपयोग करने की अनुमति नहीं है! कृपया अपने खाते के लिए व्यवस्थापक से संपर्क करें!';
$lang["simplesaml_authorisation_version_error"]='महत्वपूर्ण: आपके SimpleSAML कॉन्फ़िगरेशन को अपडेट करने की आवश्यकता है। अधिक जानकारी के लिए कृपया Knowledge Base के \'<a href=\'https://www.resourcespace.com/knowledge-base/plugins/simplesaml#saml_instructions_migrate\' target=\'_blank\'> ResourceSpace कॉन्फ़िगरेशन का उपयोग करने के लिए SP को माइग्रेट करना</a>\' अनुभाग को देखें।';
$lang["simplesaml_healthcheck_error"]='SimpleSAML प्लगइन त्रुटि';
$lang["simplesaml_rsconfig"]='मानक ResourceSpace कॉन्फ़िगरेशन फ़ाइलों का उपयोग करके SP कॉन्फ़िगरेशन और मेटाडेटा सेट करें? यदि इसे false पर सेट किया गया है तो फ़ाइलों को मैन्युअल रूप से संपादित करना आवश्यक है';
$lang["simplesaml_sp_generate_config"]='एसपी कॉन्फ़िगरेशन उत्पन्न करें';
$lang["simplesaml_sp_config"]='सेवा प्रदाता (SP) विन्यास';
$lang["simplesaml_sp_data"]='सेवा प्रदाता (SP) जानकारी';
$lang["simplesaml_idp_section"]='IdP';
$lang["simplesaml_idp_metadata_xml"]='IdP मेटाडेटा XML में पेस्ट करें';
$lang["simplesaml_sp_cert_path"]='एसपी प्रमाणपत्र फ़ाइल का पथ (उत्पन्न करने के लिए खाली छोड़ें लेकिन नीचे प्रमाणपत्र विवरण भरें)';
$lang["simplesaml_sp_key_path"]='SP कुंजी फ़ाइल (.pem) का पथ (उत्पन्न करने के लिए खाली छोड़ें)';
$lang["simplesaml_sp_idp"]='IdP पहचानकर्ता (यदि XML संसाधित कर रहे हैं तो खाली छोड़ दें)';
$lang["simplesaml_saml_config_output"]='इसे अपने ResourceSpace कॉन्फ़िग फ़ाइल में पेस्ट करें';
$lang["simplesaml_sp_cert_info"]='प्रमाणपत्र जानकारी (आवश्यक)';
$lang["simplesaml_sp_cert_countryname"]='देश कोड (केवल 2 अक्षर)';
$lang["simplesaml_sp_cert_stateorprovincename"]='राज्य, काउंटी या प्रांत का नाम';
$lang["simplesaml_sp_cert_localityname"]='स्थान (जैसे कि नगर/शहर)';
$lang["simplesaml_sp_cert_organizationname"]='संगठन का नाम';
$lang["simplesaml_sp_cert_organizationalunitname"]='संगठनात्मक इकाई /विभाग';
$lang["simplesaml_sp_cert_commonname"]='सामान्य नाम (जैसे sp.acme.org)';
$lang["simplesaml_sp_cert_emailaddress"]='ईमेल पता';
$lang["simplesaml_sp_cert_invalid"]='अमान्य प्रमाणपत्र जानकारी';
$lang["simplesaml_sp_cert_gen_error"]='प्रमाणपत्र उत्पन्न करने में असमर्थ';
$lang["simplesaml_sp_samlphp_link"]='SimpleSAMLphp परीक्षण साइट पर जाएँ';
$lang["simplesaml_sp_technicalcontact_name"]='तकनीकी संपर्क नाम';
$lang["simplesaml_sp_technicalcontact_email"]='तकनीकी संपर्क ईमेल';
$lang["simplesaml_sp_auth.adminpassword"]='एसपी टेस्ट साइट एडमिन पासवर्ड';
$lang["simplesaml_acs_url"]='एसीएस यूआरएल / उत्तर यूआरएल';
$lang["simplesaml_entity_id"]='इकाई आईडी/मेटाडेटा यूआरएल';
$lang["simplesaml_single_logout_url"]='एकल लॉगआउट यूआरएल';
$lang["simplesaml_start_url"]='प्रारंभ/साइन ऑन यूआरएल';
$lang["simplesaml_existing_config"]='अपने मौजूदा SAML कॉन्फ़िगरेशन को माइग्रेट करने के लिए नॉलेज बेस निर्देशों का पालन करें';
$lang["simplesaml_test_site_url"]='SimpleSAML परीक्षण साइट URL';
$lang["plugin-simplesaml-title"]='सरल SAML';
$lang["plugin-simplesaml-desc"]='[उन्नत] ResourceSpace तक पहुँचने के लिए SAML प्रमाणीकरण आवश्यक है';
$lang["simplesaml_idp_certs"]='SAML IdP प्रमाणपत्र';
$lang["simplesaml_idp_cert_expiring"]='IdP %idpname प्रमाणपत्र %expiretime पर समाप्त हो रहा है';
$lang["simplesaml_idp_cert_expired"]='IdP %idpname प्रमाणपत्र %expiretime पर समाप्त हो गया';
$lang["simplesaml_idp_cert_expires"]='IdP %idpname प्रमाणपत्र %expiretime पर समाप्त हो रहा है';