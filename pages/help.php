<?php
include "../include/db.php";

include "../include/authenticate.php";

$section=getvalescaped("section","");
$page   =getvalescaped("page"   ,"");

include "../include/header.php";
?>

<div class="BasicsBox"> 

<?php
if(!hook("replacehelp"))
    {

    $onClick = 'return CentralSpaceLoad(this, true);';
    if($help_modal)
        {
        $onClick = 'return ModalLoad(this, true);';
        }

if('' == $section)
    {
    ?>
    <div class="HelpHeader">
    <?php
    if($help_modal)
        {
        ?>
        <div class="backtoresults">
            <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
        </div>
        <?php
        }
        ?>
        <h1><?php echo $lang['helpandadvice']; ?></h1>
    </div>

    <p>
        <?php 
        if ($page != "")
            {
            // Build link for the specified KnowlegeBase page
            echo '<iframe src="https://www.resourcespace.com/knowledge-base/' . $page . '?from_rs=true" style="width:1235px;height:600px;border:none;margin:-20px;" id="knowledge_base" />';
            }
        else
            {
            echo text("introtext");
            }
        ?>
    </p>

    <div class="VerticalNav">
    <ul>
    <?php
    $sections=get_section_list("help");
    for ($n=0;$n<count($sections);$n++)
        {
        ?>
        <li>
            <a onClick="<?php echo $onClick; ?>"
               href="<?php echo $baseurl_short?>pages/help.php?section=<?php echo urlencode($sections[$n]); ?>"><?php echo htmlspecialchars($sections[$n]); ?></a>
        </li>
        <?php
        }
    ?>
    </ul>
    </div>

    <?php 
    }
else
    {
    ?>
    <h1><?php echo htmlspecialchars($section)?></h1>
    <p><?php echo text($section)?></p>
    <p><a onClick="<?php echo $onClick; ?>" href="<?php echo $baseurl_short?>pages/help.php"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtohelphome"]?></a></p>
    <?php
    }
} // end hook replacehelp?>


</div>

<?php
include "../include/footer.php";
?>
