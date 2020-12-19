<?php

class uri
{
 private $proto;
 private $host;
 private $path;
 private $params;
 private $anchor;
 private $text;

 function __construct($uri = null)
 {
  $this->parse($uri);
 }

 function parse($text)
 {
  //self::debug('parse(' . self::encode($text) . ')');
  if (is_null($text))
   $text = (php_sapi_name() !== 'cli') ? $_SERVER['REQUEST_URI'] : '';
  else if (!is_string($text))
   $text = "$text";
  //self::debug('text: ' . self::encode($text));

  $pos = strpos($text, '#');
  if ($pos !== FALSE)
  {
   $this->anchor = substr($text, $pos + 1);
   $text = substr($text, 0, $pos);
  }
  else
   $this->anchor = null;
  //self::debug('anchor: ' . self::encode($this->anchor));

  $pos = strpos($text, '?');
  if ($pos !== FALSE)
  {
   $this->params = substr($text, $pos + 1);
   $text = substr($text, 0, $pos);
  }
  else
   $this->params = null;
  //self::debug('params: ' . self::encode($this->params));

  $pos = strpos($text, '://');
  if ($pos !== FALSE)
  {
   $this->proto = substr($text, 0, $pos);
   $text = substr($text, $pos + 3);
  }
  else
   $this->proto = null;
  //self::debug('proto: ' . self::encode($this->proto));

  $pos = strpos($text, '/');
  if ($pos !== FALSE)
  {
   $this->path = substr($text, $pos);
   $text = substr($text, 0, $pos);
  }
  else
   $this->path = null;
  //self::debug('path: ' . self::encode($this->path));

  $this->host = strlen($text) ? $text : null;
  //self::debug('host: ' . self::encode($this->host));

  $this->text = null;
  //self::debug(self::encode(['text' => $text, 'proto' => $this->proto, 'host' => $this->host, 'path' => $this->path, 'params' => $this->params, 'anchor' => $this->anchor]));
  //self::debug(self::encode($this->state()));
 }

 private function make()
 {
  //self::debug('make()');
  $this->text = '';
  //self::debug('0. text: ' . self::encode($this->text));
  if (!is_null($this->proto))
   $this->text .= $this->proto . '://';
  //self::debug('1. text: ' . self::encode($this->text));
  if (!is_null($this->host))
   $this->text .= $this->host;
  //self::debug('2. text: ' . self::encode($this->text));
  if (!is_null($this->path))
   $this->text .= $this->path;
  //self::debug('3. text: ' . self::encode($this->text));
  if (is_array($this->params))
   $this->makeParams();
  if (!is_null($this->params))
   $this->text .= '?' . $this->params;
  //self::debug('4. text: ' . self::encode($this->text));
  if (!is_null($this->anchor))
   $this->text .= '#' . $this->anchor;
  //self::debug('text: ' . self::encode($this->text));
 }

 private function parseParams()
 {
  $params = explode('&', $this->params);
  $this->params = [];
  foreach ($params as $param)
  {
   if (!strlen($param))
    continue;
   $pos = strpos($param, '=');
   if ($pos === FALSE)
    $this->params[$param] = '';
   else
    $this->params[substr($param, 0, $pos)] = substr($param, $pos + 1);
  }
 }

 private function makeParams()
 {
  //self::debug('makeParams()');
  //self::debug('params: ' . self::encode($this->params));
  $params = [];
  foreach ($this->params as $key => $value)
   $params[] = "$key=$value";
  $this->params = count($params) ? implode('&', $params) : null;
  //self::debug('params: ' . self::encode($this->params));
 }

 function getParam($name)
 {
  if (is_string($this->params))
   $this->parseParams();
  return (is_array($this->params) && array_key_exists($name, $this->params)) ? $this->params[$name] : null;
 }

 function setParam($name, $value = null)
 {
  $value = "$value";
  $params = $this->params; // backup
  if (is_string($this->params))
   $this->parseParams();
  if (is_array($this->params) && array_key_exists($name, $this->params) && ($this->params[$name] == $value))
  {
   $this->params = $params;
   return;
  }
  if (is_null($this->params))
   $this->params = [];
  $this->params[$name] = $value;
  $this->text = null;
 }

 function unsetParam($name)
 {
  //self::debug('unsetParam(' . self::encode($name) . ')');
  //self::debug('params: ' . self::encode($this->params));
  $params = $this->params; // backup
  if (is_string($this->params))
   $this->parseParams();
  if (!is_array($this->params) || !array_key_exists($name, $this->params))
  {
   $this->params = $params;
   return;
  }
  unset($this->params[$name]);
  //self::debug('params: ' . self::encode($this->params));
  if (!count($this->params))
   $this->params = null;
  $this->text = null;
 }

 function __get($name)
 {
  //self::debug('__get(' . self::encode($name) . ')');
  if (property_exists($this, $name))
  {
   if ($name == 'text')
   {
    if (is_null($this->text))
     $this->make();
   }
   else if ($name == 'params')
   {
    if (is_string($this->params))
     $this->parseParams();
   }
   return $this->$name;
  }
  if ($name == 'paramStr')
  {
   if (is_array($this->params))
    $this->makeParams();
   return $this->params;
  }
 }

 function __set($name, $value)
 {
  //self::debug('__set(' . self::encode($name) . ', ' . self::encode($value) . ')');
  if (property_exists($this, $name))
  {
   if ($name == 'text')
    $this->parse($value);
   else
    $this->text = null;
   $this->$name = $value;
  }
  else if ($name == 'paramStr')
  {
   if (!is_null($value) && !is_string($value))
    $value = "$value";
   $this->params = $value;
   $this->text = null;
  }
  //self::debug(self::encode($this->state()));
 }

 function __toString()
 {
  return $this->__get('text');
 }

 private function state()
 {
  return ['text' => $this->text, 'proto' => $this->proto, 'host' => $this->host, 'path' => $this->path, 'params' => $this->params, 'anchor' => $this->anchor];
 }

 private static function encode($value)
 {
  return implode('/', explode('\/', json_encode($value)));
 }

 private static function debug($text)
 {
  echo "[uri] $text<br>\n";
 }

 static function test()
 {
  self::debug('test()');
  $count = 0;
  $assert = function($current, $expected) use (&$count)
  {
   if ($current !== $expected)
    self::debug('ASSERT(' . (++$count) . '): ' . self::encode($current) . ' !== ' . self::encode($expected));
  };

  $uri = new uri();
  $assert($uri->text, null);
  $assert($uri->path, $_SERVER['REQUEST_URI']);
  //$uri->path = '/index.html';
  $uri->__set('path', '/index.html');
  $assert($uri->path, '/index.html');
  $assert($uri->text, null);
  $assert($uri->__get('text'), '/index.html');

  $assert(count($uri->params), 0);
  $uri->setParam('test', 123);
  $assert($uri->params['test'], '123');
  $uri->unsetParam('test');
  $assert(count($uri->params), 0);

  // TODO: make unit test

  self::debug($count ? 'FAILED' : 'OK');
 }
}

?>
