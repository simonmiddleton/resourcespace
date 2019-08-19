<?php
/**
 * Plugins management interface (part of team center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 * @author Brian Adams <wreality@gmail.com>
 * @todo Link to wiki page for config.php activated plugins. (Help text)
 * @todo Fortify plugin delete code
 * @todo Update plugin DB if uploaded plugin is installed (upgrade functionality)
 */
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }

if (isset($_REQUEST['activate']) && enforcePostRequest(false))
   {
   $inst_name = trim(getvalescaped('activate',''), '#');
   if ($inst_name != '' && !in_array($inst_name,$disabled_plugins))
      {
      activate_plugin($inst_name);   
      }
   redirect($baseurl_short.'pages/team/team_plugins.php');    # Redirect back to the plugin page so plugin is actually activated. 
   }
elseif (isset($_REQUEST['deactivate']) && enforcePostRequest(false))
   { # Deactivate a plugin
   # Strip the leading hash mark added by javascript.
   $remove_name = trim(getvalescaped('deactivate',''), "#");
   if ($remove_name!='')
      {
       deactivate_plugin($remove_name); 
      }
   redirect($baseurl_short.'pages/team/team_plugins.php');    # Redirect back to the plugin page so plugin is actually deactivated.
   }
 elseif (isset($_REQUEST['purge']) && enforcePostRequest(false))
   { # Purge a plugin's configuration (if stored in DB)
   # Strip the leading hash mark added by javascript.
   $purge_name = trim(getvalescaped('purge',''), '#');
   if ($purge_name!='')
      {
      purge_plugin_config($purge_name);
      }
   }
elseif ($enable_plugin_upload && isset($_REQUEST['submit']) && enforcePostRequest(false))
   { # Upload a plugin .rsp file. 
   if (($_FILES['pfile']['error'] == 0) && (pathinfo($_FILES['pfile']['name'], PATHINFO_EXTENSION)=='rsp'))
      {
      require "../../lib/pcltar/pcltar.lib.php";

      # Create tmp folder if not existing
      # Since get_temp_dir() method does this, omit: if (!file_exists(dirname(__FILE__).'/../../filestore/tmp')) {mkdir(dirname(__FILE__).'/../../filestore/tmp',0777);}

      $tmp_file = get_temp_dir() . '/'.basename($_FILES['pfile']['name'].'.tgz');
      if(move_uploaded_file($_FILES['pfile']['tmp_name'], $tmp_file)==true)
         {
         $rejected = false;
         $filelist = PclTarList($tmp_file);
         if(is_array($filelist))
            {
            foreach($filelist as $key=>$value)
               { # Loop through the file list to create an array we can use php's functions with.
               $filearray[] = $value['filename'];
               }
            # Some security checks.
            foreach ($filearray as $filename)
               {
               if ($filename[0]=='/' || $filename[0] =='\\')
                  { # Paths are absolute.  Reject the plugin.
                    $rejected = true;
                    $rej_reason = $lang['plugins-rejrootpath'];
                    break; 
                  }
               }
            if (array_search('..', $filearray)!==false) 
               {# Archive may contain ../ directories (Security risk)
               $rejected = true;
               $rej_reason = $lang['plugins-rejparentpath'];
               }
            if(!$rejected)
               {
               # Locate the plugin name based on highest directory in structure.
               # This loop will also look for the .yaml file (to avoid having to loop twice).
               $exp_path = explode('/',$filearray[0]);
               $yaml_index = false;
               $u_plugin_name = $exp_path[0];
               foreach ($filearray as $key=>$value)
                  {
                  $test = explode('/',$value);
                  if ($u_plugin_name != $test[0])
                     {
                     $rejected = true;
                     $rej_reason = $lang['plugins-rejmultpath'];
                     break;
                     }
                  # TODO: This should be a regex to make sure the file is in the right position (<pluginname>/<pluginname>.yaml)
                  if (strpos($value,$u_plugin_name.'.yaml')!==false)
                     {
                     $yaml_index = $key;
                     }
                  }
               # TODO: We should extract the yaml file if it exists and validate it.
               if ($yaml_index===false)
                  {
                  $rejected = true;
                  $rej_reason = $lang['plugins-rejmetadata'];
                  }
               if (!$rejected)
		  {
                  # Uploaded plugins live in the filestore folder.		  
		  $phar = new PharData($tmp_file);
                  try {
		      $phar->extractTo($storagedir . "/plugins/", null, true);
		      activate_plugin($u_plugin_name);
                      redirect($baseurl_short.'pages/team/team_plugins.php');
		      }
		  catch (Exception $e) 
			{
			$rejected = true;
                        $rej_reason = $lang['plugins-rejarchprob'];
			}
		  }
                  
               }
            }
         else 
            {
            $rejected = true;
            $rej_reason = $lang['plugins-rejfileprob'];
            }	 
         }
      }
   else 
      {
      $rejected = true;
      $rej_reason  = $lang['plugins-rejfileprob'];
      }
   }

 $inst_plugins = sql_query('SELECT name, config_url, descrip, author, ' .
    'inst_version, update_url, info_url, enabled_groups, disable_group_select ' .
    'FROM plugins WHERE inst_version>=0 order by name');
