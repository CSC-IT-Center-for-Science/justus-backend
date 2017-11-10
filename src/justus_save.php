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


   $input = [];
   $request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
   $t_input = json_decode(file_get_contents('php://input'),true);


/*
 * Force json data intoi 2-dim array
 * {"element1": "0", "element2":"1"} --> [{"element1": "0", "element2":"1"}] 
*/

  if (is_array($t_input[0])) {
     $input = $t_input;
  } else {
     $input[0] = $t_input;
  }


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
 
/*
 * json data into array 
 *
*/
 
  $columns = [];
  $r_count=0;
  foreach ($input as $set) { 
    if ($r_count == 0){
	$columns = !$set ? null : preg_replace('/[^a-z0-9_]+/i','',array_keys($set));
    }
 
    $t_columns = !$set ? null : preg_replace('/[^a-z0-9_]+/i','',array_keys($set));
	
    if ($columns == $t_columns) {
	$values = !$set ? null : array_map(function ($value) use ($dbconn) {
     	if ($value===null) return null;
       	    // escape str
       	    $value = stripslashes($value);
       	    $value = str_replace("'","''",$value);
       	    $value = str_replace("\0","[NULL]",$value);
       	    return $value;
 	    },array_values($set));

        $all_values[] = $values;
	$r_count++;

	}
  }
 

/*
 * Acess right user/group/method checks based on a shibboleth attributes
 */

  $p_org = "";
  $p_role = "";
  $org_chk = new justus_auth();
  $p_org = $org_chk->organization;
  $p_role = $org_chk->justusrole;
  $p_uid = $org_chk->uid;
  $p_mail = $org_chk->mail;
  $p_domain = $org_chk->domain;
  $p_name = $org_chk->name;

  $has_access = False;

  if ($p_role == 'owner') {
      if (($table == 'julkaisu' && $method != 'DELETE') || $table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija' || $table == 'alayksikko' || $table == 'uijulkaisu') {
	 $has_access = True;
      }
  }
  else {

  if ($method == 'GET') {
       $has_access = True;
  }
  
  $julk_id = 0;
  $result_id = array(); 

/*
 *  POST (insert) information can be added to only one publicatiob per request
 */ 

  if ($method == 'POST') {
	if ($table == 'julkaisu') {
	    $rm_id = array_search('organisaatiotunnus', $columns);
	        $t_org = $all_values[0][$rm_id];
	        if ($p_org == $t_org) {
	    	     $has_access = True;
	        }
		/*
		 * Make sure we have only one publication
		 */
		 $tmp_array = $all_values[0];
		 unset($all_values);
		 $all_values[0] = $tmp_array;

	}

	if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
		if (in_array("julkaisuid", $columns)) {
		     $rmid = array_search('julkaisuid', $columns);
		     (int)$tmp_id = $all_values[0][$rmid];
	    	         $sql = "select j.id from julkaisu j"
				 ." inner join kaytto_loki as kl on j.accessid = kl.id" 
				 ."  where j.id = $tmp_id"
		             ." and organisaatiotunnus = '$p_org'"
		             .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
	    	         $result = pg_query($dbconn, $sql);
                         $julk_id = pg_fetch_object($result)->id;
                         if ($julk_id > 0){
			      $has_access = True; 
			      for($x=0;$x<count($all_values);$x++) {
	    	                    if ($all_values[$x][$rmid] != $julk_id) {
		                        $has_access = False;
	    		            }
                              }
			  }
		    }    
		
	}	

	if ($table == 'alayksikko') {
		 if (in_array("organisaatiotekijaid", $columns)) {
			 $rmid = array_search('organisaatiotekijaid', $columns);
                         (int)$tmp_id = $all_values[0][$rmid];
	                     $sql = "select j.id, ot.id as orgtekid from organisaatiotekija ot"
				     ." inner join julkaisu as j on ot.julkaisuid = j.id"
                                     ." inner join kaytto_loki as kl on j.accessid = kl.id"
		                     ." where ot.id = $tmp_id and j.organisaatiotunnus = '$p_org'"
		                    .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
	        	     $result = pg_query($dbconn, $sql);
			     $julk_id = pg_fetch_object($result)->id;
                             // $orgtekid = pg_fetch_object($result)->orgtekid;
                             if ($julk_id > 0){
                                   $has_access = True;
			           for($x=0;$x<count($all_values);$x++) { 
                                       if ($all_values[$x][$rmid] != $tmp_id) {
		     		       $has_access = False;
                                       }
                                  }
  		             }
	         }
 	}
  }
   
