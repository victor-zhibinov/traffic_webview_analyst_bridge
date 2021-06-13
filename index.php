<?php
header('Content-Type: application/json');
/*
  Tested in php 7.1

в массивы ниже можно добавлять синонимы, или удалять. Скрипт будет узнавать синоним как соотв. команду,
 или наоборот если его удалить

*/ 

define('ACTION_OPTION_SYNONYMS', array("operate", "action")); // название параметра перед знаком равно для обозначения действия

define('GET_ACTION_SYNONYMS', array('get','find','take',''));
define('PUT_ACTION_SYNONYMS', array('create','update','new','put'));
define('HASH_SYNONYMS', array('hash','id','userid','user_id','app_id','appid'));
define('PACKAGE_SYNONYMS', array('package','namepacket','package_name','name_package'));



abstract class ACTIONS {
   const Put = 'put';
   const Get = 'get';
   const GetRegistrations = 'get_registrations';
   const GetClicks = 'get_clicks';
   const AddRegistration = 'add_registration';
   const AddClick = 'add_click';
   const Undefined = 'uf';
}

abstract class DbSettings {
	const Host = '127.0.0.1';
	const User = 'root';
	const Db = 'happies';
	const Pass = 'Enabeb212';
	const Charset = 'utf8';
}


interface IDb {
  public function __construct( $user_id,  $package);
  public function create_user( $count_deps, $count_regs, $count_clicks ) ;
  public function update_user( $count_deps) ;
  public function update_regs( $regs_up_number );
  public function get_count_deps() ; 
  public function issue_user();
  public function get_count_registrations();
  public function get_count_clicks();
}

abstract class Database implements IDb {
	
  abstract protected function query_create_user( $user_id,  $package,  $count_deps, $count_regs, $count_clicks) ;
  abstract protected function query_update_user( $user_id,  $package,  $count_deps) ;
  abstract protected function query_get_count_deps( $user_id,  $package) ;
  abstract protected function query_issue_user( $user_id,  $package) ;
  abstract protected function query_update_regs( $user_id, $package, $regs_up_number);
  abstract protected function db_connect();

  abstract protected function query_get_count_registrations( $user_id, $package );
  abstract protected function query_get_count_clicks( $user_id, $package );
      
  public function __construct( $user_id,  $package) 
  { 
    $this->_user_id = $user_id;
    $this->_package_name = $package;
    $this->db_connect();
  }

  public function issue_user() 
  {
    return $this->query_issue_user($this->_user_id, $this->_package_name);
  }

  public function create_user( $count_deps, $count_regs, $count_clicks)
  {
    return $this->query_create_user($this->_user_id, $this->_package_name, $count_deps, $count_regs, $count_clicks);
  }

  public function update_user( $count_deps)
  {
    return $this->query_update_user($this->_user_id, $this->_package_name, $count_deps);
  }
        
  public function update_regs( $regs_up_number ) 
  {
    $this->query_update_regs($this->_user_id, $this->_package_name, $regs_up_number);
  }

  public function get_count_deps()
  {
    return $this->query_get_count_deps($this->_user_id, $this->_package_name);
  }

  public function get_count_registrations()
  {
    return $this->query_get_count_registrations($this->_user_id, $this->_package_name);
  }
         
  public function get_count_clicks() 
  {
    return $this->query_get_count_clicks($this->_user_id, $this->_package_name);
  }


  protected $_user_id;
  protected $_package_name;
}


define('DONT_HAVE_DEPS', 0);
class MysqlDatabase extends Database {
  protected function db_connect() 
  {
        
        $host = DbSettings::Host;
        $db = DbSettings::Db;
        $charset = DbSettings::Charset;
   
      	$dsn = "mysql:host=$host;dbname=$db;charset=$charset;";
        $opt = [
           PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES   => false,
        ];
       
      	$this->_connection = new PDO($dsn, DbSettings::User, DbSettings::Pass, $opt);

  }

