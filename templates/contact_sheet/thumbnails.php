<style>
<?php
if(isset($refnumberfontsize))
    {
    ?>
    page { font-size: <?php echo $refnumberfontsize; ?>px; }
    <?php
    }

if(isset($titlefontsize))
    {
    ?>
    #pageTitle { font-size: <?php echo $titlefontsize; ?>px; }
    <?php
    }
    ?>

#logo { height: 50px; max-width: 100%; }
.centeredText { text-align: center; }

.resourceContainer { vertical-align: top; }
.resourcePreview { width: <?php echo $column_width; ?>px; }
.contactsheet_textbold {font-weight: bold;}
</style>
<page backtop="25mm" backbottom="25mm">
<?php
if(isset($contactsheet_header))
    {
    ?>
    <page_header>
        <table cellspacing="0" style="width: 100%;">
            <tr>
            	<?php
            	if($contact_sheet_include_applicationname)
                	{
                	?>
                	<td style="width: 60%;">
                		<h1><?php echo $applicationname; ?></h1>
                	</td>
                	<?php
                	}
            if(isset($add_contactsheet_logo))
                {
                ?>
                <td style="width: 40%;" align=right>
                    <img id="logo" src="<?php echo $contact_sheet_logo; ?>" alt="Logo" <?php if(isset($contact_sheet_logo_resize) && $contact_sheet_logo_resize){ ?> style="width:100%;height:auto;"<?php } ?>>
                </td>
                <?php
                }
                ?>
            </tr>
        </table>
        <hr>
    </page_header>
    <?php
    }

if(isset($contact_sheet_footer))
    {
    ?>
    <page_footer>
        <hr>
        <table style="width: 100%;">
            <tr>
                <td class="centeredText" style="width: 90%">
                    <span><?php echo $lang['contact_sheet_footer_address']; ?></span>
                    <p><?php echo $lang['contact_sheet_footer_copyright']; ?></p>
                </td>
                <td style="text-align: right; width: 10%">[[page_cu]] of [[page_nb]]</td>
            </tr>
        </table>
    </page_footer>
    <?php
    }
    ?>


    <!-- Real content starts here -->
    <h3 id="pageTitle"><?php echo $title; ?></h3>

<table>
<tbody>
<?php
global $contact_sheet_field_name, $contact_sheet_field_name_bold;

$row    = 1;
$column = 0;

$max_rows = ceil(count($resources) / $columns);

foreach($resources as $resource_ref => $resource)
    {
    if(0 == $column)
        {
        ?>
        <tr>
        <?php
        }
        ?>

    <td class="resourceContainer" width="<?php echo $column_width; ?>">
    <?php
    if($config_sheetthumb_include_ref)
        {
        ?>
        <span class="resourceRef"><?php echo $resource_ref; ?></span><br>
        <?php
        }

    if(!$contact_sheet_metadata_under_thumbnail)
    	{
		foreach($resource['contact_sheet_fields'] as $contact_sheet_field)
			{
			?>
			<span><?php echo htmlspecialchars($contact_sheet_field); ?></span><br>
			<?php
			}
		}

    if(isset($contact_sheet_add_link))
        {
        // IMPORTANT: having space between a tag and img creates some weird visual lines (HTML2PDF issues maybe?!)
        ?>
        <a target="_blank" href="<?php echo $baseurl; ?>/?r=<?php echo $resource_ref; ?>"><img class="resourcePreview" src="<?php echo $resource['preview_src']; ?>" alt="Resource Preview"></a>
        <?php
        }
    else
        {
        ?>
        <img class="resourcePreview" src="<?php echo $resource['preview_src']; ?>" alt="Resource Preview">
        <?php
        }
    
    if($contact_sheet_metadata_under_thumbnail)
    	{
		foreach($resource['contact_sheet_fields'] as $contact_sheet_field)
			{
			if($contact_sheet_field_name && $contact_sheet_field_name_bold)
                {
                $contact_sheet_field=explode(': ', $contact_sheet_field);
                ?>
                <span><b><?php echo htmlspecialchars($contact_sheet_field[0]); ?></b>: <?php echo htmlspecialchars($contact_sheet_field[1]); ?></span><br>
                <?php
                }
            else
                {
                ?>
                <span><?php echo htmlspecialchars($contact_sheet_field); ?></span><br>
                <?php
                }
			}
		}
        ?>
    </td>

    <?php
    $column++;

    // We've reached the number of columns for this row
    if($column == $columns)
        {
        ?>
        </tr>
        <?php
        $row++;
        $column = 0;
        }
    }

    if($row == $max_rows)
        {
        ?>
        </tr>
        <?php
        }
    ?>
</tbody>
</table>

</page>
