<?php

class split
{
 const SPACE = 0;
 const LITER = 1;
 const DIGIT = 2;
 const PUNCT = 3;

 public static function type($char)
 {
   return ctype_space($char) ? self::SPACE : (is_numeric($char) ? self::DIGIT : (preg_match('/^\p{L}+$/u', $char) ? self::LITER : self::PUNCT));
 }

 public static function str($text)
 {
  return self::only($text, self::LITER);
 }

 public static function num($text)
 {
  return self::only($text, self::DIGIT);
 }

 /**
  * Split text onto fragments and filter fragments of the required type only
  * @param $text String Text to split
  * @param @need Integer Required type: 0 - space, 1 - literal, 2 - digit, 3 - symbol
  * @return Array of strings
  */
 public static function only($text, $need)
 {
  $data = array();
  $type = 0; // 0 - space, 1 - literal, 2 - digit, 3 - symbol
  $token = '';
  $len = mb_strlen($text);
  $pos = 0;
  while ($pos < $len)
  {
   $c = mb_substr($text, $pos++, 1);
   $t = self::type($c);
   if ($t != $type)
   {
    if (strlen($token))
    {
     $data[] = $token;
     $token = '';
    }
    $type = $t;
   }
   if ($type == $need)
    $token .= $c;
  }
  if (strlen($token))
   $data[] = $token;
  return $data;
 }

 /**
  * Split text onto fragments
  * @param $text String Text to split
  * @return Array of pairs [ type, token ] where type is 0..3 (space/literal/digit/symbol)
  */
 public static function all($text)
 {
  $data = array();
  $type = 0; // 0 - space, 1 - literal, 2 - digit, 3 - symbol
  $token = '';
  $len = mb_strlen($text);
  $pos = 0;
  while ($pos < $len)
  {
   $c = mb_substr($text, $pos++, 1);
   $t = self::type($c);
   if ($t == $type)
    $token .= $c;
   else
   {
    if ($type && strlen($token))
     $data[] = array($type, $token);
    $type = $t;
    $token = $c;
   }
  }
  if ($type && strlen($token))
   $data[] = array($type, $token);
  return $data;
 }
}

?>
