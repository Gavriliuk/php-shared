<?php

class util
{
 /**
  * Safe extract a value from an assoc_array
  * @param array $array Array to search in
  * @param string $name Key value to search
  * @return string Extracted value or null
  */
 public static function item($array, $name)
 {
  if (($array != null) && (strlen($name) > 0))
  {
   if (is_array($array))
    return array_key_exists($name, $array) ? $array[$name] : null;
   if (is_object($array))
    return property_exists($array, $name) ? $array->$name : null;
  }
  return null;
 }

 public static function nextKey($array, $key)
 {
  return self::nearKey($array, $key, 1);
 }

 public static function prevKey($array, $key)
 {
  return self::nearKey($array, $key, -1);
 }

 public static function nearKey($array, $key, $offset)
 {
  if (is_null($array) || !count($array))
   return null;
  if (is_array($array))
  {
   $keys = array_keys($array);
   $index = array_search($key, $keys);
   if ($index === FALSE)
    return null;
   $index += $offset;
   if ($index < 0 || $index >= count($keys))
    return null;
   $key = $keys[$index];
   return $keys[$index];
  }
//  if (is_object($array))
//  {
//   return property_exists($array, $name) ? $array->$name : null;
//  }
  return null;
 }

 public static function nvl($first, $second)
 {
  return ($first !== null) ? $first : $second;
 }

 public static function date2str($value, $def = '')
 {
  return ($value instanceof DateTime) ? $value->format('d-m-Y') : $def;
 }

 public static function datetime2str($value, $def = '')
 {
  return ($value instanceof DateTime) ? $value->format('d-m-Y H:i:s') : $def;
 }

 public static function str2date($value, $def = null)
 {
  if (($value == null) || !is_string($value) || (strlen($value) != 10))
   return $def;
  if (fnmatch('??-??-????', $value))
  {
   $date = new DateTime();
   $date->setDate(intval(substr($value, 6, 4)), intval(substr($value, 3, 2)), intval(substr($value, 0, 2)));
   return $date;
  }
  return $def;
 }

 // https://en.wikipedia.org/wiki/Levenshtein_distance
 // function LevenshteinDistance(char s[0..m-1], char t[0..n-1])
 public static function ld($s, $t)
 {
  //$s = mb_strtolower($s);
  //$t = mb_strtolower($t);

  //$m = strlen($s);
  //$n = strlen($t);
  $m = mb_strlen($s);
  $n = mb_strlen($t);

  // create two work vectors of integer distances
  $v0 = array(); // array_fill(0, $n + 1, 0);
  $v1 = array(); // array_fill(0, $n + 1, 0);

  // initialize v0 (the previous row of distances)
  // this row is A[0][i]: edit distance for an empty s
  // the distance is just the number of characters to delete from t
  for ($i = 0; $i <= $n; ++$i)
   $v0[$i] = $i;

  // for i from 0 to m-1:
  for ($i = 0; $i < $m; ++$i)
  {
   // calculate v1 (current row distances) from the previous row v0

   // first element of v1 is A[i+1][0]
   //   edit distance is delete (i+1) chars from s to match empty t
   $v1[0] = $i + 1;

   // use formula to fill in the rest of the row
   for ($j = 0; $j < $n; ++$j)
   {
    // calculating costs for A[i+1][j+1]
    $deletionCost = $v0[$j + 1] + 1;
    $insertionCost = $v1[$j] + 1;

    //if ($s[$i] == $t[$j])
    if (mb_substr($s, $i, 1) == mb_substr($t, $j, 1))
     $substitutionCost = $v0[$j];
    else
     $substitutionCost = $v0[$j] + 1;

    $v1[$j + 1] = min($deletionCost, $insertionCost, $substitutionCost);
   }

   // copy v1 (current row) to v0 (previous row) for next iteration
   $v0 = $v1;
  }

  // after the last swap, the results of v1 are now in v0
  return $v0[$n];
 }

 public static function test()
 {
  return array(
    'DIR' => __DIR__,
    'FILE' => __FILE__,
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'PATH_INFO' => $_SERVER['PATH_INFO'],
    'PHP_SELF' => $_SERVER['PHP_SELF'],
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
    //'' => $_SERVER[''],
  );
 }
}

?>
