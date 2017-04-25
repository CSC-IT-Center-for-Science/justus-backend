{<?php
$uid = "";
if (array_key_exists("shib-uid",$_SERVER)) {
  $uid = $_SERVER["shib-uid"];
}
$mail = "";
if (array_key_exists("shib-mail",$_SERVER)) {
  $mail = $_SERVER["shib-mail"];
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
    // a role for "owner"
    if (strpos($groups[$i],'justus#group-admins')!==false) {
      $justusrole = "owner";
    }
    // a role for organizational admin
    // - do we need group name from here?
    if (!$justusrole && preg_match($groups[$i],'justus#([^;]*)-admins')==1) {
      $justusrole = "admin";
    }
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
  "name": "<?php print($name); ?>",
  "mail": "<?php print($mail); ?>",
  "uid": "<?php print($uid); ?>",
  "domain": "<?php print($domain); ?>",
  "role": "<?php if($justusrole){ print($justusrole); } ?>"
}