/*
 * PUT (update) id or julkaisuid can be used to identify publication
 */ 

  if ($method == 'PUT') {
	if ($table == 'julkaisu' && $key > 0) {
	    
	    // check whether information exists or not
            $sql = "select count(id) as lkm from \"$table\" where id = $key";
            $result = pg_query($dbconn, $sql);
            if (!pg_fetch_object($result)->lkm){
            	http_response_code(204);
              	die(pg_last_notice($dbconn));
            }

	    // check if request has an access
            $rm_id = array_search('organisaatiotunnus', $columns);
            $t_org = $all_values[0][$rm_id];
            if ($p_org == $t_org) {
		$sql = "select j.id from julkaisu j inner join kaytto_loki as kl on j.accessid = kl.id  where j.id = $key and organisaatiotunnus = '$p_org'"
			 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
	 	$result = pg_query($dbconn, $sql);
		if ($key == pg_fetch_object($result)->id  && $p_org == $t_org) {
                    $has_access = True;
		    $julk_id = $key;
		}
            }
            /*
	     * Make sure we have only one publication
             */
             $tmp_array = $all_values[0];
             unset($all_values);
             $all_values[0] = $tmp_array;

        }

	if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
		if ($col == 'julkaisuid' && $key > 0) {

                     // check whether information exists or not
		     $sql = "select count(id) as lkm from \"$table\" where julkaisuid = $key";
            	     $result = pg_query($dbconn, $sql);
                     if (!pg_fetch_object($result)->lkm){
                     	http_response_code(204);
                	die(pg_last_notice($dbconn));
		     }

		     // check if request has an access
	    	     $sql = "select j.id from julkaisu j inner join kaytto_loki as kl on j.accessid = kl.id  where j.id = $key"
		    	  ." and organisaatiotunnus = '$p_org'"
		          .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
		}
		else if ($col == 'id' && $key > 0) {

		      // check whether information exists or not
		     $sql = "select count(id) as lkm from \"$table\" where id = $key";
                     $result = pg_query($dbconn, $sql);
                     if (!pg_fetch_object($result)->lkm){
                        http_response_code(204);
                        die(pg_last_notice($dbconn));
                     }

                    // check if request has an access
                    $sql = "select x.id from julkaisu x"
		           ." inner join \"$table\" as y on y.julkaisuid = x.id"
                           ." inner join kaytto_loki as kl on x.accessid = kl.id"
			   ." where x.organisaatiotunnus = '$p_org'"
			   ." and y.id = $key"
			   .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'"); 

		}
		else {
		       http_response_code(403);
          	       die(pg_last_notice($dbconn));
		}
	    	$result = pg_query($dbconn, $sql);
		$julk_id = pg_fetch_object($result)->id;
	    	if ($julk_id > 0) {
		    $has_access = True;
		    if (in_array("julkaisuid", $columns)) {
		        $rmid = array_search('julkaisuid', $columns);
		        for($x=0;$x<count($all_values);$x++) {
                             if ($all_values[$x][$rmid]!= $julk_id) {
	    	                 $has_access = False;
	    		     }
		        }
		    } 
		    else {
			$has_access = False;
		    }
		}
	}	

	if ($table == 'alayksikko') {
		if ($col == 'organisaatiotekijaid' && $key > 0) {
                  
                      // check whether information exists or not
		     $sql = "select count(id) as lkm from \"$table\" where organisaatiotekijaid = $key";
                     $result = pg_query($dbconn, $sql);
                     if (!pg_fetch_object($result)->lkm){
                        http_response_code(204);
                        die(pg_last_notice($dbconn));
                     }

		     // check if request has an access
	             $sql = "select j.id, ot.id as orgtekid from organisaatiotekija ot inner join julkaisu as j on ot.julkaisuid = j.id"
			   ."  inner join kaytto_loki as kl on j.accessid = kl.id"
		           ."  where ot.id = $key and j.organisaatiotunnus = '$p_org'"
			   .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");

		 }
                else if ($col == 'id' && $key > 0) {
		     
                     // check whether information exists or not
		     $sql = "select count(id) as lkm from \"$table\" where id = $key";
                     $result = pg_query($dbconn, $sql);
                     if (!pg_fetch_object($result)->lkm){
                        http_response_code(204);
                        die(pg_last_notice($dbconn));
                     }

                     
                     // check if request has an access
		     $sql = "select j.id, ot.id as orgtekid from organisaatiotekija ot" 
			       ." inner join julkaisu as j on ot.julkaisuid = j.id"
                               ." inner join kaytto_loki as kl on j.accessid = kl.id"
			       ." inner join alayksikko as a on a.organisaatiotekijaid = ot.id "
                               ." where j.organisaatiotunnus = '$p_org'"
			       ." and a.id = $key"
		               .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
		}
		else {
		     $sql = "";
		     http_response_code(403);
                     die(pg_last_notice($dbconn));
	        }

	        $result = pg_query($dbconn, $sql);
		$t_data = pg_fetch_object($result);
		$julk_id = $t_data->id;
                $orgtekid = $t_data->orgtekid;
                if ($julk_id > 0) {
		     $has_access = True;
                     if (in_array("organisaatiotekijaid", $columns)) {
                        $rmid = array_search('organisaatiotekijaid', $columns);
                        for($x=0;$x<count($all_values);$x++) {
                             if ($all_values[$x][$rmid]!= $orgtekid) {
                                 $has_access = False;
                             }
                        }
                    } 
		    else {
                        $has_access = False;
                    }
                }
       }
  }

