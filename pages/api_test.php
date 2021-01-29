<?php
include "../include/db.php";

include "../include/authenticate.php";
include_once "../include/node_functions.php";
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
        $required = ($fparam->isOptional() ? "" : " *");
        $required_attr = ($fparam->isOptional() ? "" : "required");
        ?>
        <div class="Question">
            <label><?php echo $param_name; echo $required; ?></label>
            <input type="text" name="<?php echo $param_name; ?>" class="stdwidth" value="<?php echo htmlspecialchars(getval($param_name, "")); ?>" <?php echo $required_attr; ?>>
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

<?php if ($output!="") { ?>
<pre style=" white-space: pre-wrap;word-wrap: break-word; width:100%;background-color:black;color:white;padding:5px;border-left:10px solid #666;"><?php echo htmlspecialchars($output) ?></pre>


<br /><br />
<h2><?php echo $lang["api-php-code"] ?></h2>
<p><?php echo $lang["api-php-help"] ?></p>

<style>.codecomment {color:#090;}</style>
<pre style=" white-space: pre-wrap;word-wrap: break-word; width:100%;background-color:white;color:black;padding:10px;">
&lt;?php

<span class="codecomment">// Set the private API key for the user (from the user account page) and the user we're accessing the system as.</span>
$private_key="<?php echo get_api_key($userref) ?>";
$user="<?php echo $username ?>";

<span class="codecomment">// Formulate the query</span>
$query="user=" . $user . "&amp;<?php echo htmlspecialchars($query) ?>";

<span class="codecomment">// Sign the query using the private key</span>
$sign=hash("sha256",$private_key . $query);

<span class="codecomment">// Make the request and output the JSON results.</span>
$results=json_decode(file_get_contents("<?php echo htmlspecialchars($baseurl) ?>/api/?" . $query . "&sign=" . $sign));
print_r($results);
</pre>

<?php } ?>


</div>
</div>
<?php
include "../include/footer.php";
?>
