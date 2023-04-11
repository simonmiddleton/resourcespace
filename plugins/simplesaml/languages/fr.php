<?php


$lang["simplesaml_configuration"]='Configuration SimpleSAML.';
$lang["simplesaml_main_options"]='Options d\'utilisation.';
$lang["simplesaml_site_block"]='Utiliser SAML pour bloquer complètement l\'accès au site, si défini sur vrai alors personne ne peut accéder au site, même de manière anonyme, sans s\'authentifier.';
$lang["simplesaml_allow_public_shares"]='Si le site est bloqué, autoriser les partages publics à contourner l\'authentification SAML ?';
$lang["simplesaml_allowedpaths"]='Liste des chemins supplémentaires autorisés qui peuvent contourner l\'exigence SAML.';
$lang["simplesaml_allow_standard_login"]='Autoriser les utilisateurs à se connecter avec des comptes standards ainsi qu\'en utilisant SAML SSO ? ATTENTION : Désactiver cette option peut entraîner le risque de verrouiller tous les utilisateurs du système si l\'authentification SAML échoue.';
$lang["simplesaml_use_sso"]='Utiliser SSO pour se connecter.';
$lang["simplesaml_idp_configuration"]='Configuration de l\'IdP';
$lang["simplesaml_idp_configuration_description"]='Utilisez les éléments suivants pour configurer le plugin afin qu\'il fonctionne avec votre fournisseur d\'identité (IdP).';
$lang["simplesaml_username_attribute"]='Attribut(s) à utiliser pour le nom d\'utilisateur. S\'il s\'agit d\'une concaténation de deux attributs, veuillez les séparer par une virgule.';
$lang["simplesaml_username_separator"]='Si vous joignez des champs pour le nom d\'utilisateur, utilisez ce caractère comme séparateur.';
$lang["simplesaml_fullname_attribute"]='Attribut(s) à utiliser pour le nom complet. S\'il s\'agit d\'une concaténation de deux attributs, veuillez les séparer par une virgule.';
$lang["simplesaml_fullname_separator"]='Si vous joignez les champs pour le nom complet, utilisez ce caractère comme séparateur.';
$lang["simplesaml_email_attribute"]='Attribut à utiliser pour l\'adresse e-mail.';
$lang["simplesaml_group_attribute"]='Attribut à utiliser pour déterminer l\'appartenance à un groupe.';
$lang["simplesaml_username_suffix"]='Suffixe à ajouter aux noms d\'utilisateur créés pour les distinguer des comptes ResourceSpace standard.';
$lang["simplesaml_update_group"]='Mettre à jour le groupe d\'utilisateurs à chaque connexion. Si l\'attribut de groupe SSO n\'est pas utilisé pour déterminer l\'accès, définissez-le sur "faux" afin que les utilisateurs puissent être déplacés manuellement entre les groupes.';
$lang["simplesaml_groupmapping"]='SAML - Mappage de groupe ResourceSpace';
$lang["simplesaml_fallback_group"]='Groupe d\'utilisateurs par défaut qui sera utilisé pour les nouveaux utilisateurs créés.';
$lang["simplesaml_samlgroup"]='Groupe SAML';
$lang["simplesaml_rsgroup"]='Groupe ResourceSpace';
$lang["simplesaml_priority"]='Priorité (un nombre plus élevé aura la priorité)';
$lang["simplesaml_addrow"]='Ajouter une correspondance.';
$lang["simplesaml_service_provider"]='Nom du fournisseur de services local (SP)';
$lang["simplesaml_prefer_standard_login"]='Préférer la connexion standard (rediriger vers la page de connexion par défaut)';
$lang["simplesaml_sp_configuration"]='La configuration du fournisseur de services simplesaml doit être complétée afin d\'utiliser ce plugin. Veuillez consulter l\'article de la base de connaissances pour plus d\'informations.';
$lang["simplesaml_custom_attributes"]='Attributs personnalisés à enregistrer dans le profil utilisateur.';
$lang["simplesaml_custom_attribute_label"]='Attribut SSO';
$lang["simplesaml_usercomment"]='Créé par le plugin SimpleSAML.';
$lang["origin_simplesaml"]='Plugin SimpleSAML.';
$lang["simplesaml_lib_path_label"]='Chemin de la bibliothèque SAML (veuillez spécifier le chemin complet du serveur)';
$lang["simplesaml_login"]='Utiliser les identifiants SAML pour se connecter à ResourceSpace ? (Ceci est uniquement pertinent si l\'option ci-dessus est activée)';
$lang["simplesaml_create_new_match_email"]='Correspondance par e-mail : Avant de créer de nouveaux utilisateurs, vérifiez si l\'e-mail de l\'utilisateur SAML correspond à un e-mail de compte RS existant. Si une correspondance est trouvée, l\'utilisateur SAML "adoptera" ce compte.';
$lang["simplesaml_multiple_email_match_subject"]='ResourceSpace SAML - tentative de connexion avec un email en conflit.';
$lang["simplesaml_multiple_email_match_text"]='Un nouvel utilisateur SAML a accédé au système mais il existe déjà plus d\'un compte avec la même adresse e-mail.';
$lang["simplesaml_multiple_email_notify"]='Adresse e-mail pour notifier en cas de conflit de courrier électronique trouvé.';
$lang["simplesaml_duplicate_email_error"]='Il existe déjà un compte avec la même adresse e-mail. Veuillez contacter votre administrateur.';
$lang["simplesaml_usermatchcomment"]='Mis à jour en utilisateur SAML par le plugin SimpleSAML.';
$lang["simplesaml_usercreated"]='Création d\'un nouvel utilisateur SAML.';
$lang["simplesaml_duplicate_email_behaviour"]='Gestion des comptes en double.';
$lang["simplesaml_duplicate_email_behaviour_description"]='Cette section contrôle ce qui se passe si un nouvel utilisateur SAML qui se connecte entre en conflit avec un compte existant.';
$lang["simplesaml_authorisation_rules_header"]='Règle d\'autorisation.';
$lang["simplesaml_authorisation_rules_description"]='Permettre à ResourceSpace d\'être configuré avec une autorisation locale supplémentaire des utilisateurs basée sur un attribut supplémentaire (c\'est-à-dire une assertion/réclamation) dans la réponse de l\'IdP. Cette assertion sera utilisée par le plugin pour déterminer si l\'utilisateur est autorisé à se connecter à ResourceSpace ou non.';
$lang["simplesaml_authorisation_claim_name_label"]='Nom de l\'attribut (assertion/réclamation)';
$lang["simplesaml_authorisation_claim_value_label"]='Valeur d\'attribut (assertion/réclamation)';
$lang["simplesaml_authorisation_login_error"]='Vous n\'avez pas accès à cette application ! Veuillez contacter l\'administrateur de votre compte !';
$lang["simplesaml_authorisation_version_error"]='IMPORTANT : Votre configuration SimpleSAML doit être mise à jour. Veuillez vous référer à la section \'<a href=\'https://www.resourcespace.com/knowledge-base/plugins/simplesaml#saml_instructions_migrate\' target=\'_blank\'>Migration du SP pour utiliser la configuration de ResourceSpace</a>\' de la base de connaissances pour plus d\'informations.';
$lang["simplesaml_healthcheck_error"]='Erreur de plugin SimpleSAML.';
$lang["simplesaml_rsconfig"]='Utiliser les fichiers de configuration standard de ResourceSpace pour définir la configuration SP et les métadonnées ? Si cette option est définie sur "false", alors l\'édition manuelle des fichiers est requise.';
$lang["simplesaml_sp_generate_config"]='Générer la configuration SP.';
$lang["simplesaml_sp_config"]='Configuration du fournisseur de services (SP)';
$lang["simplesaml_sp_data"]='Informations du fournisseur de services (SP)';
$lang["simplesaml_idp_section"]='Système d\'authentification de l\'identité (IdP)';
$lang["simplesaml_idp_metadata_xml"]='Coller le XML des métadonnées de l\'IdP.';
$lang["simplesaml_sp_cert_path"]='Chemin d\'accès au fichier de certificat SP (laissez vide pour générer mais remplissez les détails du certificat ci-dessous)';
$lang["simplesaml_sp_key_path"]='Chemin d\'accès au fichier clé SP (.pem) (laissez vide pour générer)';
$lang["simplesaml_sp_idp"]='Identifiant IdP (laissez vide si traitement XML)';
$lang["simplesaml_saml_config_output"]='Collez ceci dans votre fichier de configuration ResourceSpace.';
$lang["simplesaml_sp_cert_info"]='Informations du certificat (obligatoire)';
$lang["simplesaml_sp_cert_countryname"]='Code de pays (2 caractères seulement)';
$lang["simplesaml_sp_cert_stateorprovincename"]='Nom de l\'État, du comté ou de la province.';
$lang["simplesaml_sp_cert_localityname"]='Localité (par exemple ville)';
$lang["simplesaml_sp_cert_organizationname"]='Nom de l\'organisation.';
$lang["simplesaml_sp_cert_organizationalunitname"]='Unité organisationnelle / Département';
$lang["simplesaml_sp_cert_commonname"]='Nom commun (par exemple, sp.acme.org)';
$lang["simplesaml_sp_cert_emailaddress"]='Adresse e-mail.';
$lang["simplesaml_sp_cert_invalid"]='Informations de certificat invalides.';
$lang["simplesaml_sp_cert_gen_error"]='Impossible de générer le certificat.';
$lang["simplesaml_sp_samlphp_link"]='Visitez le site de test SimpleSAMLphp.';
$lang["simplesaml_sp_technicalcontact_name"]='Nom du contact technique.';
$lang["simplesaml_sp_technicalcontact_email"]='Adresse e-mail du contact technique.';
$lang["simplesaml_sp_auth.adminpassword"]='Mot de passe administrateur du site de test SP.';
$lang["simplesaml_acs_url"]='URL ACS / URL de réponse';
$lang["simplesaml_entity_id"]='Identifiant d\'entité/URL de métadonnées.';
$lang["simplesaml_single_logout_url"]='URL de déconnexion unique';
$lang["simplesaml_start_url"]='URL de démarrage/connexion';
$lang["simplesaml_existing_config"]='Suivez les instructions de la base de connaissances pour migrer votre configuration SAML existante.';
$lang["simplesaml_test_site_url"]='URL du site de test SimpleSAML.';
$lang["simplesaml_allow_duplicate_email"]='Autoriser la création de nouveaux comptes si des comptes ResourceSpace existants ont la même adresse e-mail ? (cela est remplacé si la correspondance d\'e-mail est définie ci-dessus et qu\'une correspondance est trouvée)';