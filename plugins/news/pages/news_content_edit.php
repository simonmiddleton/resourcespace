<?php
/**
 * Edit news items page 
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include dirname(__FILE__)."/../../../include/db.php";
include_once dirname(__FILE__)."/../../../include/general.php";
include dirname(__FILE__)."/../../../include/authenticate.php";if (!checkperm("o")) {exit ("Permission denied.");}
include_once dirname(__FILE__)."/../inc/news_functions.php";

$ref=getvalescaped("ref","",true);
$offset=getvalescaped("offset",0);
$findtext=getvalescaped("findtext","");

$date=getval("date",date("Y-m-d H:i:s"));
$title=getvalescaped("title",0);
$body=getvalescaped("body",0);

# get ref value from database, unless it is set to new 
if (getval("ref","")=="new"){$createnews=true;} else {$news=get_news($ref,"",""); $createnews=false;}

if (getval("save","")!="" && enforcePostRequest(false))
	{
	# Save news
	If ($createnews) {add_news($date,$title,$body);}
	else {update_news($ref,$date,$title,$body);}
	redirect("plugins/news/pages/news_edit.php?findtext=".$findtext."&offset=".$offset);
	}
	
# Fetch news data
$news=get_news($ref,"","");
include dirname(__FILE__)."/../../../include/header.php";
?>

<p id="EditNewsBack">
    <a href="news_edit.php?offset=<?php echo $offset?>&findtext=<?php echo $findtext?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["news_manage"]?></a>
</p>

<div class="BasicsBox">
    <h1><?php echo $lang["news_edit"]?></h1>

    <form method=post id="mainform">
        <?php generateFormToken("mainform"); ?>

        <input type=hidden name=name value="<?php echo $ref?>">

        <div class="Question">
            <label><?php echo $lang["date"]?></label>
            <input name="date" class="stdwidth" type="text" value="<?php echo $createnews ? date("Y-m-d H:i:s") : $news[0]["date"]; ?>">
        </div>

        <div class="clearerleft"> </div>

        <div class="Question">
            <label><?php echo $lang["news_headline"];?></label>
            <input name="title" class="stdwidth" type="text" value="<?php echo $createnews ? $lang["news_addtitle"] : $news[0]["title"]; ?>">
        </div>

        <div class="clearerleft"> </div>

        <div class="Question">
            <label><?php echo $lang["news_body"]?></label>
            <textarea name="body" class="stdwidth" rows="15" cols="50"><?php if (!$createnews) { echo htmlspecialchars($news[0]["body"]); } ?></textarea>
        </div>

        <div class="clearerleft"> </div>

        <div class="QuestionSubmit">
            <label for="buttons"> </label>
            <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
        </div>
    </form>
</div>

<?php		
include dirname(__FILE__)."/../../../include/footer.php";