  protected function query_create_user( $user_id,  $package,  $count_deps, $count_regs, $count_clicks)  { 
    $sql = "INSERT INTO users (hash, package, deps, regs, click) VALUES (?,?,?,?,?)";
    $stmt= $this->_connection->prepare($sql);
    $stmt->execute([$user_id, $package, $count_deps, $count_regs, $count_clicks]);
    return $count_deps; 
  }

  protected function query_update_user( $user_id,  $package,  $count_deps)  {
    $sql = "UPDATE users SET deps = ? WHERE hash = ? AND package = ?";
    $stmt= $this->_connection->prepare($sql);
    $stmt->execute([$count_deps, $user_id, $package]);
    return $count_deps;
  }

  protected function query_update_regs( $user_id, $package, $regs_up_number)
  {
    $sql = "UPDATE users SET regs = ? WHERE (hash = ? AND package = ?)";
    $stmt = $this->_connection->prepare($sql);
    $stmt->execute([$regs_up_number, $user_id, $package]);
    return $regs_up_number;
  }

  protected function query_get_count_deps( $user_id,  $package)
  {
    $deps = $this->_connection->prepare('SELECT deps FROM users WHERE (hash = ? AND package = ?)');
    $result = $deps->execute([$user_id, $package]);
    $data = $deps->fetch();
    return ($data['deps'] != null) ? $data['deps'] : DONT_HAVE_DEPS;
  } 

  protected function query_get_count_clicks($user_id, $package)
  {
    $clicks = $this->_connection->prepare('SELECT click FROM users WHERE (hash = ? AND package = ?)');
    $result = $clicks->execute([$user_id, $package]);
    $data = $clicks->fetch();
    return ($data['click'] != null) ? $data['click'] : 0;
  }

  protected function query_get_count_registrations($user_id, $package)
  {
    $regs = $this->_connection->prepare('SELECT regs FROM users WHERE (hash = ? AND package = ?)');
    $result = $regs->execute([$user_id, $package]);
    $data = $regs->fetch();
    return ($data['regs'] != null) ? $data['regs'] : 0;
  }
	
  protected function query_issue_user( $user_id,  $package) 
  {        
    if($this->_countDeps == DONT_HAVE_DEPS) 
      $this->_countClicks = $this->query_get_count_clicks($user_id, $package);
       
    return ($this->_countClicks != DONT_HAVE_DEPS  ); 
   }
      
  private  $_countDeps = DONT_HAVE_DEPS;
  private $_connection;
}


function search_action_sign( $key) 
{
  return in_array(strtolower($key), ACTION_OPTION_SYNONYMS);
}

function search_get_action( $value) 
{
  return in_array(strtolower($value), GET_ACTION_SYNONYMS);
}

function search_put_action( $value) 
{
  return in_array(strtolower($value), PUT_ACTION_SYNONYMS);
}

function search_package_synonyms( $value) 
{
  return in_array(strtolower($value), PACKAGE_SYNONYMS);
}

function search_hash_synonyms( $value) 
{
  return in_array(strtolower($value), HASH_SYNONYMS);
}
 


interface IRequestData {
	public function getPackage();
	public function getHash();
	public function getAction();
}

define('PACKAGE_NULL', '');
define('HASH_NULL', '');
define('ACTION_NULL', '');

class RequestData implements IRequestData {

  public function __construct( $action,  $hash,  $package)
  {
  
    RequestData::checkValidity($hash, $action, $package);
  
    $this->_action = $action;
    $this->_hash = $hash;
    $this->_package = $package;
  
  }
     
  public function getPackage()  { return $this->_package; }
  public function getHash()  { return $this->_hash; }
  public function getAction()  {  return $this->_action; }
    
  public static function checkValidity($hash, $action, $package) 
  {
    if($hash == HASH_NULL || $package == PACKAGE_NULL) throw new Exception("Invalid request data", 1);
  }
   

  protected  $_hash;
  protected  $_action;
  protected  $_package;

}

interface ITreatingRequest {
	public function __construct(array $request);
	public function processing();
	public function getRequestData();
}

