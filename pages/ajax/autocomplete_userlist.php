<?php
# Feeder page for userlist names

include "../../include/db.php";

include "../../include/authenticate.php";

$find=getval("term","  ");
$userlists=ps_query("select userlist_name from user_userlist where user= ? and userlist_name like ?", ['i', $userref, 's', '%'.$find.'%']);
$first=true;
?>[
<?php
$users=get_users(0,$find);
for ($n=0;$n<count($userlists) && $n<=20;$n++)
	{
	if (!$first) { ?>, <?php }
			$first=false;
    ?>{
       "value": "<?php echo $userlists[$n]['userlist_name']?>"}<?php	
	}
?>
]
