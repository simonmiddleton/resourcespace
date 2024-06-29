<?php


$lang["rse_workflow_configuration"]='Arbetsflödeskonfiguration';
$lang["rse_workflow_summary"]='Detta tillägg gör det möjligt för dig att skapa ytterligare arkiv (arbetsflödes) tillstånd, samt definiera åtgärder för att beskriva rörelsen mellan tillstånden. <br><br>';
$lang["rse_workflow_introduction"]='För att ändra arbetsflödesstatusar och åtgärder, använd \'Hantera arbetsflödesåtgärder\' och \'Hantera arbetsflödesstatusar\' från Admin. Klicka %%HÄR för att gå till Admin';
$lang["rse_workflow_user_info"]='Dessa åtgärder kommer att ändra arbetsflödesstatusen för denna resurs och kan utlösa åtgärder för andra användare.';
$lang["rse_workflow_actions_heading"]='Arbetsflödesåtgärder';
$lang["rse_workflow_manage_workflow"]='Arbetsflöde';
$lang["rse_workflow_manage_actions"]='Arbetsflödesåtgärder';
$lang["rse_workflow_manage_states"]='Arbetsflödesstatus';
$lang["rse_workflow_status_heading"]='Definierade åtgärder';
$lang["rse_workflow_action_new"]='Skapa ny åtgärd';
$lang["rse_workflow_state_new"]='Skapa ny arbetsflödesstatus';
$lang["rse_workflow_action_reference"]='Åtgärdsreferens (tillstånd)';
$lang["rse_workflow_action_name"]='Åtgärdsnamn';
$lang["rse_workflow_action_filter"]='Filtrera åtgärder som är tillämpliga på ett tillstånd';
$lang["rse_workflow_action_text"]='Åtgärdstext';
$lang["rse_workflow_button_text"]='Knapp-text';
$lang["rse_workflow_new_action"]='Skapa ny åtgärd';
$lang["rse_workflow_action_status_from"]='Från status';
$lang["rse_workflow_action_status_to"]='Målställningens status';
$lang["rse_workflow_action_check_fields"]='Ogiltiga alternativ för arbetsflödesåtgärd, vänligen kontrollera dina valda alternativ';
$lang["rse_workflow_action_none_defined"]='Inga arbetsflödesåtgärder har definierats';
$lang["rse_workflow_action_edit_action"]='Redigera åtgärd';
$lang["rse_workflow_action_none_specified"]='Ingen åtgärd specificerad';
$lang["rse_workflow_action_deleted"]='Åtgärd borttagen';
$lang["rse_workflow_access"]='Tillgång till arbetsflödesåtgärd';
$lang["rse_workflow_saved"]='Resursen har flyttats till tillståndet:';
$lang["rse_workflow_edit_state"]='Redigera arbetsflödesstatus';
$lang["rse_workflow_state_reference"]='Arbetsflödesstatusreferens';
$lang["rse_workflow_state_name"]='Namn på arbetsflödesstatus';
$lang["rse_workflow_state_fixed"]='Fastställd i config.php';
$lang["rse_workflow_state_not_editable"]='Denna arkivstatus är inte redigerbar, antingen är det en obligatorisk systemstatus, har ställts in i config.php eller så finns den inte';
$lang["rse_workflow_state_check_fields"]='Ogiltigt namn eller referens för arbetsflödesstatus, vänligen kontrollera dina poster';
$lang["rse_workflow_state_deleted"]='Arbetsflödesstatus raderad';
$lang["rse_workflow_confirm_action_delete"]='Är du säker på att du vill ta bort denna åtgärd?';
$lang["rse_workflow_confirm_state_delete"]='Är du säker på att du vill ta bort detta arbetsflödesstatus?';
$lang["rse_workflow_state_need_target"]='Ange en målstatereferens för befintliga resurser i denna arbetsflödesstatus';
$lang["rse_workflow_confirm_batch_wf_change"]='Bekräfta ändring av arbetsflödesstatus för satsvis behandling';
$lang["rse_workflow_confirm_to_state"]='Följande åtgärd kommer att redigera alla påverkade resurser i batch och flytta dem till arbetsflödesstatusen \'%wf_name\'';
$lang["rse_workflow_err_invalid_action"]='Ogiltig åtgärd';
$lang["rse_workflow_err_missing_wfstate"]='Saknad arbetsflödesstatus';
$lang["rse_workflow_affected_resources"]='Påverkade resurser: %count';
$lang["rse_workflow_confirm_resources_moved_to_state"]='Flyttade alla påverkade resurser till \'%wf_name\' arbetsflödesstatus.';
$lang["rse_workflow_state_notify_group"]='När resurser når detta tillstånd, meddela användargrupp:';
$lang["rse_workflow_state_notify_message"]='Det finns nya resurser i arbetsflödesstatusen:';
$lang["rse_workflow_more_notes_label"]='Tillåt tillägg av extra anteckningar vid ändring av arbetsflöde?';
$lang["rse_workflow_notify_user_label"]='Ska bidragsgivaren meddelas?';
$lang["rse_workflow_simple_search_label"]='Inkludera arbetsflödesstatus i standard sökningar (vissa speciella sökningar kan ignorera detta)';
$lang["rse_workflow_link_open"]='Mer';
$lang["rse_workflow_link_close"]='Stäng';
$lang["rse_workflow_more_notes_title"]='Anteckningar:';
$lang["rse_workflow_email_from"]='E-postadress att skicka notifieringar från (kommer att använda %EMAILFROM% om tom):';
$lang["rse_workflow_bcc_admin"]='Meddela systemadministratörerna när bidragsgivaren har meddelats';
$lang["rse_workflow_state_notify_help"]='Användare kommer att se resurser i detta tillstånd som åtgärder, snarare än att få enkla aviseringar';