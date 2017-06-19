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
$key = array_shift($request);
// extra: get by other (for ex. foreign key) column:
$col = "id";
if (count($request)>0) {
  $col = $key;
  $key = array_shift($request);
} else {
  $key = $key+0; // force numeric for id
}

// escape the columns and values from the input object
$columns = !$input ? null : preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
$values = !$input ? null : array_map(function ($value) use ($dbconn) {
  if ($value===null) return null;
  // escape str
  $value = stripslashes($value);
  $value = str_replace("'","''",$value);
  $value = str_replace("\0","[NULL]",$value);
  return $value;
},array_values($input));

// create SQL based on HTTP method
$params=array();
switch ($method) {
  case 'GET':
    $sql = "select * from \"$table\"";
    if ($key) {
      $sql.= " WHERE $col=$1";
      $params=array($key);
    }
    break;
  case 'PUT':
    $sql = "update \"$table\" set ";
    for ($i=0;$i<count($columns);$i++) {
      if ($i>0) { $sql.=","; }
      $sql.= $columns[$i]."="."$".($i+2); //leave room for id
    }
    $params=array_merge(array($key),$values);
    $sql.= " where $col=$1";
    break;
  case 'POST':
    $sql = "insert into \"$table\" (".implode(",",$columns).") values (";
    for ($i=0;$i<count($columns);$i++) {
      if ($i>0) { $sql.=","; }
      $sql.= '$'.($i+1);
    }
    $params=$values;
    $sql.= ") returning id";
    break;
  case 'DELETE':
    $sql = "delete from \"$table\" where $col=$1";
    $params=array($key);
    break;
}

// excecute SQL statement
$result = pg_query_params($dbconn,$sql,$params);

// die if SQL statement failed
if (!$result) {
  http_response_code(404);
  die(pg_last_notice($dbconn));
}

// print results, insert id or affected row count
if ($method == 'GET') {
  echo '[';
  for ($i=0;$i<pg_num_rows($result);$i++) {
    if ($table=='uijulkaisut') {
      echo ($i>0?',':'').pg_fetch_result($result,$i,'row_to_json');
    } else {
      echo ($i>0?',':'').json_encode(pg_fetch_object($result)); // would be nice but breaks things. option: JSON_NUMERIC_CHECK
    }
  }
  echo ']';
} elseif ($method == 'POST') {
  echo pg_fetch_object($result)->id;
} else {
  echo pg_affected_rows($result);
}

// clean up & close
pg_free_result($result);
pg_close($dbconn);
?>