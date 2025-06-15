<?php

class db extends mysqli ///< http://php.net/manual/en/class.mysqli.php
{
 const ERROR_SELECT = 'Error reading data from the database';
 const ERROR_INSERT = 'Error inserting data to the database';
 const ERROR_UPDATE = 'Error updating data in the database';
 const ERROR_DELETE = 'Error deleting data from the database';

 private static $db;// = createInstance();
 private static $queries = [];
 private $lastQuery = null;
 private $lastError = null;
 private $insertId = 0;

 static function queries() { return self::$queries; }
 function __get($name)
 {
  if ($name == 'query')
   return $this->lastQuery;
  return parent::__get($name);
 }

 private static function cli()
 {
  return php_sapi_name() === 'cli';
 }

 private static function web()
 {
  return php_sapi_name() !== 'cli';
 }

 static function lastQuery()
 {
  if (self::cli())
   return $this->lastQuery;
  if (count(self::$queries))
   return self::$queries[count(self::$queries) - 1];
 }

 static function lastError()
 {
  return self::$db ? self::$db->error : null;
 }

 private function addQuery($query)
 {
  if (self::cli())
   $this->lastQuery = $query;
  else
   self::$queries[] = $query;
 }

 private function addError()
 {
  if (!$this->error)
   return;
  if (self::cli())
   $this->lastQuery = [$this->lastQuery, $this->error];
  else
  {
   $index = count(self::$queries) - 1;
   if ($index >= 0)
    self::$queries[$index] = [self::$queries[$index], $this->error];
  }
 }

 static function instance()
 {
  if (!self::$db)
   self::createDefault();
  return self::$db;
 }

 static function createDefault($tmp = null)
 {
  return self::createFromConfigFile(str_replace('.php', '.conf', __FILE__), $tmp);
 }

 static function createFromConfigFile($filename, $tmp = null)
 {
  //throw new dberr($filename);
  return self::createFromConfig(file_get_contents($filename), $tmp);
 }

 static function createFromConfig($json, $tmp = null)
 {
  if (!$json)
   self::error('No config');
  //print_r($json);

  $conf = json_decode($json);
  if (!$conf || !is_object($conf))
   self::error('Invalid config: ' . $json);
  //print_r($conf);

  if (!property_exists($conf, 'database') || !$conf->database)
   self::error('No database in config');
  if (!property_exists($conf, 'username') || !$conf->username)
   self::error('No username in config');
  if (!property_exists($conf, 'password') || !$conf->password)
   self::error('No password in config');

  return self::create($conf->database, $conf->username, $conf->password, $tmp);
 }

 static function create($database, $username, $password, $tmp = null)
 {
  $db = new db($database, $username, $password);
  if ($db->connect_errno != 0)
   self::error('Connection error: ' . $db->connect_errno);
  if (!$tmp)
   self::$db = $db;
  return $db;
 }

 function __construct($database, $username, $password)
 {
  $this->addQuery('use ' . $database);
  parent::__construct(null, $username, $password, $database);
  if (!mysqli_connect_error()) // http://php.net/manual/ru/mysqli.construct.php
   $this->set_charset('utf8');
  $this->addError();
 }

 function __destruct()
 {
  if (mysqli_connect_error())
   return;
  $saved_display_errors = ini_get('display_errors');
  ini_set('display_errors', '0');
  $this->close();
  ini_set('display_errors', $saved_display_errors);
 }

 /**
  * Add slashes and quotations to the text
  * @param string $value Text to process
  * @return string Quoted and slashed text
  */
 static function str($value)
 {
  return '\'' . addslashes($value) . '\'';
 }

 static function strn($value)
 {
  return strlen($value) ? ('\'' . addslashes($value) . '\'') : 'null';
 }

 static function int($value)
 {
  return intval($value);
 }

 static function intn($value)
 {
  if (is_int($value))
   return $value;
  return (($value != null) && strlen($value)) ? intval($value) : 'null';
 }

 static function ints($value)
 {
  if (is_int($value))
   return $value;
  return (($value != null) && strlen($value)) ? intval($value) : '';
 }

 static function money($value)
 {
  return number_format($value, 2, '.', '');
 }