/**
 * Ad hoc function for array_walk through plugins array.
 * 
 * When called from array_walk, steps through each element of the installed 
 * plugins array and checks to see if it was installed via config.php (legacy).
 * If so, sets an addition array key for template to display the link correctly.
 * 
 * @param array $i_plugin Plugin array element. 
 * @param string $key Array key. 
 */
function legacy_check(&$i_plugin, $key)
   {
   global $legacy_plugins;
   if (array_search($i_plugin['name'], $legacy_plugins)!==false)
      {
      $i_plugin['legacy_inst'] = true;
      }
   }

for ($n=0;$n<count($inst_plugins)-1;$n++)
    {
    # Check if group access is permitted by YAML file. Needed because plugin may have been enabled before this development)
    $plugin_yaml_path = get_plugin_path($inst_plugins[$n]["name"])."/".$inst_plugins[$n]["name"].".yaml";
    $py = get_plugin_yaml($plugin_yaml_path, false);  
    $inst_plugins[$n]['disable_group_select'] = $py['disable_group_select'];
    }
        
array_walk($inst_plugins, 'legacy_check');
# Build an array of available plugins.
$plugins_avail = array();

function load_plugins($plugins_dir)
 {
 global $plugins_avail;
 
 $dirh = opendir($plugins_dir);
 while (false !== ($file = readdir($dirh))) 
    {
    if (is_dir($plugins_dir.$file)&&$file[0]!='.')
       {
       #Check if the plugin is already activated.
       $status = sql_query('SELECT inst_version, config FROM plugins WHERE name="'.$file.'"');
       if ((count($status)==0) || ($status[0]['inst_version']==null))
          {
          # Look for a <pluginname>.yaml file.
          $plugin_yaml = get_plugin_yaml($plugins_dir.$file.'/'.$file.'.yaml', false);
          foreach ($plugin_yaml as $key=>$value)
             {
             $plugins_avail[$file][$key] = $value ;
             }
          $plugins_avail[$file]['config']=(sql_value("SELECT config AS value FROM plugins WHERE name='$file'",'') != '');
          # If no yaml, or yaml file but no description present, 
          # attempt to read an 'about.txt' file
          if ($plugins_avail[$file]["desc"]=="")
             {
             $about=$plugins_dir.$file.'/about.txt';
             if (file_exists($about)) 
                {
                $plugins_avail[$file]["desc"]=substr(file_get_contents($about),0,95) . "...";
                }
             }
          }        
       }
    }
 closedir($dirh);
 }

load_plugins(dirname(__FILE__) . '/../../plugins/');
if(!file_exists($storagedir . '/plugins/'))
   {
   mkdir($storagedir . '/plugins/');
   }
load_plugins($storagedir . '/plugins/');

ksort ($plugins_avail);


// Search functionality
$searching          = ((getval("searching", "") != "" && getval("clear_search", "") == "") ? true : false);
$find               = getval("find", "");
$search_placeholder = ($searching ? $find : $lang['plugins-search-plugin-placeholder']);

/**
* Find plugin which contains the searched text in the name/ description
* 
* @param array   $plugin  Plugin data array (either the installed/ available version)
* @param string  $search  Searched text to find the plugin
* 
* @return boolean Returns TRUE if plugin data matches the search (mostly name and description), FALSE otherwise
*/
function findPluginFromSearch(array $plugin, $search)
    {
    // If we are not searching for anything in particular then 
    if(trim($search) == "")
        {
        return true;
        }

    if(isset($plugin["name"]) && strpos($plugin["name"], $search) !== false)
        {
        return true;
        }

    if(isset($plugin["descrip"]) && strpos($plugin["descrip"], $search) !== false)
        {
        return true;
        }

    if(isset($plugin["desc"]) && strpos($plugin["desc"], $search) !== false)
        {
        return true;
        }

    return false;
    }

