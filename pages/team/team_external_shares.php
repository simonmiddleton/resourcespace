<?php 
include '../../include/db.php';
include '../../include/authenticate.php';
if(!checkperm('ex'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Permission denied!');
    }

$ajax              = ('true' == getval('ajax', '') ? true : false);
$delete_access_key = getvalescaped('delete_access_key', '');

// Process access key deletion
if($ajax && '' != $delete_access_key && enforcePostRequest($ajax))
    {
    $resource   = getvalescaped('resource', '');
    $collection = getvalescaped('collection', '');
    $response   = array(
        'success' => false
    );

    if('' != $resource)
        {
        delete_resource_access_key($resource, $delete_access_key);
        $response['success'] = true;
        }
    
    if('' != $collection)
        {
        delete_collection_access_key($collection, $delete_access_key);
        $response['success'] = true;
        }

    exit(json_encode($response));
    }

$external_access_keys_query = 
"     SELECT access_key,
             resource,
             collection,
             group_concat(DISTINCT user ORDER BY user SEPARATOR ', ') AS users,
             group_concat(DISTINCT email ORDER BY email SEPARATOR ', ') AS emails,
             max(date) AS maxdate,
             max(lastused) AS lastused,
             access,
             expires,
             usergroup
        FROM external_access_keys
    GROUP BY access_key
    ORDER BY date
";
$external_shares = sql_query($external_access_keys_query);

include '../../include/header.php';
?>
<div class="BasicsBox">
    <?php
        $links_trail = array(
            array(
                'title' => $lang["teamcentre"],
                'href'  => $baseurl_short . "pages/team/team_home.php"
            ),
            array(
                'title' => $lang["manage_external_shares"],
                'help'  => "user/sharing-resources"
            )
        );
     
        renderBreadcrumbs($links_trail);
    ?>
        <div class="Listview">
            <table class="ListviewStyle" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr class="ListviewTitleStyle">
                        <td><?php echo $lang['accesskey']; ?></td>
                        <td><?php echo $lang['type']; ?></td>
                        <td><?php echo $lang['sharedby']; ?></td>
                        <td><?php echo $lang['sharedwith']; ?></td>
                        <td><?php echo $lang['lastupdated']; ?></td>
                        <td><?php echo $lang['lastused']; ?></td>
                        <td><?php echo $lang['expires']; ?></td>
                        <td><?php echo $lang['access']; ?></td>
                        <td><div class="ListTools"><?php echo $lang['tools']; ?></div></td>
                    </tr>
                    <?php
                    foreach($external_shares as $external_share)
                        {
                        render_access_key_tr($external_share);
                        }
                    ?>
                </tbody>
            </table>
        </div><!-- end of Listview -->


</div><!-- end of BasicBox -->
<?php
include '../../include/footer.php';