 static function moneyn($value)
 {
  return is_numeric($value) ? number_format($value, 2) : 'null';
 }

 static function daten($value)
 {
  if (is_string($value))
   $value = util::str2date($value);
  return $value ? self::str(self::date2str($value)) : 'null';
 }

 static function date2str($value, $def = '')
 {
  return ($value instanceof DateTime) ? $value->format('Y-m-d') : $def;
 }

 static function str2date($value, $def = null)
 {
  //return self::nvl(DateTime::createFromFormat('Y-m-d', $value), $def);
  if (fnmatch('????-??-??', $value))
  {
   $date = new DateTime();
   $date->setDate(intval(substr($value, 0, 4)), intval(substr($value, 5, 2)), intval(substr($value, 8, 2)));
   return $date;
  }
  return $def;
 }

 static function datetime2str($value, $def = '')
 {
  return ($value instanceof DateTime) ? $value->format('Y-m-d H:i:s') : $def;
 }

 static function str2datetime($value, $def = null)
 {
  //return self::nvl(DateTime::createFromFormat('Y-m-d', $value), $def);
  if (fnmatch('????-??-?? ?:??:??', $value))
  {
   $date = new DateTime();
   $date->setDate(intval(substr($value, 0, 4)), intval(substr($value, 5, 2)), intval(substr($value, 8, 2)));
   $date->setTime(intval(substr($value, 11, 1)), intval(substr($value, 13, 2)), intval(substr($value, 16, 2)));
   return $date;
  }
  if (fnmatch('????-??-?? ??:??:??', $value))
  {
   $date = new DateTime();
   $date->setDate(intval(substr($value, 0, 4)), intval(substr($value, 5, 2)), intval(substr($value, 8, 2)));
   $date->setTime(intval(substr($value, 11, 2)), intval(substr($value, 14, 2)), intval(substr($value, 17, 2)));
   return $date;
  }
  return $def;
 }

 function queryCols($table)
 {
  $result = [];
  if ($obj = $this->execSql('show columns from ' . $table))
   while ($fields = $obj->fetch_object())
    $result[] = $fields->Field;
  return $result;
 }

 static function cols($table)
 {
  return self::instance()->queryCols($table);
 }

 function queryCols2($table, $arr = null)
 {
  $result = [];
  if ($obj = $this->execSql('show columns from ' . $table))
   while ($fields = $obj->fetch_object())
    $result[$fields->Field] = $arr ? [] : null;
  return $result;
 }

 static function cols2($table, $arr = null)
 {
  return self::instance()->queryCols2($table, $arr);
 }

 //static function mapWhere($key, $value)
 //{
 // return $key . '=' . $value;
 //}

 /**
  * Make an SQL WHERE clause text
  * @param type $where
  * @return string String SQL WHERE clause or assoc_array
  */
 static function makeWhere($where)
 {
  if (!is_array($where))
   return "$where";
  if (!count($where))
   return '';
  //return implode(' and ', array_map(array(__CLASS__, 'mapWhere'), array_keys($where), $where));
  return implode(' and ', array_map(function($k, $v) { return "$k=$v"; }, array_keys($where), $where));
 }

 /**
  * Add an SQL WHERE clause text to the SQL text
  * @param out ref string $sql SQL text to modify
  * @param string $where String SQL WHERE clause or assoc_array
  */
 private static function addWhere(&$sql, $where)
 {
  $where = self::makeWhere($where);
  if ($where)
   $sql .= ' where ' . $where;
 }

 /**
  * Make an SQL query text
  * @param string $table Database table name
  * @param string $fields An asterisk sign or comma-separated list of the field names to retrieve
  * @param mixed $where String SQL WHERE clause or assoc_array
  * @param string $order String SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $offset Number of records to skip at the beginning
  * @return string SQL query text
  */
 private static function makeQuerySelect($table, $fields = null, $where = null, $order = null, $limit = null, $offset = null)
 {
  $sql = 'select ' . ($fields ? $fields : '*');
  if (strlen(trim($table)))
  {
   $sql .= ' from ' . $table;
   self::addWhere($sql, $where);
   if (strlen(trim($order)))
    $sql .= ' order by ' . $order;
  }
  if ($limit)
   $sql .= ' limit ' . $limit . ($offset ? (' offset ' . $offset) : '');
  return $sql;
 }