/*
 * Start Plugins Page Content
 */
include "../../include/header.php"; ?>
<script type="text/javascript">
(function($) { 
   $(function() {
      function actionPost(action, value){
      $('input#anc-input').attr({
      name: action,
      value: value});
      jQuery('form#anc-post').submit();
      }
      $('#BasicsBox').ready(function() {
         $('a.p-deactivate').click(function() {
         actionPost('deactivate', $(this).attr('href'));
         return false;
         });
         $('a.p-activate').click(function() {
         var pname = $(this).attr('href');
         actionPost('activate', $(this).attr('href'));
         return false;
         });
         $('a.p-purge').click(function() {
         actionPost('purge', $(this).attr('href'));
         return false;						  
         });
         $('a.p-delete').click(function() {
         actionPost('delete', $(this).attr('href'));
         return false;
         });
      });
   });
})(jQuery);
</script>
<div class="BasicsBox"> 
<h1><?php echo $lang["pluginmanager"]; ?>
    <form id="SearchPlugins" method="get">
        <input type="text" name="find" placeholder="<?php echo htmlspecialchars($search_placeholder); ?>">
        <input type="submit" name="searching" value="<?php echo htmlspecialchars($lang["searchbutton"]); ?>">
    <?php
    if($searching)
        {
        ?>
        <input type="submit" name="clear_search" value="<?php echo htmlspecialchars($lang["clearbutton"]); ?>">
        <?php
        }
        ?>
    </form>
