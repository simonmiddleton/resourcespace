<?php /* -------- Text Box (formatted / CKeditor) ---------------- */ ?>
<br /><br />

<?php hook("befckeditortextarea"); ?>

<textarea class="stdwidth" rows=10 cols=80 name="<?php echo $name?>" id="<?php echo ((isset($modal) && $modal)?"Modal_":"CentralSpace_") . $name?>" <?php echo $help_js; ?>
><?php if($value == strip_tags($value)){
	$value=nl2br($value);
}
echo htmlspecialchars(strip_tags_and_attributes($value,array("a"),array("href","target")))?></textarea><?php

switch (strtolower($language))
    {
    case "en":
        # en in ResourceSpace corresponds to en-gb in CKEditor
        $ckeditor_language = "en-gb";
        break;
    case "en-us";
        # en-US in ResourceSpace corresponds to en in CKEditor
        $ckeditor_language = "en";
        break;
    default:
        $ckeditor_language = strtolower($language);
        break;
    }
?>
<script type="text/javascript">

// Replace the <textarea id=$name> with an CKEditor instance.
<?php if(!hook("ckeditorinit","",array($name))): ?>
var editor = CKEDITOR.instances['<?php echo ((isset($modal) && $modal)?"Modal_":"CentralSpace_") . $name?>'];
if (editor) { editor.destroy(true); }
CKEDITOR.replace('<?php echo ((isset($modal) && $modal)?"Modal_":"CentralSpace_") . $name ?>',
    {
    language: '<?php echo $ckeditor_language ?>',
    // Define the toolbar to be used.
    toolbar : [ [ <?php global $ckeditor_toolbars;echo $ckeditor_toolbars; ?> ] ],
    height: "150",
    });
var editor = CKEDITOR.instances['<?php echo ((isset($modal) && $modal)?"Modal_":"CentralSpace_") . $name?>'];
<?php endif; ?>

<?php hook("ckeditoroptions"); ?>

<?php 
# Add an event handler to auto save this field if changed.
if ($edit_autosave) {?>
editor.on('blur',function(e) 
	{
	if(this.checkDirty())
		{
		this.updateElement();
		AutoSave('<?php echo $field["ref"]?>');
		}
	});

<?php } ?>

// Ensure that help text is shown when given focus
editor.on('focus', function(e)
    {
    ShowHelp('<?php echo $field["ref"]?>');
    });

editor.on('blur', function(e)
    {
    HideHelp('<?php echo $field["ref"]?>');
    });

</script>

