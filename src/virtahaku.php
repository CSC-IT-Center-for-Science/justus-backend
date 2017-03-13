<?php
require 'http_response_code.php';

$headers = array();
$headers[]='Access-Control-Allow-Headers: Content-Type';
$headers[]='Access-Control-Allow-Methods: GET, POST';
$headers[]='Access-Control-Allow-Credentials: true';
$headers[]='Access-Control-Max-Age: 1728000';
if (isset($_SERVER['REQUEST_METHOD'])) {
  foreach ($headers as $header) header($header);
} else {
  echo json_encode($headers);
}

header('Content-Type: application/json; charset=utf-8');

//$method = $_SERVER['REQUEST_METHOD'];
//echo "method: ".$method.PHP_EOL;

// POST-parametrit (esim autentikointiin)
$postdata = file_get_contents('php://input');
// aidot post-parametrit, jos osattu käyttää Content-Type arvoa "application/x-www-form-urlencoded" tai "multipart/form-data"
// user,pass = $_POST[""];

// jos/kun autentikointi OK, voidaan hakea oikeasta rajapinnasta
// GET-parametrit
// - julkaisunTunnus
// TAI
// - julkaisunNimi ja henkiloHaku

$julkaisunTunnus = "";
$haku = "";
$julkaisunNimi = "";
$henkiloHaku = "";
$sukunimi = "";
$etunimi = "";
$organisaatioTunnus = "";
if (isset($_GET["julkaisunTunnus"])) {
  $julkaisunTunnus = htmlspecialchars($_GET["julkaisunTunnus"]);
} else {
  // lisää "haku?" ei haittaa vaikka tulee "?&..."
  $haku = "haku?";
  // oikeat ehdot: katenoi myös parametrierotin!
  if (isset($_GET["julkaisunNimi"])) {
    $julkaisunNimi = "&julkaisunNimi=".htmlspecialchars($_GET["julkaisunNimi"]);
  }
  if (isset($_GET["henkiloHaku"])) {
    // sukunimi, etunimi?
    $henkiloHaku = "&henkiloHaku=".htmlspecialchars($_GET["henkiloHaku"]);
  }
  if (isset($_GET["sukunimi"])) {
    $sukunimi = "&sukunimi=".htmlspecialchars($_GET["sukunimi"]);
  }
  if (isset($_GET["etunimi"])) {
    $etunimi = "&etunimi=".htmlspecialchars($_GET["etunimi"]);
  }
  if (isset($_GET["organisaatioTunnus"])) {
    $organisaatioTunnus = "&organisaatioTunnus=".htmlspecialchars($_GET["organisaatioTunnus"]);
  }
}

$uri = 'https://virta-jtp.csc.fi/api/julkaisut/';
//$uri = 'https://dwitjutife1.csc.fi/api/julkaisut/';
//$uri = 'http://localhost:8080/api/julkaisut/';
//$uri = 'http://localhost/api/julkaisut/';
$json = file_get_contents($uri.$julkaisunTunnus.$haku.$julkaisunNimi.$henkiloHaku.$sukunimi.$etunimi.$organisaatioTunnus);

echo $json;

?>
