<?php /* -------- Text Box (formatted / TinyMCE) ---------------- */ 

global $tinymce_plugins, $tinymce_toolbar;

// Ensure autoresize plugin is included so min_height can be used
if (mb_strpos($tinymce_plugins, 'autoresize') === false) {
    $tinymce_plugins .= ' autoresize';
}

?>

<div style="display: inline-block; width: 100%;">
    <br />
    <textarea
        class="stdwidth"
        name="<?php echo $name?>"
        id="<?php echo ((isset($modal) && $modal) ? "Modal_" : "CentralSpace_") . $name?>"
        <?php echo $help_js; ?>
    ><?php if ($value == strip_tags($value)) {
        $value = nl2br($value);
     }
     echo escape(strip_tags_and_attributes($value, array("a"), array("href","target")))?></textarea>
</div>

<script type="text/javascript">
    tinymce.remove('textarea#<?php echo ((isset($modal) && $modal) ? "Modal_" : "CentralSpace_") . $name?>');
    tinymce.init({
        selector: 'textarea#<?php echo ((isset($modal) && $modal) ? "Modal_" : "CentralSpace_") . $name?>',
        plugins: '<?php echo escape($tinymce_plugins); ?>',
        menubar: '',
        toolbar: "<?php echo escape($tinymce_toolbar); ?>",
        min_height: 400,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        setup: (editor) => {
            editor.on('blur', (e) => tinymce.triggerSave());
        },
    });

</script>

