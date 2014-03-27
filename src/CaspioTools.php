<?php
class CaspioTools
{
   private $SoapClient;
   private $AccountID; //caspio bridge account ID
   private $ProfileName; //web services profile name
   private $Password; //web services profile password
   function __construct($AccountID, $ProfileName, $Password) {
      $this->AccountID = $AccountID;
      $this->ProfileName = $ProfileName;
      $this->Password = $Password;
      $this->SoapClient = new SoapClient("https://b5.caspio.com/ws/api.asmx?wsdl"); //TODO: Make Bridge a passed parameter
      
      //TODO Cookie/cache expiration timer
      session_start();
   }
   
   //inserts data into a table
   public function insert($table, $values) {
      if($values == null) { //TODO remove this
         return $false;
      }
   
      $fields = '';
      $query_values = "";
      
      foreach($values as $field => $value) {
         $fields .= $field . ', ';
         $query_values .= "'{$value}'" . ", ";
      }
      $fields = substr($fields, 0, strlen($fields) - 2);
      $query_values = substr($query_values, 0, strlen($query_values) - 2);

      //TODO make this XML
      try {
         $insert_result = $this->SoapClient->InsertData($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $fields, $query_values);
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
         return false;
      }
      return $insert_result;
   }
   
   //fetches rows which match criteria, storing fields into associative array
   public function fetch($table, $fields, $criteria = '') {
      $query_fields = '';
      foreach($fields as $field) {
            $query_fields .= $field . ', ';
      }
      //TODO make this XML
      try {
         $select_result = $this->SoapClient->SelectDataRaw($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $query_fields, $criteria, '', '$|CTLS$|', ' ');
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
         return false;
      }

      for($i = 0; $i < count($select_result); $i++) {
         $row_values = explode('$|CTLS$|', $select_result[$i]);
         for($j = 0; $j < count($fields); $j++) {
            if(trim($row_values[$j]) === 'NULL') { //clean out NULL strings
               $row_values[$j] = NULL;
            }
            $results[$i]["{$fields[$j]}"] = trim($row_values[$j]);
         }
      }
      return $results;
   }
   
   //updates data in a table, values is an associative array of field-value pairs
   //returns number of columns updated
   public function update($table, $values, $criteria) {
      if($values == null) { //TODO remove this
         return $false;
      }
   
      $fields = '';
      $query_values = "";
      
      foreach($values as $field => $value) {
         $fields .= $field . ', ';
         $query_values .= "'{$value}'" . ", ";
      }
      $fields = substr($fields, 0, strlen($fields) - 2);
      $query_values = substr($query_values, 0, strlen($query_values) - 2);

      //TODO make this XML
      try {
         $update_result = $this->SoapClient->UpdateData($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $fields, $query_values, $criteria);
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
         return false;
      }
      return $update_result;
   }
   
   //check if user is logged in
   public function logged_in() {
      if(isset($_SESSION) && isset($_SESSION['userinfo'])) {
         return true;
      } else {
         return false;
      }
   }
   
   //returns true on successful login, false on non
   public function login($username, $password, $table, $username_field, $password_field, $userinfo_fields) {
      if($this->logged_in()) { //already logged in!
         return true;
      }
      
      $params = array(
         'AccountID' => $this->AccountID, 
         'Profile' => $this->ProfileName,
         'Password' => $this->Password,
         'ObjectName' => $table,
         'IsView' => false,
         'PasswordFieldName' => $password_field,
         'PasswordValue' => $password,
         'Criteria' => "$username_field='{$username}'",
         'OrderBy' => ''
      );
      
      $loggedin = false;
      while(!$loggedin) { //keep trying incase CheckPassword is timed out
         try {
            $login_result = $this->SoapClient->CheckPassword($params);
            $loggedin = true;
         } catch(SoapFault $e) {
            if($e->faultstring === '{CheckPassword API cannot be called at this time.}') {
               //sleep(5); //wait 5 seconds and try again
            } else {
               //TODO Elegantly handle failures
               return false;
            }
         }
      }
      if(isset($login_result->CheckPasswordResult->Row)) { //user logged in successfully
         //grab their userinfo now
         $fields = '';
         foreach($userinfo_fields as $field) {
            $fields .= $field . ', ';
         }
         
         try {
            $select_result = $this->SoapClient->SelectDataRaw($this->AccountID, $this->ProfileName, $this->Password, 
               $table, false, $fields, "$username_field='{$username}'", '', '$|CTLS$|', ' ');
         } catch(SoapFault $e) {
            //TODO Elegantly handle failures
            return false;
         } 

         $field_values = explode('$|CTLS$|', $select_result[0]);
         for($i = 0; $i < count($userinfo_fields); $i++) {
            $userinfo["{$userinfo_fields[$i]}"] = trim($field_values[$i]);
         }
         
         $_SESSION['userinfo'] = $userinfo;
         return true;
      } else { //incorrect username/password
         //TODO handle failed login
      }
   }
   
   //logs the user out, must be called before content is sent (with headers)
   public function logout() {
      // Unset all of the session variables, cookie, and destroy session
      $_SESSION = array();
      if (ini_get("session.use_cookies")) {
         $params = session_get_cookie_params();
         setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
         );
      }
      session_destroy();
   }
}
?>