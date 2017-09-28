<?php
  require 'http_response_code.php';
  require 'auth_util.php';

  $headers = array();
  $headers[]='Access-Control-Allow-Headers: Content-Type';
  $headers[]='Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE';
  $headers[]='Access-Control-Allow-Credentials: true';
  $headers[]='Access-Control-Max-Age: 1728000';
  if (isset($_SERVER['REQUEST_METHOD'])) {
    foreach ($headers as $header) header($header);
  }
  else {
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
  $table = "";
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

  $has_access = false;
  $p_org = "";
  $p_role = "";
  $org_chk = new justus_auth();
  $p_org = $org_chk->organization;
  $p_role = $org_chk->justusrole;

  $has_access = False;

  if ($p_role == 'admin') {
    $has_access = True;
  }
  else {
    if ($method == 'GET') {
      $has_access = True;
    }

    if ($method == 'POST') {
      if ($table == 'julkaisu') {
        $rm_id = array_search('organisaatiotunnus', $columns);
        $t_org = $values[$rm_id];
        if ($p_org == $t_org) {
          $has_access = True;
        }
      }

      if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
        $rm_id = array_search('julkaisuid', $columns);
        (int)$t_id = $values[$rm_id];
        $sql = "select id from julkaisu where id = $t_id and organisaatiotunnus = '$p_org'";
        $result = pg_query($dbconn, $sql);
        if ($t_id == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }	

      if ($table == 'alayksikko') {
        $rm_id = array_search('organisaatiotekijaid', $columns);
        (int)$t_id = $values[$rm_id];
        $sql = "select ot.id from organisaatiotekija ot inner join julkaisu as j on ot.julkaisuid = j.id"
        ."  where ot.id = $t_id and j.organisaatiotunnus = '$p_org'";
        $result = pg_query($dbconn, $sql);
        if ($t_id == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }
    }

    if ($method == 'PUT') {
      if ($table == 'julkaisu') {
        $rm_id = array_search('organisaatiotunnus', $columns);
        $t_org = $values[$rm_id];
        if ($p_org == $t_org) {
          $sql = "select id from julkaisu where id = $key and organisaatiotunnus = '$p_org'";
          $result = pg_query($dbconn, $sql);
          if ($key == pg_fetch_object($result)->id) {
            $has_access = True;
          }
        }
      }

      if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
        $rm_id = array_search('julkaisuid', $columns);
        (int)$t_id = $values[$rm_id];
        $sql = "select id from julkaisu where id = $t_id and organisaatiotunnus = '$p_org'"
        ." and id in (select julkaisuid from \"$table\" x" 
        ." inner join julkaisu as y on x.julkaisuid = y.id " 
        ." where y.organisaatiotunnus = '$p_org' and x.id  = $key)";
        $result = pg_query($dbconn, $sql);
        if ($t_id == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }
    
      if ($table == 'alayksikko') {
        $rm_id = array_search('organisaatiotekijaid', $columns);
        (int)$t_id = $values[$rm_id];
        $sql = "select a.id from organisaatiotekija ot"
        ." inner join julkaisu as j on ot.julkaisuid = j.id"
        ." inner join alayksikko as a on a.organisaatiotekijaid = ot.id and a.id = $key" 
        ." where ot.id = $t_id "
        ." and j.organisaatiotunnus = '$p_org'"
        ." and j.id in (select julkaisuid from organisaatiotekija x"
        ." inner join julkaisu as y on x.julkaisuid = y.id"
        ." inner join alayksikko as a on a.organisaatiotekijaid = x.id"
        ." where y.organisaatiotunnus = '$p_org'"
        ." and a.id = $key)";
        # error_log($sql);
        $result = pg_query($dbconn, $sql);
        if ($key == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }
    }
    error_log($table); 
    if ($method == 'DELETE') {
      if ($table == 'julkaisu') {
        $sql = "select id from julkaisu where id = $key and organisaatiotunnus = '$p_org'";
        # error_log($sql);
        $result = pg_query($dbconn, $sql);
        if ($key == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }

      if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
        $sql = "select x.id from julkaisu j"
        ." inner join \"$table\" as x on x.julkaisuid = j.id"
        ." where x.id = $key and j.organisaatiotunnus = '$p_org'";
        # error_log($sql);
        $result = pg_query($dbconn, $sql);
        if ($key == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }

      if ($table == 'alayksikko') {
        $sql = "select a.id from organisaatiotekija ot"
        ." inner join julkaisu as j on ot.julkaisuid = j.id"
        ." inner join alayksikko as a on a.organisaatiotekijaid = ot.id"
        ." where a.id = $key"
        ." and j.organisaatiotunnus = '$p_org'";
        # error_log($sql);
        $result = pg_query($dbconn, $sql);
        if ($key == pg_fetch_object($result)->id) {
          $has_access = True;
        }
      }
    }
  }

  // create SQL based on HTTP method
  $params=array();

  if ($has_access) {
    switch ($method) {
      case 'GET':
        if ($p_role != 'admin') {
          if ($table == 'julkaisu') {
            $sql = "select j.* from \"$table\" j WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND j.$col=$1";
              $params=array($key);
            }
            error_log($sql);
          }
          else if ($table == 'avainsana') {
            $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND a.$col=$1";
              $params=array($key);
            }
            error_log($sql);
          }
          else if ($table == 'alayksikko') {
            $sql = "select a.* from \"$table\" a INNER JOIN organisaatiotekija as org on a.organisaatiotekijaid = org.id INNER JOIN julkaisu as j on j.id = org.julkaisuid WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND a.$col=$1";
              $params=array($key);
            }
            error_log($sql);
          }
          else if ($table == 'organisaatiotekija') {
            $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND a.$col=$1";
              $params=array($key);
            }
            error_log($sql);
          }
          else if ($table == 'tieteenala') {
            $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND a.$col=$1";
              $params=array($key);
            }
            error_log($sql);	
          }
          else if ($table == 'uijulkaisut') {
            $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.id WHERE j.organisaatiotunnus = '$p_org'";
            if ($key) {
              $sql.= " AND a.$col=$1";
              $params=array($key);
            }
            error_log($sql);
          }
          else {
            http_response_code(403);
            die(pg_last_notice($dbconn));
          }
        } 
        else {
          $sql = "select * from \"$table\"";
          if ($key) {
            $sql.= " WHERE $col=$1";
            $params=array($key);
          }
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
        error_log($sql);
        break;
      case 'POST':
        $sql = "insert into \"$table\" (".implode(",",$columns).") values (";
        for ($i=0;$i<count($columns);$i++) {
          if ($i>0) { $sql.=","; }
          $sql.= '$'.($i+1);
        }
        $params=$values;
        $sql.= ") returning id";
        error_log($sql);
        break;
      case 'DELETE':
        $sql = "delete from \"$table\" where $col=$1";
        $params=array($key);
        error_log($sql);
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
        } 
        else {
          echo ($i>0?',':'').json_encode(pg_fetch_object($result)); // would be nice but breaks things. option: JSON_NUMERIC_CHECK
        }
      }
      echo ']';
    } 
    elseif ($method == 'POST') {
      echo pg_fetch_object($result)->id;
    } 
    else {
      echo pg_affected_rows($result);
    }
  }
  else {
    http_response_code(403);
    die(pg_last_notice($dbconn));
  }
  
  // clean up & close
  pg_free_result($result);
  pg_close($dbconn);
?>
