<?php
require 'http_response_code.php';

$headers = array();
$headers[]='Access-Control-Allow-Headers: Content-Type';
$headers[]='Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE';
$headers[]='Access-Control-Allow-Credentials: true';
$headers[]='Access-Control-Max-Age: 1728000';
if (isset($_SERVER['REQUEST_METHOD'])) {
  foreach ($headers as $header) header($header);
} else {
  echo json_encode($headers);
}

header('Content-Type: application/json; charset=utf-8');

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
if ($method=='OPTIONS') {
  http_response_code(200);
  exit;
}

$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// connect to the database
$settings = parse_ini_file('/etc/justus-backend.ini', true);
$dbconn = pg_connect(
  "host=".$settings['database']['host'].
  " dbname=".$settings['database']['name'].
  " user=".$settings['database']['user'].
  " password=".$settings['database']['pass'])
  or die('Something went wrong while connecting to database: '.pg_last_error());

// retrieve the table and key from the path
$table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
$key = array_shift($request)+0;

// escape the columns and values from the input object
$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
$values = array_map(function ($value) use ($dbconn) {
  if ($value===null) return null;
  // escape str
  $value = stripslashes($value);
  $value = str_replace("'","''",$value);
  $value = str_replace("\0","[NULL]",$value);
  return $value;
},array_values($input));

// build the SET part of the SQL command (for UPDATE)
// and sqlcolumns, and sqlvalues for INSERT
$set = '';
$sqlcolumns = '';
$sqlvalues = '';
for ($i=0;$i<count($columns);$i++) {
  $set.=($i>0?',':'').$columns[$i].'=';
  $set.=($values[$i]===null?'NULL':"'".$values[$i]."'");
  $sqlcolumns.=($i>0?',':'').$columns[$i];
  $sqlvalues.=($i>0?',':'').($values[$i]===null?'NULL':"'".$values[$i]."'");
}

// create SQL based on HTTP method
switch ($method) {
  case 'GET':
    $sql = "select * from \"$table\"".($key?" WHERE id=$key":'');
    break;
  case 'PUT':
    $sql = "update \"$table\" set $set where id=$key";
    break;
  case 'POST':
    $sql = "insert into \"$table\" ($sqlcolumns) values ($sqlvalues) returning id";
    break;
  case 'DELETE':
    $sql = "delete from \"$table\" where id=$key";
    break;
}

// excecute SQL statement
$result = pg_query($dbconn,$sql);

// die if SQL statement failed
if (!$result) {
  http_response_code(404);
  die(pg_last_notice($dbconn));
}

// print results, insert id or affected row count
if ($method == 'GET') {
  if (!$key) echo '[';
  for ($i=0;$i<pg_num_rows($result);$i++) {
    echo ($i>0?',':'').json_encode(pg_fetch_object($result));
  }
  if (!$key) echo ']';
} elseif ($method == 'POST') {
  echo pg_fetch_object($result)->id;
} else {
  echo pg_affected_rows($result);
}

// clean up
pg_free_result($result);
// close connection
pg_close($dbconn);
?>
