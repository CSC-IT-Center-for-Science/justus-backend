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
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// connect to the database
$settings = parse_ini_file('/var/www/justus-settings.ini', true);
$link = mssql_connect($settings['database']['host'], $settings['database']['user'], $settings['database']['pass']);
if(!mssql_select_db($settings['database']['name'], $link)){
  die('Something went wrong while connecting to database');
}

// retrieve the table and key from the path
$table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
$key = array_shift($request)+0;

// escape the columns and values from the input object
$columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
$values = array_map(function ($value) use ($link) {
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
  $set.=($i>0?',':'').'['.$columns[$i].']=';
  $set.=($values[$i]===null?'NULL':'"'.$values[$i].'"');
  $sqlcolumns.=($i>0?',':'').'['.$columns[$i].']';
  $sqlvalues.=($i>0?',':'').($values[$i]===null?'NULL':"'".$values[$i]."'");
}

// create SQL based on HTTP method
switch ($method) {
  case 'GET':
    $sql = "select * from [$table]".($key?" WHERE id=$key":''); break;
  case 'PUT':
    $sql = "update [$table] set $set where id=$key"; break;
  case 'POST':
    $sql = "insert into [$table] ($sqlcolumns) values ($sqlvalues)"; break;
  case 'DELETE':
    $sql = "delete [$table] where id=$key"; break;
}

// excecute SQL statement
// lisää turvallisuutta käyttämällä sp_executesql proseduuria
// TODO pitää tehdä lisää escapea, tai tuo params...
//$dbsql = "EXEC sp_executesql
//          N'$sql'";//,
          //N'$params',
          //$paramslist";
$result = mssql_query($sql,$link);

// die if SQL statement failed
if (!$result) {
  http_response_code(404);
  die(mssql_get_last_message());
}

// print results, insert id or affected row count
if ($method == 'GET') {
  if (!$key) echo '[';
  for ($i=0;$i<mssql_num_rows($result);$i++) {
    echo ($i>0?',':'').json_encode(mssql_fetch_object($result));
  }
  if (!$key) echo ']';
} elseif ($method == 'POST') {
  $insert_result = mssql_query('select SCOPE_IDENTITY() AS last_insert_id') or die('Scope Query failed');
  $insert_result = mssql_fetch_object($insert_result);
  mssql_free_result($insert_result);
  echo $insert_result->last_insert_id;
} else {
  echo mssql_rows_affected($link);
}

// clean up
mssql_free_result($result);
// close connection
mssql_close($link);

?>
