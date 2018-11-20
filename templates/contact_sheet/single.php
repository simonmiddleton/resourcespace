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
.contactsheet_textbold {font-weight: bold;}
</style>
<?php
global $contact_sheet_field_name, $contact_sheet_field_name_bold;
foreach($resources as $resource_ref => $resource)
    {
    ?>
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

    <table style="width: 100%;">
        <tr>
            <td style="width: 15%"></td>
            <td style="width: 70%; height: <?php echo $available_height * 0.5; ?>px;">
            <?php
            $image_dimensions = calculate_image_dimensions($resource['preview_src'], $available_width * 0.7, $available_height * 0.5);

            if(isset($contact_sheet_add_link))
                {
                // IMPORTANT: having space between a tag and img creates some weird visual lines (HTML2PDF issues maybe?!)
                ?>
                <a target="_blank" href="<?php echo $baseurl; ?>/?r=<?php echo $resource_ref; ?>"><img style="margin-left: <?php echo $image_dimensions['y_offset']; ?>px;" src="<?php echo $resource['preview_src']; ?>" width="<?php echo $image_dimensions['new_width']; ?>" height="<?php echo $image_dimensions['new_height']; ?>" alt="Resource Preview"></a>
                <?php
                }
            else
                {
                ?>
                <img style="margin-left: <?php echo $image_dimensions['y_offset']; ?>px;" src="<?php echo $resource['preview_src']; ?>" width="<?php echo $image_dimensions['new_width']; ?>" height="<?php echo $image_dimensions['new_height']; ?>" alt="Resource Preview">
                <?php
                }
                ?>
            </td>
            <td style="width: 15%"></td>
        </tr>
        </table>
    <?php
    if($config_sheetthumb_include_ref)
        {
        ?>
        <p><?php echo $resource_ref; ?></p>
        <?php
        }

    foreach($resource['contact_sheet_fields'] as $contact_sheet_field)
        {
        if($contact_sheet_field_name && $contact_sheet_field_name_bold)
            {
            $contact_sheet_field=explode(': ', $contact_sheet_field);
            ?>
            <p><b><?php echo htmlspecialchars($contact_sheet_field[0]); ?></b>: <?php echo htmlspecialchars($contact_sheet_field[1]); ?></p>
            <?php
            }
        else
            {
            ?>
            <p><?php echo htmlspecialchars($contact_sheet_field); ?></p>
            <?php
            }
        }
        ?>
</page>
    <?php
    }
    ?>