</h1>
<p><?php echo $lang["plugins-headertext"]; render_help_link('systemadmin/managing_plugins');?></p>
<h2 class="pageline"><?php echo (!$searching ? $lang['plugins-installedheader'] : $lang['plugins-search-results-header']); ?></h2>
<?php hook("before_active_plugin_list");
if($searching)
    {
    $all_plugins = array_merge($inst_plugins, $plugins_avail);
    ?>
    <div class="Listview">
        <table class= "ListviewStyle" cellspacing="0" cellpadding="0" border="0">
            <thead>
                <tr class="ListviewTitleStyle">
                    <td><?php echo $lang['name']; ?></td>
                    <td><?php echo $lang['description']; ?></td>
                    <td><?php echo $lang['plugins-author']; ?></td>
                    <td><?php echo $lang['plugins-version']; ?></td>
                    <?php hook('additional_plugin_columns'); ?>
                    <td><div class="ListTools"><?php echo $lang['tools']; ?></div></td>
                </tr>
            </thead>
            <tbody>
    <?php
    foreach($all_plugins as $plugin)
        {
        if(!findPluginFromSearch($plugin, $find))
            {
            continue;
            }

        // Plugin description key is different if plugin is installed (desc|descrip)
        $plugin_description = (isset($plugin["desc"]) ? $plugin["desc"] : "");
        $plugin_description = (isset($plugin["descrip"]) ? $plugin["descrip"] : $plugin_description);

        // Plugin version key is different if plugin is installed (version|inst_version)
        $plugin_version = (isset($plugin["version"]) ? $plugin["version"] : "");
        $plugin_version = (isset($plugin["inst_version"]) ? $plugin["inst_version"] : $plugin_version);
        /* Make sure that the version number is displayed with at least one decimal place.
        If the version number is 0 the displayed version is $lang["notavailableshort"].
        (E.g. 0 -> (en:)N/A ; 1 -> 1.0 ; 0.92 -> 0.92)
        */
        if($plugin_version == 0)
           {
           $plugin_version = $lang["notavailableshort"];
           }
        else if(sprintf("%.1f", $plugin_version) == $plugin_version)
            {
            $plugin_version = sprintf("%.1f", $plugin_version);
            }

        $activate_or_deactivate_label = (isset($plugin["inst_version"]) ? $lang["plugins-deactivate"] : $lang['plugins-activate']);
        $activate_or_deactivate_class = (isset($plugin["inst_version"]) ? "p-deactivate" : "p-activate");
        $activate_or_deactivate_href  = $plugin['name'];

        if(isset($plugin["legacy_inst"]))
            {
            $activate_or_deactivate_label = $lang["plugins-legacyinst"];
            $activate_or_deactivate_class = "nowrap";
            $activate_or_deactivate_href  = "";
            }
        ?>
            <tr>
                <td><?php echo htmlspecialchars($plugin["name"]); ?></td>
                <td><?php echo htmlspecialchars($plugin_description); ?></td>
                <td><?php echo htmlspecialchars($plugin["author"]); ?></td>
                <td><?php echo htmlspecialchars($plugin_version); ?></td>
                <?php hook('additional_plugin_column_data'); ?>
                <td>
                    <div class="ListTools">
                    <?php
                    if(!in_array($plugin["name"],$disabled_plugins) || isset($plugin["inst_version"]))
                        {?>
                        <a href="#<?php echo $activate_or_deactivate_href; ?>" class="<?php echo $activate_or_deactivate_class; ?>"><?php echo LINK_CARET . $activate_or_deactivate_label; ?></a>
                        <?php
                        }
                    elseif(in_array($plugin["name"],$disabled_plugins))
                        {
                        echo ($disabled_plugins_message != "") ? strip_tags_and_attributes(i18n_get_translated($disabled_plugins_message),array("a"),array("href","target")) : ("<a href='#' >" . LINK_CARET . "&nbsp;" . strip_tags_and_attributes($lang['plugins-disabled-plugin-message'],array("a"),array("href","target")) . "</a>");
                        }
                if ($plugin['info_url']!='')
                   {
                   echo '<a class="nowrap" href="'.$plugin['info_url'].'" target="_blank">' . LINK_CARET . $lang['plugins-moreinfo'].'</a> ';
                   }
                if (!$plugin['disable_group_select'])
                    {
                    echo '<a onClick="return CentralSpaceLoad(this,true);" class="nowrap" href="'.$baseurl_short.'pages/team/team_plugins_groups.php?plugin=' . urlencode($plugin['name']) . '">' . LINK_CARET . $lang['groupaccess'] . ((isset($plugin['enabled_groups']) && trim($plugin['enabled_groups']) != '') ? ' (' . $lang["on"] . ')': '') . '</a> ';
                    $plugin['enabled_groups'] = (isset($plugin['enabled_groups']) ? array($plugin['enabled_groups']) : array());
                    }
                if ($plugin['config_url']!='')        
                   {
                   // Correct path to support plugins that are located in filestore/plugins
                   if(substr($plugin['config_url'],0,8)=="/plugins")
                    {
                    $pluginlugin_config_url = str_replace("/plugins/" . $plugin['name'], get_plugin_path($plugin['name'],true), $plugin['config_url']);
                    }
                   else
                    {$pluginlugin_config_url = $baseurl . $plugin['config_url'];}
                   echo '<a onClick="return CentralSpaceLoad(this,true);" class="nowrap" href="' . $pluginlugin_config_url . '">' . LINK_CARET .$lang['options'].'</a> ';        
                   if (sql_value("SELECT config_json as value from plugins where name='".$plugin['name']."'",'')!='' && function_exists('json_decode'))
                         {
                         echo '<a class="nowrap" href="'.$baseurl_short.'pages/team/team_download_plugin_config.php?pin='.$plugin['name'].'">' . LINK_CARET .$lang['plugins-download'].'</a> ';
                         }
                   }
                ?>
                    </div><!-- End of ListTools -->
                </td>
            </tr>
        <?php
        }
        ?>
            </tbody>
        </table>
        <form id="anc-post" method="post" action="<?php echo $baseurl_short; ?>pages/team/team_plugins.php" >
            <?php generateFormToken("anc_post"); ?>
            <input type="hidden" id="anc-input" name="" value="" />
        </form>
    </div><!-- end of ListView -->
    </div> <!-- end of BasicBox -->
    <?php
    include "../../include/footer.php";
    exit();
    }
    
