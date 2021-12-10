<?php

function HookGrant_editViewBeforepermissionscheck()
    {
    global $ref,$userref, $access;
    $grant_edit=ps_value("select resource value from grant_edit where resource = ? and user = ? and (expiry is null or expiry >= NOW())", array("i",$ref,"i",$userref), "");
    if($grant_edit!=""){$access=0;}
    return true;        
    }