<?php

class http
{

static function ori()
{
 return self::pro() . self::host();
}

static function uri()
{
 return self::pro() . self::host() . self::path();
}

static function pro()
{
 return self::sv('HTTPS') ? 'https://' : 'http://';
}

static function host()
{
 return self::sv('HTTP_HOST');
}

static function path()
{
 $uri = self::req();
 $pos = strpos($uri, '?');
 if ($pos !== FALSE)
  $uri = substr($uri, 0, $pos);
 $pos = strpos($uri, '#');
 if ($pos !== FALSE)
  $uri = substr($uri, 0, $pos);
 return $uri;
}

static function req()
{
 return self::sv('REQUEST_URI');
}

static function ref()
{
 return self::sv('HTTP_REFERER');
}

static function agent()
{
 return self::sv('HTTP_USER_AGENT');
}

static function loc($uri = null)
{
 header('Location: ' . ($uri ? $uri : self::req()));
}

static function go($uri = null)
{
 self::loc($uri);
 exit();
}

static function ssl()
{
 $https = util::item($_SERVER, 'HTTPS');
 $proto = util::item($_SERVER, 'REQUEST_SCHEME');
 if ($https != 'on' && $proto != 'https') // Redirect to HTTPS
  self::go('https://' . self::sv('HTTP_HOST') . self::req());
}

static function get($name)
{
 return array_key_exists($name, $_GET) ? $_GET[$name] : null;
}

static function par($name, $required = null)
{
 if (array_key_exists($name, $_POST))
  return $_POST[$name];
 if ($required)
  throw new Exception('Parameter ' . $name . ' is required');
}

static function deleteCookie($name, $path = null, $domain = null)
{
 if (array_key_exists($name, $_COOKIE))
 {
  setcookie($name, null, time() - 3600, $path, $domain);
  unset($_COOKIE[$name]);
 }
}

static function wget($uri)
{
 if (!$uri)
  self::error404('URL not specified');
 system("wget \"$uri\" -q -O -");
 exit();
}

static function error404($text = null, $info = null)
{
 header( 'HTTP/1.0 404 Not Found');
 $GLOBALS['http_response_code'] = 404;
 self::error($text, $info);
}

static function error($text, $info = null)
{
 trigger_error($text . ($info ? (': ' . $info) : null));
 exit('Runtime error' . ($text ? (': ' . $text) : ''));
}

static function sv($name)
{
 return array_key_exists($name, $_SERVER) ? $_SERVER[$name] : null;
}

}

?>