if (count($inst_plugins)>0)
   { ?>
   <div class="Listview">
   <table class= "ListviewStyle" cellspacing="0" cellpadding="0" border="0">
      <thead>
         <tr class="ListviewTitleStyle">
         <td><?php echo $lang['name']; ?></td>
         <td><?php echo $lang['description']; ?></td>
         <td><?php echo $lang['plugins-author']; ?></td>
         <td><?php echo $lang['plugins-instversion']; ?></td>
         <?php hook('additional_plugin_columns'); ?>
         <td><div class="ListTools"><?php echo $lang['tools']; ?></div></td>
         </tr>
      </thead>
      <tbody>
         <?php 
         foreach ($inst_plugins as $p)
            {
            if($searching && !findPluginFromSearch($p, $find))
                {
                continue;
                }
            # Make sure that the version number is displayed with at least one decimal place.
            # If the version number is 0 the displayed version is $lang["notavailableshort"].
            # (E.g. 0 -> (en:)N/A ; 1 -> 1.0 ; 0.92 -> 0.92)
            if ($p['inst_version']==0)
               {
               $formatted_inst_version = $lang["notavailableshort"];
               }
            else
               {
               if (sprintf("%.1f",$p['inst_version'])==$p['inst_version'])
                  {
                  $formatted_inst_version = sprintf("%.1f",$p['inst_version']);
                  }
               else
                  {
                  $formatted_inst_version = $p['inst_version'];
                  }
               }
            echo '<tr>';
            echo "<td>{$p['name']}</td><td>{$p['descrip']}</td><td>{$p['author']}</td><td>".$formatted_inst_version."</td>";
            hook('additional_plugin_column_data');
            echo '<td><div class="ListTools">';
            if (isset($p['legacy_inst']))
               {
               echo '<a class="nowrap" href="#">' . LINK_CARET . $lang['plugins-legacyinst'].'</a> '; # TODO: Update this link to point to a help page on the wiki
               }
            else
               {
               echo '<a href="#'.$p['name'].'" class="p-deactivate">' .  LINK_CARET . $lang['plugins-deactivate'].'</a> ';
               }
            if ($p['info_url']!='')
               {
               echo '<a class="nowrap" href="'.$p['info_url'].'" target="_blank">' . LINK_CARET . $lang['plugins-moreinfo'].'</a> ';
               }
            if (!$p['disable_group_select'])
                {
                echo '<a onClick="return CentralSpaceLoad(this,true);" class="nowrap" href="'.$baseurl_short.'pages/team/team_plugins_groups.php?plugin=' . urlencode($p['name']) . '">' . LINK_CARET . $lang['groupaccess'] . ((trim($p['enabled_groups']) != '') ? ' (' . $lang["on"] . ')': '')  . '</a> ';
                $p['enabled_groups'] = array($p['enabled_groups']);
                }
            if ($p['config_url']!='')        
               {
               // Correct path to support plugins that are located in filestore/plugins
               if(substr($p['config_url'],0,8)=="/plugins")
                {
                $plugin_config_url = str_replace("/plugins/" . $p['name'], get_plugin_path($p['name'],true), $p['config_url']);
                }
               else
                {$plugin_config_url = $baseurl . $p['config_url'];}
               echo '<a onClick="return CentralSpaceLoad(this,true);" class="nowrap" href="' . $plugin_config_url . '">' . LINK_CARET .$lang['options'].'</a> ';        
               if (sql_value("SELECT config_json as value from plugins where name='".$p['name']."'",'')!='' && function_exists('json_decode'))
                     {
                     echo '<a class="nowrap" href="'.$baseurl_short.'pages/team/team_download_plugin_config.php?pin='.$p['name'].'">' . LINK_CARET .$lang['plugins-download'].'</a> ';
                     }
               }
            echo '</div></td></tr>';
            } 
         ?>
      </tbody>
   </table>
   </div>
   <?php 
   } 
else 
   {
   echo "<p>".$lang['plugins-noneinstalled']."</p>";
   } ?>

<h2 class="pageline"><?php echo $lang['plugins-availableheader']; ?></h2>
<?php