class TreatingRequest implements ITreatingRequest {
	
  public function __construct(array $request) 
  { 
     $this->_request = $request;
  }
    
  public function processing() 
  {
    $_action = ACTION_NULL;
    $_package = PACKAGE_NULL;
    $_hash = HASH_NULL;

    foreach ($this->_request as $key => $value) 
    {  	
    
  	  if( search_action_sign($key) )
  	  {
 
        if(search_get_action($value)) { 
        
          $_action = ACTIONS::Get;
        
        } else if(search_put_action($value)) 
        
          $_action = ACTIONS::Put;
        
        } else {

          switch($value) {
 
            case(ACTIONS::GetRegistrations);
              $_action = ACTIONS::GetRegistrations;
            break; 
 
            case(ACTIONS::AddRegistration);
              $_action = ACTIONS::AddRegistration;
            break; 
 
            case(ACTIONS::GetClicks); 
              $_action = ACTIONS::GetClicks;
            break; 
 
            case(ACTIONS::AddClick); 
              $_action = ACTIONS::AddClick;
            break; 
          
            default;
            break;
          }

        }
 
      }

  	
      if( search_hash_synonyms($key) )
    	{
  	    $_hash = $value;
     	}
 
      if( search_package_synonyms($key) )
	    {
 	      $_package = $value;
	    }
    
    }
 
    $this->_requestData = new RequestData($_action, $_hash, $_package);
    return $this;
  }
    
  public function getRequestData() 
  {   
    return $this->_requestData;
  }

private $_requestData;
private $_request;
}


define('COUNT_DEPS_TO_UP_WHEN_UPDATE', 1);  
define('COUNT_DEPS_TO_UP_WHEN_CREATE', 1);


class Logic {

  public function __construct(IRequestData &$requestData, IDb &$database ) 
  {
    $this->_requestData = $requestData;
    $this->_database = $database;	
  }
     
  private function action_put()  
  {
    if( $this->_database->issue_user() ) 
    {
      $this->_database->update_user($this->_database->get_count_deps() + COUNT_DEPS_TO_UP_WHEN_UPDATE);
      $this->_database->update_regs(1);
    }
    else 
    {
      $this->_database->create_user(COUNT_DEPS_TO_UP_WHEN_CREATE, 1, 1);
    } 

  }

  private function action_add_click() 
  { 
    if( !$this->_database->issue_user() )
    {
      $this->_database->create_user(0, 0, 1);  
    } else {
    }
  }

  private function action_add_registration() 
  {
    if( !$this->_database->issue_user() )
    {
      $this->_database->create_user(0, 1, 1);
    } else {
      $this->_database->update_regs(1);
    }
  }
   
  private function action_get_regs() 
  {
    return $this->_database->get_count_registrations();
  }

  private function action_get_clicks()
  {  
    return $this->_database->get_count_clicks();
  }

  private function action_get() 
  {
    return $this->_database->get_count_deps();
  }

  public function start() 
  {
    switch ( $this->_requestData->getAction() ) {
      case ACTIONS::Put;
        $this->action_put();
      break;
      case ACTIONS::GetRegistrations;
         echo json_encode($this->action_get_regs());
      break;
      case ACTIONS::GetClicks;
         echo json_encode($this->action_get_clicks());
      break;
      case ACTIONS::AddClick;
         $this->action_add_click();
      break;
      case ACTIONS::AddRegistration;
         $this->action_add_registration();
      break;
      case ACTIONS::Get;
      default:
        echo json_encode($this->action_get());
      break;
    }
  } 
    
  private $_database;
  private $_requestData;

}

function Main()
{
   $treatingRequest = new TreatingRequest($_REQUEST);
   $requestData = $treatingRequest->processing()->getRequestData();
   $mysqlDatabase = new MysqlDatabase($requestData->getHash(), $requestData->getPackage());
   $logic = new Logic($requestData, $mysqlDatabase);
   $logic->start();
}
Main();

