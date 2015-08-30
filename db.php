<?php
define("DB_DRIVER","mysql");
define("DB_HOST","localhost");
define("DB_NAME","peoples");
define("DB_PORT","3306");
define("DB_USER","root");
define("DB_PASS","root");
/***********************************************************************************/
class DB{
	protected $pdo 	 = null;
	protected $query = null;
	protected $where = null; 
	protected $select= null;
	protected $limit = null;
	protected $into  = null;
	protected $orderby = null;
	protected $groupby = null;
	protected $between = null;
	protected $prepare_obj;
	protected static $table = null;
	protected static $param_values=array();
	
	function __construct(){
		if($this->pdo === NULL)
		try{
			switch(DB_DRIVER){
				case 'mysql':
					$this->pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASS); break;
				case 'sqlite':
					$this->pdo = new PDO('sqlite:' . DB_NAME . '.db'); break;
				default:
					Throw new Exception('Database driver not supported!'); break;
			}
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e){
			trigger_error("Connection failed: " . $e->getMessage());
		}
	}
	static function table($table){
		self::$table = $table;
		return new DB;
	}
	function run($data=null){
		$query = $this->select . $this->where . $this->orderby . $this->limit ; //echo $query; die;
        $this->prepare_obj = $this->pdo->prepare($query);
		if(!isset($data)){
			$data = self::$param_values;
		}else{
			$data = array_merge($data, self::$param_values);
		}
        try{
			return $this->prepare_obj->execute($data);
		}
		catch(PDOException $e){
			echo "<pre>Query: " . $query . "<br>Execution error - " . $e->getMessage(). "</pre>";
			return false;
		}
	}
	function insert($data){
		$keys = array_keys($data);
		$q_mark = substr(str_repeat('?,',count($keys)), 0, -1);
		$this->select = "INSERT INTO `" . self::$table . "` (" . implode(",", array_keys($data) ) . ") VALUES ($q_mark);";
		if($this->run(array_values($data))){
			return $this->pdo->lastInsertId();
		}else{
			return false;
		}
	}
	function where(){
		$args = func_get_args();
		$where = array_shift($args);
		if(count($args) > 0){
			foreach($args as $arg){
				self::$param_values[] = $arg;
			}
		}
		$this->where = " WHERE " . $where;
		return $this;
	}
	function limit(){
		$args = func_get_args();
		if( count($args) > 1 ){
			self::$param_values[] = array_shift($args);
			self::$param_values[] = array_shift($args);
			$this->limit = " LIMIT ?, ?";
		}else{
			self::$param_values[] = array_shift($args);
			$this->limit = " LIMIT ?";
		}
		return $this;
	}
	function orderBy(){
		$args = func_get_args();
		if( count($args) > 1 ){ 
			self::$param_values[] = array_shift($args);
			self::$param_values[] = array_shift($args);
			$this->orderby = " ORDERBY ? ?";
		}else{
			self::$param_values[] = array_shift($args);
			$this->orderby = " ORDERBY ? ASC";
		}
		return $this;
	}
	public function lastInsertId(){
		return $this->pdo->lastInsertId();
	}
	function update($data){
		$keys = array_keys($data);
		$string = '';
		foreach($keys as $key){
			$string .= "$key = ?,";
		}
		$string = explode(',',$string);
		array_pop($string);
		$string = implode(', ',$string);
		$this->select = "UPDATE `" . self::$table . "` SET $string";
		return $this->run(array_values($data));
	}
	function delete(){
		$this->select = "DELETE FROM `" . self::$table."`";
		return $this->run();
	}
	function emptyTable(){ 
		$this->select = "TRUNCATE TABLE `" . self::$table."`";
		return $this->run();
	}
	function select($column = '*'){
		$this->select = "SELECT $column FROM `" . self::$table."`";
		return $this;
	} 
	function getAll($column = '*'){
		$this->select($column);
		$this->run();
		$velue = $this->prepare_obj->fetchAll( PDO::FETCH_ASSOC);
		return isset($velue) ? $velue : false;
	}
	function getRow($column = '*'){
		$this->select($column);
		$this->run();
		$velue = $this->prepare_obj->fetch( PDO::FETCH_ASSOC );
		return isset($velue) ? $velue : false;
	}
	function getRowObject($column = '*'){
		$this->select($column);
		$this->run();
		$velue = $this->prepare_obj->fetch( PDO::FETCH_OBJ );
		return isset($velue) ? $velue : false;
	}
	function getColumn($column){
		$this->select($column);
		$this->run();
		$velue = $this->prepare_obj->fetchAll( PDO::FETCH_COLUMN );
		return isset($velue) ? $velue : false;
	}
	function getValue($column){
		$this->select($column);
		$this->run();
		$velue = $this->prepare_obj->fetch( PDO::FETCH_COLUMN );
		return isset($velue) ? $velue : false;
	}
	function rowCount(){
		$this->select('*');
		$this->run();
		$velue = $this->prepare_obj->rowCount();
		return isset($velue) ? $velue : false;
	}
}
/*
************ Usage ************
$data = array(
			'id' => 1,
			'username' => "MD Rejaul",
			'name' => "shefali Begum",
			'address' => "Latifpur Colony",
			'phone' => "01717456789"
		);
//$result = DB::table('people')->rowCount();
//$result = DB::table('people')->where()->rowCount();
//$result = DB::table('people')->insert($data);
//$result = DB::table('people')->where("id = ?", 2)->update($data);
//$result = DB::table('people')->where("id = ?", 4)->delete();
//$result = DB::table('people')->emptyTable();
//$result = DB::table('people')->getAll();
//$result = DB::table('people')->where()->getAll('col1, col2');
//$result = DB::table('people')->getColumn('col1');	
//$result = DB::table('people')->where()->getColumn('col1');
//$result = DB::table('people')->where("username = ?", "shamim")->getRow();
//$result = DB::table('people')->where()->getRow('col1, col2');
//$result = DB::table('people')->where()->getRowObject();
//$result = DB::table('people')->where()->getRowObject('col1, col2');
//$result = DB::table('people')->where()->getValue('col');
//$result = DB::table('people')->limit(1)->getAll();
//$result = DB::table('people')->where("id > ?", 20)->limit(5,10)->getAll();
//$result = DB::table('people')->where("id < ?", 20)->orderBy('id', 'DESC')->limit()->getAll();
//$result = DB::table('people')->where()->orderBy()->limit()->getAll();

print"<pre>";
//print_r ($result);
print"</pre>";
*/