if (count($plugins_avail)>0) 
   { 
   $plugin_categories = array();
   foreach($plugins_avail as $p)
      {
        if($searching && !findPluginFromSearch($p, $find))
            {
            continue;
            }

      $plugin_row = '<tr><td>'.$p['name'].'</td><td>'.$p['desc'].'</td><td>'.$p['author'].'</td>';
      if ($p['version'] == 0)
         {
         $plugin_row .= '<td>' . $lang["notavailableshort"] . '</td>';
         }
      else
         {
         $plugin_row .= '<td>'.$p['version'].'</td>';
         }
        $plugin_row .= '<td><div class="ListTools">';
      
        if(!in_array($p["name"],$disabled_plugins) || isset($p["inst_version"]))
            {
            $plugin_row .= '<a href="#'.$p['name'].'" class="p-activate">' . LINK_CARET .$lang['plugins-activate'].'</a> ';
            }
        elseif(in_array($p["name"],$disabled_plugins))
            {
            $plugin_row .=  ($disabled_plugins_message != "") ? strip_tags_and_attributes(i18n_get_translated($disabled_plugins_message),array("a"),array("href","target")) : ("<a href='#' >" . LINK_CARET . "&nbsp;" . $lang['plugins-disabled-plugin-message'] . "</a>");
            }
                        
     
      if ($p['info_url']!='')
         {
         $plugin_row .= '<a class="nowrap" href="'.$p['info_url'].'" target="_blank">' . LINK_CARET . $lang['plugins-moreinfo'].'</a> ';
         }
      if ($p['config'])
         {
         $plugin_row .= '<a href="#'.$p['name'].'" class="p-purge">' .  LINK_CARET . $lang['plugins-purge'].'</a> ';
         }
      $plugin_row .= '</div></td></tr>';  
      if(isset($p["category"]))
         {
         $p["category"] = trim(strtolower($p["category"]));
         #Check for category lists
         if(preg_match("/.*,.*/",$p["category"]))
            {
            $p_cats = explode(",",$p["category"]);
            foreach($p_cats as $p_cat)
               {
                $p_cat = trim(strtolower($p_cat));
                if(!isset($plugin_categories[$p_cat]))
                    {
                    $plugin_categories[$p_cat] = array();
                    }
                array_push($plugin_categories[$p_cat], $plugin_row);
               }
            }
         else 
            {
            if(!isset($plugin_categories[$p["category"]]))
               {
               $plugin_categories[$p["category"]] = array();
               }

            array_push($plugin_categories[$p["category"]], $plugin_row);
            }
         }
      }

function display_plugin_category($plugins,$category,$header=true) 
    { 
    global $lang;
    ?>
    <div class="plugin-category-container">
    <?php 
    if($header)
        {
        $category_name = isset($lang["plugin_category_{$category}"]) ? $lang["plugin_category_{$category}"] : $category;
        ?>
        <h3 class="CollapsiblePluginListHead collapsed"><?php echo htmlspecialchars($category_name); ?></h3>
        <?php
        }
        ?>
        <div class="Listview CollapsiblePluginList">
            <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
                <thead>
                    <tr class="ListviewTitleStyle">
                    <td><?php echo $lang['name']; ?></td>
                    <td><?php echo $lang['description']; ?></td>
                    <td><?php echo $lang['plugins-author']; ?></td>
                    <td><?php echo $lang['plugins-version']; ?></td>
                    <td><div class="ListTools"><?php echo $lang['tools']; ?></div></td>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach($plugins as $plugin)
                    {
                    echo $plugin;
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    }

   # Category Specific plugins
   ksort($plugin_categories);
   foreach($plugin_categories as $category => $plugins)
      {
      display_plugin_category($plugins,$category);
      }
    ?>
   <script>
      jQuery(".CollapsiblePluginListHead").click(function(){
         if(jQuery(this).hasClass("collapsed")) {
            jQuery(this).removeClass("collapsed");
            jQuery(this).addClass("expanded");
            jQuery(this).siblings(".CollapsiblePluginList").show();
         }
         else {
            jQuery(this).removeClass("expanded");
            jQuery(this).addClass("collapsed");
            jQuery(this).siblings(".CollapsiblePluginList").hide();
         }
      });
      jQuery(".CollapsiblePluginList").hide();
   </script>
   <?php
   } 
else 
   {
   echo ",p>".$lang['plugins-noneavailable']."</p>";
   }

if ($enable_plugin_upload) 
   {
   ?>
   <div class="plugin-upload">
   <h2 class="pageline"><?php echo $lang['plugins-uploadheader']; ?></h2>
   <form enctype="multipart/form-data" method="post" action="<?php echo $baseurl_short?>pages/team/team_plugins.php">
        <?php generateFormToken("team_plugins"); ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="30000000" />
      <p><?php echo $lang['plugins-uploadtext']; ?><input type="file" name="pfile" /><br /></p>
      <input type="submit" name="submit" value="<?php echo $lang['plugins-uploadbutton'] ?>" />
   </form>
   <?php if (isset($rejected)&& !$rejected) 
      { 
      echo "<p>".$lang['plugins-uploadsuccess']."</p>";
      }
   echo "</div>"; 
   }
?>
</div>
<form id="anc-post" method="post" action="<?php echo $baseurl_short?>pages/team/team_plugins.php" >
    <?php generateFormToken("anc_post"); ?>
  <input type="hidden" id="anc-input" name="" value="" />
</form>
<?php
if (isset($rejected) && $rejected)
   { ?>
   <script>alert("<?php echo $rej_reason.'\\n\\r'.$lang['plugins-rejremedy']; ?>");</script>
   <?php 
   } 
include "../../include/footer.php";

