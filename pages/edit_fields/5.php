<?php /* -------- Text Box (large multi-line) ----- */ ?>
<textarea class="stdwidth" rows=20 cols=80 name="<?php echo $name?>" id="<?php echo $name?>" <?php echo $help_js; ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><?php echo htmlspecialchars(strip_tags(strip_tags_and_attributes($value))); ?></textarea>
