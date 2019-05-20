<?php
function HookNewsHomeHomebeforepanels()
	{
	global $lang,$site_text,$baseurl;
	include_once dirname(__FILE__)."/../inc/news_functions.php";
	$recent = 3;
	$findtext = "";
	$news = get_news_headlines("",$recent,"");
	$results=count($news);
   	?>

	<div id="NewsPanel">
    	<h2><span class="fa fa-newspaper-o"></span>&nbsp;<?php echo $lang['title']; ?></h2>
		<?php
			if($results > 0)
			{
			for($n = 0; ($n < $results); $n++)
				{
				?>
				<p><?php echo LINK_CARET; ?><a href="<?php echo $baseurl; ?>/plugins/news/pages/news.php?ref=<?php echo $news[$n]['ref']; ?>"><?php echo $news[$n]['title']; ?></a></p>
				<?php
				}
			}
		else
			{
			echo $lang['news_nonewmessages'];
			}
		?>
	</div>
	<?php
	}

