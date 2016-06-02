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

#logo {
    height: 50px;
    max-width: 100%;
}

.centeredText {
    text-align: center;
}

.resourceContainer { border: 1px solid black; }
.resourceContainer img { width: <?php echo $column_width; ?>px; }
</style>
<page backtop="25mm" backbottom="25mm">
<?php
if(isset($contactsheet_header))
    {
    ?>
    <page_header>
        <table cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 60%;"><h1><?php echo $applicationname; ?></h1></td>
            <?php
            if(isset($add_contactsheet_logo))
                {
                ?>
                <td style="width: 40%;" align=right>
                    <img id="logo" src="<?php echo $contact_sheet_logo; ?>" alt="Logo">
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
                    <span>XXX MAIN STREET, CITY, ABC 123 - TEL: (111) 000-8888 - FAX: (000) 111-9999</span>
                    <p>&#0169; ReourceSpace. All Rights Reserved.</p>
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
            <tr>
        <?php
        $current_column = 1;
        $current_row    = 1;
        $max_rows       = ceil(count($resources) / $columns);

        foreach($resources as $resource)
            {
            ?>

                <td class="resourceContainer">
                <?php
                foreach($resource['contact_sheet_fields'] as $contact_sheet_field)
                    {
                    ?>
                    <p><?php echo $contact_sheet_field; ?></p>
                    <?php
                    }
                    ?>
                    <img src="<?php echo $contact_sheet_logo; ?>" alt="Logo">
                </td>

            <?php
            if($current_column == $columns)
                {
                $current_column = 1;
                ?>
                </tr>
                <?php
                if($current_row < $max_rows)
                    {
                    ?>
                    <tr>
                    <?php
                    }
                }
            $current_column++;
            $current_row++;
            }
            ?>
        </tbody>
    </table>

</page>