/*
 * Delete methods
 */  	 

  if ($method == 'DELETE') {
	if ($table == 'julkaisu') {
    		$has_access = False;

	/* We don't want to support this */
	/*
	    $col = "id";
	    $sql = "select id from julkaisu where id = $key and organisaatiotunnus = '$p_org'"
		   .($p_role == 'admin' ? '' :  " and _uid = '$p_uid'");
	    error_log($sql);
            # error_log($sql);
	    $result = pg_query($dbconn, $sql);
            if ($key == pg_fetch_object($result)->id) {
                $has_access = True;
		$julk_id = $key;
            }
	*/

	}

	if ($table == 'avainsana' || $table == 'tieteenala' || $table == 'organisaatiotekija') {
	    if ($col == "julkaisuid" && $key > 0) {

                 // check whether information exists or not
		 $sql = "select count(id) as lkm from \"$table\" where julkaisuid = $key";
                 $result = pg_query($dbconn, $sql);
                 if (!pg_fetch_object($result)->lkm){
                        http_response_code(204);
                        die(pg_last_notice($dbconn));
                }

                 // check if request has an access
	         $sql = "select x.id from julkaisu x" 
			  #." inner join \"$table\" as y on y.julkaisuid = x.id"
                          ." inner join kaytto_loki as kl on x.accessid = kl.id"
			  ." where x.id = $key and x.organisaatiotunnus = '$p_org'"
		          .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
	    }
	    else if ($col == 'id' && $key > 0) {

                  // check whether information exists or not
		 $sql = "select count(id) as lkm from \"$table\" where id = $key";
		 $result = pg_query($dbconn, $sql);
                 if (!pg_fetch_object($result)->lkm){
        		http_response_code(204);
        		die(pg_last_notice($dbconn));
     		}

		 // check if request has an access
		 $sql = "select x.id from julkaisu x"
			." inner join \"$table\" y on y.julkaisuid = x.id" 
                        ."  inner join kaytto_loki as kl on x.accessid = kl.id"
			." where y.id = $key and x.organisaatiotunnus = '$p_org'"
                        .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
	    }
	    else {
                     $sql = "";
                     http_response_code(403);
                     die(pg_last_notice($dbconn));
           }
	
	         $result = pg_query($dbconn, $sql);
		 $julk_id = pg_fetch_object($result)->id;
                 if ($julk_id > 0) {
		       $has_access = True;
		 }
	    }
	}
      
	if ($table == 'alayksikko') {
            if ($col == "organisaatiotekijaid" && $key > 0) {

	        // check whether information exists or not	
		$sql = "select count(id) as lkm from \"$table\" where organisaatiotekijaid = $key";
                 	$result = pg_query($dbconn, $sql);
                 	if (!pg_fetch_object($result)->lkm){
                            http_response_code(204);
                            die(pg_last_notice($dbconn));
                	}

                // check if request has an access
	     	$sql = "select j.id from organisaatiotekija ot"
                        ." inner join julkaisu as j on ot.julkaisuid = j.id"
                        ." inner join kaytto_loki as kl on j.accessid = kl.id"
                        # ." inner join alayksikko as a on a.organisaatiotekijaid = ot.id"
		        ." where ot.id = $key"
		        ." and j.organisaatiotunnus = '$p_org'"
			.($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
		}
		else if ($col == 'id' && $key > 0) {

                     // check whether information exists or not
		     $sql = "select count(id) as lkm from \"$table\" where id = $key";
                     $result = pg_query($dbconn, $sql);
                     if (!pg_fetch_object($result)->lkm){
                      	http_response_code(204);
                      	die(pg_last_notice($dbconn));
                     }

                     // check if request has an access
		     $sql = "select j.id from alayksikko a"
				." inner join organisaatiotekija as ot on ot.id = a.organisaatiotekijaid"
				." inner join julkaisu as j on ot.julkaisuid = j.id"
                                ." inner join kaytto_loki as kl on j.accessid = kl.id"
				." where a.id = $key"
				." and j.organisaatiotunnus = '$p_org'"
				.($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");

	    	}
		
            error_log($sql);
	    $result = pg_query($dbconn, $sql);
	    $julk_id = pg_fetch_object($result)->id;
            if ($julk_id > 0) {
                $has_access = True;
            }
	}
  }
 

// create SQL based on HTTP method
$params = array();

if ($has_access) {

switch ($method) {
  case 'GET':
    if ($p_role != 'owner') {
    if ($table == 'julkaisu') {
        $sql = "select j.* from \"$table\" j inner join kaytto_loki as kl on j.accessid = kl.id  WHERE j.organisaatiotunnus = '$p_org'"
		 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
        if ($key) {
            $sql.= " AND j.$col=$1";
            $params=array($key);
        }
	error_log($sql);
    }
    else if ($table == 'avainsana') {
         $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid  inner join kaytto_loki as kl on j.accessid = kl.id WHERE j.organisaatiotunnus = '$p_org'"
		 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
         if ($key) {
            $sql.= " AND a.$col=$1";
            $params=array($key);
        }
	error_log($sql);
    }
    else if ($table == 'alayksikko') {
         $sql = "select a.* from \"$table\" a INNER JOIN organisaatiotekija as org on a.organisaatiotekijaid = org.id INNER JOIN julkaisu as j on j.id = org.julkaisuid  inner join kaytto_loki as kl on j.accessid = kl.id WHERE j.organisaatiotunnus = '$p_org'"
		 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
         if ($key) {
            $sql.= " AND a.$col=$1";
            $params=array($key);
        }
	error_log($sql);
    }
    else if ($table == 'organisaatiotekija') {
        $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid inner join kaytto_loki as kl on j.accessid = kl.id WHERE j.organisaatiotunnus = '$p_org'"
		 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
        if ($key) {
            $sql.= " AND a.$col=$1";
            $params=array($key);
       }
	error_log($sql);
    }
    else if ($table == 'tieteenala') {
        $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.julkaisuid inner join kaytto_loki as kl on j.accessid = kl.id WHERE j.organisaatiotunnus = '$p_org'"
		.($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
        if ($key) {
            $sql.= " AND a.$col=$1";
            $params=array($key);
       }
	error_log($sql);	
    }
    else if ($table == 'uijulkaisut') {
        $sql = "select a.* from \"$table\" a INNER JOIN julkaisu as j on j.id = a.id inner join kaytto_loki as kl on j.accessid = kl.id WHERE j.organisaatiotunnus = '$p_org'"
		 .($p_role == 'admin' ? '' :  " and kl.uid = '$p_uid'");
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
  } else {
       	$sql = "select * from \"$table\"";
    	if ($key) {
      	    $sql.= " WHERE $col=$1";
      	    $params=array($key);
    	}
 }      
    // excecute SQL statement
    $result = pg_query_params($dbconn,$sql,$params);

    // die if SQL statement failed
    if (!$result) {
  	http_response_code(404);
  	die(pg_last_notice($dbconn));
    }
 
    break;
  case 'PUT':
        error_log($col);
        for($x=0;$x<count($all_values);$x++) {	 
	     $sql = "update \"$table\" set ";
             for ($i=0;$i<count($columns);$i++) {
                 if ($i>0) { $sql.=","; }
                 $sql.= $columns[$i]."="."$".($i+2); //leave room for id
             }

             $params=array_merge(array($key),$all_values[$x]);
             $sql.= " where $col=$1";

	     // excecute SQL statement
	     $result = pg_query_params($dbconn,$sql,$params);
             
              // No affected rows
             if (pg_affected_rows($result) == 0){
             	  http_response_code(204);
                  die(pg_last_notice($dbconn));
             }

	     // die if SQL statement failed
	     if (!$result) {
  	          http_response_code(404);
  	          die(pg_last_notice($dbconn));
	     }

        }

  break;
  case 'POST':
    
    for($x=0;$x<count($all_values);$x++) {
         $sql = "insert into \"$table\" (".implode(",",$columns).") values (";
         for ($i=0;$i<count($columns);$i++) {
              if ($i>0) { $sql.=","; }
                  $sql.= '$'.($i+1);
              }
         $params=$all_values[$x];
         $sql.= ") returning id";
	 error_log($sql);

            // excecute SQL statement
            $result = pg_query_params($dbconn,$sql,$params);
	   // die if SQL statement failed

	   // No affected rows
	   if (pg_affected_rows($result) == 0){
           	http_response_code(204);
        	die(pg_last_notice($dbconn));
     	   }

           if (!$result) {
                http_response_code(404);
                die(pg_last_notice($dbconn));
           }

	   if ($julk_id == 0 && $table == 'julkaisu') {
		$julk_id =  pg_fetch_object($result)->id;
	   }

          array_push($result_id, pg_fetch_object($result)->id); 

       }
   

    break;
  case 'DELETE':
      
      $sql = "delete from \"$table\" where $col=$1";
      $params=array($key);
      error_log($sql);
     
     // excecute SQL statement
     $result = pg_query_params($dbconn,$sql,$params);

     if (pg_affected_rows($result) == 0){
	http_response_code(204);
	die(pg_last_notice($dbconn));
     }

     // die if SQL statement failed
     if (!$result) {
          http_response_code(404);
         die(pg_last_notice($dbconn));
     }

  break;
}


/*
 * Update access rights and modifications log if necessary 
 */ 

  if ($julk_id > 0 || ($p_role == 'owner' && $method != 'GET'))  {
	
	$sql = "insert into \"kaytto_loki\" (name, mail, uid, julkaisu, organization, role, itable, action, data) values" 
		." ($1,$2,$3,$4,$5,$6,$7,$8,$9) returning id";

        $params=array($p_name);
        array_push($params, $p_mail);
	array_push($params, $p_uid);
        array_push($params, $julk_id);
	array_push($params, $p_org);
	array_push($params, $p_role);
	array_push($params, $table);
	array_push($params, $method);
	array_push($params, json_encode($input));
        
	$result2 = pg_query_params($dbconn,$sql,$params);

	if ($result2) {
            $accessid = pg_fetch_object($result2)->id;
	    $sql = "update julkaisu set accessid = $accessid where id = $1";
	    $params=array($julk_id);
            error_log($sql);
            $result = pg_query_params($dbconn,$sql,$params);

	}
    
	 if (!$result) {
             http_response_code(404);
             die(pg_last_notice($dbconn));
         }
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
  # echo pg_fetch_object($result)->id;

     if (count($result_id)>1) {
         echo implode(', ', $result_id);
     }
     else {
       echo $result_id[0];
     }


} else {
  echo pg_affected_rows($result);
}

} else {
   http_response_code(403);
   die(pg_last_notice($dbconn));
}
 
// clean up & close
pg_free_result($result);
pg_close($dbconn);
?>
