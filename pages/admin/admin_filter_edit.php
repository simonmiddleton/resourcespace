<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

include "../../include/header.php";
?>


<div id="CentralSpaceContainer"><div id="CentralSpace">


<div class="BasicsBox">
  <h1><?php echo $lang["filter_rules_edit"] ?></h1>

  
  <form id="form1" name="form1" method="post" action="">

    
<div class="Question">
<br><h2>Configure the conditions required for the rule to be met</h2>
  </div>
<div class="clearerleft"></div>

  
<div class="Question " id="question_0">
<label for="field_96">Filter name </label>

<input class="stdwidth" type="text" name="field_96" id="field_96" value="Happy OR Australia" onblur="HideHelp(96);return false;" onfocus="ShowHelp(96);return false;" onchange="AutoSave('96');">

<div class="clearerleft"> </div>
</div>
    
<div class="Question" id="question_2" title="" style="height: 50px;">
<label>Criteria</label>
<select>
    <option value=1>
    ALL of these conditions must be met
    </option>
    
    <option value=2>
    NONE of these conditions are met
    </option>
    
    <option value=3>
    ANY ONE of these conditions are met
    </option>
</select>  
<div class="clearerleft"> </div>
</div>


<div class="Question">
    <label>Options</label>
    <div class="ui-widget" style="width: 730px;">
        <input  name="search" type="text" class="SearchWidth tag-editor-hidden-src" >
        <ul class="tag-editor ui-sortable">
        
        <li style="width:1px" class="ui-sortable-handle">&nbsp;</li>
        <li class="ui-sortable-handle"><div class="tag-editor-spacer">&nbsp;~</div>        
            <div class="tag-editor-tag">country=Australia</div><div class="tag-editor-delete" style="height:20px;"><i></i></div>
            <div class="tag-editor-spacer">&nbsp;~</div>
            <div class="tag-editor-tag">emotion=Happy</div><div class="tag-editor-delete" style="height:20px;"><i></i></div>
        </li>
        </ul>
    </div>
    

<div class="clearerleft"> </div>
</div>



 <div class="Question">
<label>Field:</label>
<select name="action_dates_deletefield" id="action_dates_deletefield" style="width:300px">
<option value="" selected=""></option>
<option value="78">Aspect ratio</option>    <option value="83">Audio bitrate</option>    <option value="52">Camera make / model</option>    <option value="18">Caption</option>    <option value="81">Channel mode</option>    <option value="3">Country</option>    <option value="10">Credit</option>    <option value="12">Date</option>    <option value="88">Date Range</option>    <option value="91">Doctype</option>    <option value="9">Document extract</option>    <option value="80">Duration</option>    <option value="72">Extracted text</option>    <option value="76">Frame rate</option>    <option value="75" selected >Department</option>    <option value="74">Keywords - Event</option>    <option value="1">Keywords - Other</option>    <option value="73">Keywords - Subject</option>    <option value="29">Named person(s)</option>    <option value="25">Notes</option>    <option value="51">Original filename</option>    <option value="82">Sample rate</option>    <option value="54">Source</option>    <option value="85">Tags</option>    <option value="8">Title</option>    <option value="92">Tree</option>    <option value="77">Video bitrate</option>    <option value="93">Video link</option>    <option value="79">Video size</option>    <option value="84">Year</option>
</select>

<select name="conditionlogic" id="conditionlogic" style="width:100px">
<option value="0" selected >IS</option>
<option value="1" >IS NOT</option>
</select>


<select name="condition2" id="condition2" style="width:300px">
<option value="0" selected >Marketing</option>
<option value="1" >Sales</option>
<option value="1" >Support</option>
<option value="1" >HR</option>
</select>
 <a><i aria-hidden="true" class="fa fa-plus-circle"></i>&nbsp;Add condition</input></a>
</div>




</form>
</div>