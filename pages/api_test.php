<?php
include "../include/db.php";
include "../include/authenticate.php";
include_once "../include/image_processing.php";
include_once "../include/api_functions.php";
include_once "../include/api_bindings.php";
include_once "../include/login_functions.php";
include "../include/header.php";

if (!$enable_remote_apis) {exit("API not enabled.");}
if (!checkperm("a")) {exit("Access denied");}

$api_function=getvalescaped("api_function","");

if ($api_function!="")
    {
    $fct = new ReflectionFunction("api_" . $api_function);
    $paramcount=$fct->getNumberOfParameters();
    $rparamcount=$fct->getNumberOfRequiredParameters();
    $fct_params = $fct->getParameters();
    }
    
$output="";
if (getval("submitting","")!="" && $api_function!="")
    {
    $output="";

    $query="function=" . $api_function;
    foreach($fct_params as $fparam)
        {
        $param_name = $fparam->getName();
        $param_val = trim(getval($param_name, ""));

        if($fparam->isOptional() && $param_val == "")
            {
            continue;
            }

        $query .= "&{$param_name}=" . urlencode($param_val);
        }
    $output.="Query: " . $query . "\n\n";
    $output.="Response:\n";
    $output.=execute_api_call($query,true);
    
    }

?>


<div class="RecordBox">
<div class="RecordPanel">
<div class="Title"><?php echo $lang['api-test-tool']; ?></div>

<p><?php echo $lang["api-help"];render_help_link("api"); ?></p>

<form id="api-form" method="post" action="<?php echo $baseurl_short?>pages/api_test.php" onSubmit="return CentralSpacePost(this);">
<?php generateFormToken("api-form"); ?>

<div class="Question">
<label><?php echo $lang["api-function"] ?></label>
<select class="stdwidth" name="api_function" onChange="CentralSpacePost(document.getElementById('api-form'));">
    <option value=""><?php echo $lang["select"] ?></option>
    <?php
    # Allow selection from built in functions
    $functions=get_defined_functions();$functions=$functions["user"];asort($functions);
    foreach ($functions as $function)
        {
            if (substr($function,0,4)=="api_")
                {
                ?>
                <option <?php if ($function=="api_" . $api_function) {echo " selected";} ?>><?php echo substr($function,4) ?></option>
                <?php
                }
        }
    ?>
    
</select>
<?php if ($api_function!="") { ?>&nbsp;&nbsp;<a target="_blank" href="https://www.resourcespace.com/knowledge-base/api/<?php echo $api_function ?>"><?php echo $lang["api-view-documentation"] ?></a><?php } ?>
</div>

<?php
if ($api_function!="")
    {
    foreach($fct_params as $fparam)
        {
        $param_name = $fparam->getName();

        if($fparam->isOptional())
            {
            $required = '';
            $required_attr = '';

            $send_param = getval("send_{$param_name}", '') === 'yes';
            $send_param_input = sprintf(
                '<input type="checkbox" name="send_%s" value="yes" %s onchange="ToggleSendParam(this);">',
                $param_name,
                ($send_param ? 'checked' : '')
            );
            $disabled_attr = ($send_param ? '' : 'disabled');
            }
        else
            {
            $required = ' *';
            $required_attr = 'required';
            $send_param_input = '';
            $disabled_attr = '';
            }
        ?>
        <div class="Question">
            <label><?php echo $send_param_input . $param_name . $required; ?></label>
            <input type="text"
                   name="<?php echo $param_name; ?>"
                   class="stdwidth"
                   value="<?php echo htmlspecialchars(getval($param_name, "")); ?>"
                   <?php echo "{$required_attr} {$disabled_attr}"; ?>>
        </div>
        <?php
        }
    }
?>
<div class="QuestionSubmit">
    <label></label>
    <input type="hidden" name="submitting" value="" id="submitting" />
    <input type="submit" name="submit" value="Submit" onclick="document.getElementById('submitting').value='true';" />
</div>

</form>

<?php if ($output!="") { 
    //rebuild params for output to include encoding if needed
    $query="function=" . $api_function;
    foreach($fct_params as $fparam)
        {
        $param_name = $fparam->getName();
        $param_val = trim(getval($param_name, ""));

        if($fparam->isOptional() && $param_val == "")
            {
            continue;
            }

        strpos(urlencode($param_val), '%') === false?$query .= '&' . $param_name . '=' . $param_val:$query .= '&' . $param_name . '=" . urlencode("' . $param_val . '") . "';
        }
    ?>
<pre style=" white-space: pre-wrap;word-wrap: break-word; width:100%;background-color:black;color:white;padding:5px;border-left:10px solid #666;"><?php echo htmlspecialchars($output) ?></pre>


<br /><br />
<h2><?php echo $lang["api-php-code"] ?></h2>
<p><?php echo $lang["api-php-help"] ?></p>

<style>.codecomment {color:#090;}</style>
<pre style=" white-space: pre-wrap;word-wrap: break-word; width:100%;background-color:white;color:black;padding:10px;">
&lt;?php

<span class="codecomment">// Set the private API key for the user (from the user account page) and the user we're accessing the system as.</span>
$private_key="<?php echo get_api_key($userref) ?>";
$user=<?php echo strpos(urlencode($username), '%') === false?'"' . $username . '"':'urlencode("' . $username . '")'; ?>;

<span class="codecomment">// Formulate the query</span>
$query="user=" . $user . "&amp;<?php echo substr($query, -4)!=' . "'?htmlspecialchars($query) . '"':substr(htmlspecialchars($query), 0, -9); ?>;

<span class="codecomment">// Sign the query using the private key</span>
$sign=hash("sha256",$private_key . $query);

<span class="codecomment">// Make the request and output the JSON results.</span>
$results=json_decode(file_get_contents("<?php echo htmlspecialchars($baseurl) ?>/api/?" . $query . "&sign=" . $sign));
print_r($results);
</pre>

<?php } ?>


</div>
</div>
<script>
function ToggleSendParam(el)
    {
    console.debug('ToggleSendParam(%o)', el);

    var send_param = jQuery(el);
    var param_name = send_param.attr('name').replace('send_', '');
    var param_input = jQuery('input[name="' + param_name + '"]').not('[required]');

    console.debug('param_name = %o', param_name);
    console.debug('param_input = %o', param_input);

    if(param_input.length == 0)
        {
        console.error('Unable to find an input with name %o', param_name);
        return false;
        }

    if(send_param.is(':checked'))
        {
        param_input.prop('disabled', false);
        }
    else
        {
        param_input.prop('disabled', true);
        }

    return true;
    }
</script>
<?php
include "../include/footer.php";