 static function makeQueryInsert($table, array $values)
 {
  return 'insert into ' . $table . ' (' . implode(',', array_keys($values)) . ') values (' . implode(',', $values) . ')';
 }

 static function makeQueryUpdate($table, array $values, $where = null)
 {
  $sql = 'update ' . $table . ' set ' . implode(',', array_map(function($f, $v) { return $f . '=' . $v; }, array_keys($values), $values));
  self::addWhere($sql, $where);
  return $sql;
 }

 /**
  * Get a field value from the single record
  * @param string $table Database table name
  * @param string $field Database table field name
  * @param string $where Database table filter value
  * @param string $order Records sort order
  * @return mixed Field value or null
  */
 function queryField($table, $field, $where = null, $order = null)
 {
  $result = null;
  $sql = self::makeQuerySelect($table, $field, $where, $order);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($cursor = $this->use_result()) && ($record = $cursor->fetch_row()))
   $result = $record[0];
  $this->addError();
  return $result;
 }

 /**
  * Get a field value from the single record
  * @param string $table Database table name
  * @param string $field Database table field name
  * @param string $where Database table filter value
  * @param string $order Records sort order
  * @return mixed Field value or null
  */
 static function field($table, $field, $where = null, $order = null)
 {
  return self::instance()->queryField($table, $field, $where, $order);
 }

 /**
  * Get field values from the single record
  * @param string $table Database table name
  * @param string $fields Comma-separated field list
  * @param string $where SQL filter expression
  * @param string $order SQL sort order expression
  * @return array Specified field values
  */
 function queryFields($table, $fields = null, $where = null, $order = null)
 {
  $result = null;
  $sql = self::makeQuerySelect($table, $fields, $where, $order);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($records = $this->store_result()))
  {
   $result = $records->fetch_row();
   $records->free();
  }
  $this->addError();
  return $result;
 }

 /**
  * Get field values from the single record
  * @param string $table Database table name
  * @param string $fields Comma-separated field list
  * @param string $where SQL filter expression
  * @param string $order SQL sort order expression
  * @return array Specified field values
  */
 static function fields($table, $fields = null, $where = null, $order = null)
 {
  return self::instance()->queryFields($table, $fields, $where, $order);
 }

 /**
  * Get field values from the database table
  * @param string $table Database table name
  * @param string $fields Comma-separated field list
  * @param string $where SQL filter expression
  * @param string $order SQL sort order expression
  * @return array Specified field values
  */
 function queryFields2($table, $fields = null, $where = null, $order = null)
 {
  $result = null;
  $sql = self::makeQuerySelect($table, $fields, $where, $order);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($records = $this->store_result()))
  {
   $result = $records->fetch_array(MYSQLI_ASSOC);
   $records->free();
  }
  $this->addError();
  return $result;
 }

 /**
  * Get field values from the database table
  * @param string $table Database table name
  * @param string $fields Comma-separated field list
  * @param string $where SQL filter expression
  * @param string $order SQL sort order expression
  * @return array Specified field values
  */
 static function fields2($table, $fields = null, $where = null, $order = null)
 {
  return self::instance()->queryFields2($table, $fields, $where, $order);
 }

 /**
  * Retrieve data records from the database
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-empty records array or null
  */
 function queryRows($table, $fields = null, $where = null, $order = null, $limit = null, $skip = null)
 {
  //echo "queryRows('$table','$fields','$where','$order','$limit','$skip')<br>\n";
  $result = [];
  $sql = self::makeQuerySelect($table, $fields, $where, $order, $limit, $skip);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($records = $this->use_result()))
  {
   while ($record = $records->fetch_row())
    $result[] = (count($record) == 1) ? $record[0] : $record;
   $records->close();
  }
  $this->addError();
  return $result;
 }

 /**
  * Retrieve data records from the database
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-empty records array or null
  */
 static function rows($table, $fields = null, $where = null, $order = null, $limit = null, $skip = null)
 {
  return self::instance()->queryRows($table, $fields, $where, $order, $limit, $skip);
 }

 /**
  * Retrieve data records from the database in 2D array(array(field => value)) or array(value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 function queryRows2($table, $fields = null, $where = null, $order = null, $limit = null, $skip = null)
 {
  //echo "queryRows2('$table','$fields','$where','$order','$limit','$skip')<br>\n";
  $result = [];
  $key = null;
  $sql = self::makeQuerySelect($table, $fields, $where, $order, $limit, $skip);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($records = $this->use_result()))
  {
   while ($record = $records->fetch_array(MYSQLI_ASSOC))
   {
    if (count($record) == 1)
    {
     if (is_null($key))
      $key = array_keys($record)[0];
     $result[] = $record[$key];
    }
    else
     $result[] = $record;
   }
   $records->close();
  }
  $this->addError();
  return $result;
 }

 /**
  * Retrieve data records from the database in 2D array(array(field => value)) or array(value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 static function rows2($table, $fields = null, $where = null, $order = null, $limit = null, $skip = null)
 {
  return self::instance()->queryRows2($table, $fields, $where, $order, $limit, $skip);
 }

 private function queryMatrixInternal($table, $fields, $where, $order, $limit, $skip, $useNames)
 {
  //echo "queryMatrixInternal('$table','$fields','$where','$order','$limit','$skip')<br>\n";
  $result = [];
  $sql = self::makeQuerySelect($table, $fields, $where, $order, $limit, $skip);
  $this->addQuery($sql);
  if ($this->real_query($sql) && ($records = $this->use_result()))
  {
   $names = array_map(function($f) { return $f->name; }, $records->fetch_fields());
   $fieldCount = count($names);
   while ($record = $records->fetch_row())
   {
    if ($fieldCount == 1)
     $row = null;
    else if ($fieldCount == 2)
     $row = $record[1];
    else
    {
     $row = [];
     for ($i = 1; $i < count($names); $i++)
      if ($useNames)
       $row[$names[$i]] = $record[$i];
      else
       $row[] = $record[$i];
    }
    $result[$record[0]] = $row;
   }
   $records->close();
  }
  $this->addError();
  return $result;
 }

 /**
  * Retrieve data records from the database in 2D assoc_array(key => array(value)) or (key => value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list (the first is the key)
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 function queryMatrix($table, $fields, $where = null, $order = null, $limit = null, $skip = null)
 {
  //echo "queryMatrix('$table','$fields','$where','$order','$limit','$skip')<br>\n";
  return $this->queryMatrixInternal($table, $fields, $where, $order, $limit, $skip, false);
 }

 /**
  * Retrieve data records from the database in 2D assoc_array(key => array(value)) or (key => value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list (the first is the key)
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 static function matrix($table, $fields, $where = null, $order = null, $limit = null, $skip = null)
 {
  return self::instance()->queryMatrixInternal($table, $fields, $where, $order, $limit, $skip, false);
 }

 /**
  * Retrieve data records from the database in 2D assoc_array(key => array(field => value)) or array(value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list (the first is the key)
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 function queryMatrix2($table, $fields, $where = null, $order = null, $limit = null, $skip = null)
 {
  //echo "queryMatrix2('$table','$fields','$where','$order','$limit','$skip')<br>\n";
  return $this->queryMatrixInternal($table, $fields, $where, $order, $limit, $skip, true);
 }

 /**
  * Retrieve data records from the database in 2D assoc_array(key => array(field => value)) or array(value)
  * @param string $table Database table names comma-separated list
  * @param string $fields Database field names comma-separated list (the first is the key)
  * @param string $where SQL WHERE clause
  * @param string $order SQL ORDER BY clause
  * @param int $limit Maximum number of records to retrieve
  * @param int $skip Number of records to skip at the beginning
  * @return array Non-null records array
  */
 static function matrix2($table, $fields, $where = null, $order = null, $limit = null, $skip = null)
 {
  return self::instance()->queryMatrixInternal($table, $fields, $where, $order, $limit, $skip, true);
 }

 static function shortValues(array $values, $maxlen)
 {
  return array_map(function($v) use ($maxlen) { return strlen($v) > $maxlen ? "''" : $v; }, $values);
 }

 /**
  * Append a new record to database table
  * @param string $table Database table name
  * @param assoc_array $values Field names and their values as named pairs
  * @param serial boolean Modify serial after insert
  * @return bool Result of the operation
  */
 function insertValues($table, array $values, $serial = null)
 {
  $this->insertId = 0;
  $sql = self::makeQueryInsert($table, $values);
  if (strlen($sql) < 1024)
   $this->addQuery($sql);
  else
   $this->addQuery(self::makeQueryInsert($table, self::shortValues($values, 1000)));
  $result = $this->query($sql);
  $this->addError();
  $this->insertId = $this->insert_id;
  if ($result && $serial)
   $this->modifySerialAfterInsert($table);
  return $result;
 }

 /**
  * Append a new record to database table
  * @param string $table Database table name
  * @param assoc_array $values Field names and their values as named pairs
  * @param serial boolean Modify serial after insert
  * @return bool Result of the operation
  */
 static function insert($table, array $values, $serial = null)
 {
  return self::instance()->insertValues($table, $values, $serial);
 }

 static function insertId()
 {
  return self::instance()->insertId;
 }

 /**
  * Update the 'serial' field of the last inserted record by the 'id' value
  * @param string $table Database table name
  * @param int $id Record key value
  */
 function modifySerialAfterInsert($table, $id = null)
 {
  if (!strlen($id))
   $id = 'id';
  $this->updateRows($table, ['serial' => $id], 'id=' . $this->insertId);
 }

 static function updateSerial($table, $id = null)
 {
  return self::instance()->modifySerialAfterInsert($table, $id);
 }

 /**
  * Change the values of the database table fields
  * @param string $table Database table name
  * @param assoc_array $values Field names and their values as named pairs
  * @param string $where Database predicate (where clause)
  * @return bool Result of the operation
  */
 function updateRows($table, array $values, $where = null)
 {
  $sql = self::makeQueryUpdate($table, $values, $where);
  if (strlen($sql) < 1024)
   $this->addQuery($sql);
  else
   $this->addQuery(self::makeQueryUpdate($table, self::shortValues($values, 1000), $where));
  $result = $this->query($sql);
  $this->addError();
  return $result;
 }

 /**
  * Change the values of the database table fields
  * @param string $table Database table name
  * @param assoc_array $values Field names and their values as named pairs
  * @param string $where Database predicate (where clause)
  * @return bool Result of the operation
  */
 static function update($table, array $values, $where = null)
 {
  return self::instance()->updateRows($table, $values, $where);
 }

 function mergeRows($table, array $values, array $where, $serial = null)
 {
  if ($this->queryField($table, '1', $where))
   return $this->updateRows($table, $values, $where);
  foreach ($where as $field => $value)
   $values[$field] = $value;
  return $this->insertValues($table, $values, $serial);
 }

 static function merge($table, array $values, array $where, $serial = null)
 {
  return self::instance()->mergeRows($table, $values, $where, $serial);
 }

 function mergeField($table, $field, $value, array $where, $serial = null)
 {
  return $this->mergeRows($table, [$field => $value], $where, $serial);
 }

 /**
  * Delete records from the database table
  * @param string $table Database table name
  * @param string $where Database predicate (where clause)
  * @return bool Result of the operation
  */
 function deleteRows($table, $where = null)
 {
  $sql = 'delete from ' . $table;
  self::addWhere($sql, $where);
  //echo $sql;
  $this->addQuery($sql);
  $result = $this->query($sql);
  $this->addError();
  return $result;
 }

 /**
  * Delete records from the database table
  * @param string $table Database table name
  * @param string $where Database predicate (where clause)
  * @return bool Result of the operation
  */
 static function delete($table, $where = null)
 {
  return self::instance()->deleteRows($table, $where);
 }

 function execSql($sql)
 {
  $this->addQuery($sql);
  $result = $this->query($sql);
  $this->addError();
  return $result;
 }

 static function sql($sql)
 {
  return self::instance()->execSql($sql);
 }

 private static function error($text)
 {
  trigger_error($text);
  throw new dberr($text);
 }
}

class dberr extends Exception
{
 function __construct($text, $db = null)
 {
  parent::__construct($text);
  $this->query = $db ? $db->query : db::lastQuery();
  $this->descr = $db ? $db->error : db::lastError();
 }
}

?>
