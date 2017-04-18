<?php
$user = "";
if (array_key_exists("shib-uid",$_SERVER)) {
  $user = $_SERVER["shib-uid"];
}
if (!$user && array_key_exists("shib-mail",$_SERVER)) {
  $user = $_SERVER["shib-mail"];
}

$name = "";
if (array_key_exists("shib-givenName",$_SERVER) && array_key_exists("shib-sn",$_SERVER)) {
  $name = $_SERVER["shib-givenName"]." ".$_SERVER["shib-sn"];
}

$domain = "";
$justusrole = "";
if (array_key_exists("shib-group",$_SERVER)) {
  $groups = explode(";",$_SERVER["shib-group"]);
  //print_r($groups);
  for ($i=0; $i<count($groups); $i++) {
    // new, should use
    if (strpos($groups[$i],'justus#group-admins')!==false) {
      $justusrole = "admin";
    }
    // old. deprecated?
    //if (strpos($groups[$i],'justus-owners')!==false) {
    //  $justusrole = "owner ";
    //}
    // todo: what will be the format? (justus#...?)
    if (!$justusrole && strpos($groups[$i],'justus')!==false) {
      $justusrole = "member";
    }
    if (strpos($groups[$i],'@')!==false) {
      $domain = $groups[$i];
    }
  }
}
?>
{"name": "<?php print($name); ?>"},
{"user": "<?php print($user); ?>"},
{"domain": "<?php print($domain); ?>"},
{"role": "<?php if($justusrole){ print($justusrole); } ?>"}
