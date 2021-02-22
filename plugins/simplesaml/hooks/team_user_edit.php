<?php
function HookSimplesamlTeam_user_editAdditionaluserfields()
    {
    global $user, $lang;

    if(isset($user['simplesaml_custom_attributes']))
        {
        $custom_attributes = $user['simplesaml_custom_attributes'];
        }
    else
        {
        $custom_attributes = sql_value("SELECT simplesaml_custom_attributes as `value` FROM user WHERE ref = '{$user['ref']}'", '');
        }

    if('' == $custom_attributes)
        {
        return;
        }

    $custom_attributes = json_decode($custom_attributes);

    if(0 == count($custom_attributes))
        {
        return;
        }

    foreach($custom_attributes as $custom_attribute => $custom_attribute_value)
        {
        ?>
        <div class="Question">
            <label><?php echo $lang['simplesaml_custom_attribute_label'] . ucfirst(htmlspecialchars($custom_attribute)); ?></label>
            <input type="text" class="stdwidth" value="<?php echo htmlspecialchars($custom_attribute_value); ?>" disabled>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
    }