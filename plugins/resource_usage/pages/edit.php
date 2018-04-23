<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";

if(!checkperm('r'))
    {
    exit('Permission denied.');
    }

include_once "../../../include/resource_functions.php";

$ref      = getvalescaped('ref', '');
$resource = getvalescaped('resource', '');

# Check access
$edit_access = get_edit_access($resource);
if(!$edit_access)
    {
    # Should never arrive at this page without edit access
    exit("Access denied");
    }

# Set default values for the creation of a new record
$new_record = true;
$usage_data = array(
    'resource'       => $resource,
    'usage_location' => '',
    'usage_medium'   => '',
    'description'    => '',
    'usage_date'     => date('Y-m-d')
);

# Fetch usage data
if (trim($ref) != '')
    {
    $usage_data = sql_query("SELECT * FROM resource_usage WHERE ref = '$ref'");

    if(count($usage_data) == 0)
        {
        exit('Usage not found.');
        }

    $new_record = false;

    $usage_data = $usage_data[0];
    $resource   = $usage_data['resource'];
    }

if(getval('submitted', '') != '' && enforcePostRequest(false))
    {
    $usage_location = getvalescaped('usage_location', '');
    $usage_medium   = getvalescaped('usage_medium', '');
    $description    = getvalescaped('description', '');

    # Construct usage date
    $usage_date = getvalescaped('usage_date_year', '') . '-' . getvalescaped('usage_date_month', '') . '-' . getvalescaped('usage_date_day', '-');

    # Construct usage medium
    $usage_medium = '';
    if(isset($_POST['usage_medium']))
        {
        $usage_medium = escape_check(join(', ', $_POST['usage_medium']));
        }

    if($new_record)
        {
        # New record 
        sql_query("INSERT INTO resource_usage(resource, usage_location, usage_medium, description, usage_date) VALUES ('$resource', '$usage_location', '$usage_medium', '$description', '$usage_date')");

        $ref = sql_insert_id();

        resource_log($resource, '', '', $lang['new_usage'] . ' ' . $ref);
        }
    else
        {
        # Existing record   
        sql_query("UPDATE resource_usage SET usage_location = '$usage_location', usage_medium = '$usage_medium', description = '$description', usage_date = '$usage_date' WHERE ref = '$ref' AND resource = '$resource'");
        
        resource_log($resource, '', '', $lang['edit_usage'] . ' ' . $ref);
        }

    redirect('pages/view.php?ref=' . $resource);
    }
       
include "../../../include/header.php";
?>
<div class="BasicsBox">
    <p>
        <a href="<?php echo $baseurl_short; ?>pages/view.php?ref=<?php echo $resource; ?>" onClick="return CentralSpaceLoad(this, true);">&lt;&nbsp;<?php echo $lang['backtoresourceview']; ?></a>
    </p>
    <h1><?php echo ($new_record ? $lang['new_usage'] : $lang['edit_usage']); ?></h1>

    <form method="post" action="<?php echo $baseurl_short?>plugins/resource_usage/pages/edit.php" onSubmit="return CentralSpacePost(this, true);">
        <?php generateFormToken("resource_usage_editForm"); ?>
        <input type=hidden name="submitted" value="true">
        <input type=hidden name="ref" value="<?php echo $ref; ?>">
        <input type=hidden name="resource" value="<?php echo $resource; ?>">

    <div class="Question">
        <label><?php echo $lang['usage_ref']; ?></label>
        <div class="Fixed"><?php echo ($new_record ? $lang['usage_id_new'] : htmlspecialchars($ref)); ?></div>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['resourceid']; ?></label>
        <div class="Fixed"><?php echo htmlspecialchars($usage_data['resource']); ?></div>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['usage_location']; ?></label>
        <input class="stdwidth" type="text" name="usage_location" value="<?php echo htmlspecialchars($usage_data['usage_location']); ?>">
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['usage_medium']; ?></label>
        <?php
        $s = trim_array(explode(',', $usage_data['usage_medium']));
        foreach($resource_usage_mediums as $medium)
            {
            ?>
            <input type="checkbox" name="usage_medium[]" value="<?php echo $medium; ?>" <?php if(in_array($medium, $s)) { ?>checked<?php } ?>>&nbsp;<?php echo $medium; ?>
            &nbsp;&nbsp;&nbsp;
            <?php
            }

        // Old mediums might have been removed from the options
        // Nonetheless we should still show it as being checked
        foreach($s as $old_medium)
            {
            if('' !== trim($old_medium) && !in_array($old_medium, $resource_usage_mediums))
                {
                ?>
                <input type="checkbox" name="usage_medium[]" value="<?php echo $old_medium; ?>" checked>&nbsp;<?php echo $old_medium; ?>
                &nbsp;&nbsp;&nbsp;
                <?php
                }
            }
        ?>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['description']; ?></label>
        <textarea id="description" class="stdwidth" name="description" rows="4"><?php echo htmlspecialchars($usage_data["description"]); ?></textarea>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['usage_date']; ?></label>
        <select name="usage_date_day" class="SearchWidth" style="width:98px;">
        <?php
        for($n = 1; $n <= 31; $n++)
            {
            $m = str_pad($n, 2, '0', STR_PAD_LEFT);
            ?>
            <option <?php if($n == substr($usage_data['usage_date'], 8, 2)) { ?>selected<?php } ?> value="<?php echo $m; ?>"><?php echo $m;?></option><?php
            }
        ?>
        </select>

        <select name="usage_date_month" class="SearchWidth" style="width:98px;">
        <?php
        for($n = 1; $n <= 12; $n++)
            {
            $m = str_pad($n, 2, '0', STR_PAD_LEFT);
            ?>
            <option <?php if($n == substr($usage_data['usage_date'], 5, 2)) { ?>selected<?php } ?> value="<?php echo $m; ?>"><?php echo $lang['months'][$n - 1]; ?></option>
            <?php
            }
        ?>
        </select>
        
        <select name="usage_date_year" class="SearchWidth" style="width:98px;">
        <?php
        $y = date('Y') + 30;
        for($n = $minyear; $n <= $y; $n++)
            {
            ?><option <?php if($n == substr($usage_data['usage_date'], 0, 4)) { ?>selected<?php } ?>><?php echo $n; ?></option>
            <?php
            }
        ?>
        </select>
        <div class="clearerleft"></div>
    </div>

    <div class="QuestionSubmit">
        <label for="buttons"></label>          
        <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang['save']; ?>&nbsp;&nbsp;" />
    </div>
    </form>
</div>
<?php       
include "../../../include/footer.php";
