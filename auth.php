<?php

require_once('db.php');
require_once('http.php');

class auth
{

private const TABLE = 'auth';
private const INFO_URI = 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=';

static function domain()
{
 $parts = explode('.', http::host());
 if ($parts[1] == 'com')
  return http::host();
 return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
}

static function uri($params = null)
{
 return 'https://' . self::domain() . '/' . ($params ? ('?' . $params) : '');
}

static function root($params = null)
{
 http::go(self::uri($params));
}

static function check()
{
 $guid = util::item($_COOKIE, 'GUID');
 $fields = $guid ? db::createDefault(true)->queryFields2(self::TABLE, null, "guid='$guid'") : null;
 if ($fields)
  return $fields;

 if ($guid)
  http::deleteCookie('GUID', '/', self::domain());

 return null;
}

// is called from any subdomain
static function login($optional = null)
{
 $check = self::check();
 if ($check || $optional)
  return $check;

 http::go(self::uri('signin'));
}

// is called from any subdomain
static function logout()
{
 http::go(self::uri('signout'));
}

// is called from /?a=signin
static function signin($ajax = null, $limited = null)
{
//die('Here');
 $token = http::par('token');
 if (!$token)
  return self::xerror($ajax, 'No token');

 $json = file_get_contents(self::INFO_URI . $token);
 if (!$json)
  return self::xerror($ajax, 'No token info', $token);

 $info = json_decode($json);
 if (!$info)
  return self::xerror($ajax, 'Invalid token info', $json);

// if (!property_exists($info, 'aud'))
//  self::xerror($ajax, 'No aud in token info', $json);
// if ($info->aud != self::CLIENT_ID)
//  self::xerror($ajax, 'Invalid aud in token info', $json);

 if (!property_exists($info, 'sub'))
  return self::xerror($ajax, 'No sub in token info', $json);

 if (!$info->sub)
  return self::xerror($ajax, 'Empty sub in token info', $json);

 $sub = $info->sub;
 $guid = sha1($sub);
 if (!$guid)
  return self::xerror($ajax, 'Error: no guid');
 $name = property_exists($info, 'name') ? $info->name : '';
 $email = property_exists($info, 'email') ? $info->email : '';
 $avatar = property_exists($info, 'picture') ? $info->picture : '';

 $db = db::createDefault(true);
 $values = ['type' => db::str('g'), 'sub' => db::str($sub)];
 $fields = $db->queryFields2(self::TABLE, null, $values);
 //die('Here ' . json::encode($fields) . ' / ' . db::lastQuery());
 if (!$fields)
 {
  if ($limited)
   return ['guid' => $guid, 'name' => $name, 'email' => $email];
  $values['guid'] = db::str($guid);
  if ($name)
   $values['name'] = db::str($name);
  if ($email)
   $values['email'] = db::str($email);
  if ($avatar)
   $values['avatar'] = db::str($avatar);
  $values['created'] = 'now()';
  $values['updated'] = 'now()';
  if (!$db->insertValues(self::TABLE, $values))
   return self::xerror($ajax, 'Error adding new record to the database: ' . db::lastQuery());
  //die('Here ' . db::lastQuery());
 }
 else
 {
  $values2 = [];
  if ($guid != $fields['guid'])
   $values2['guid'] = db::str($guid);
  if ($name != $fields['name'])
   $values2['name'] = db::str($name);
  if ($email != $fields['email'])
   $values2['email'] = db::str($email);
  if ($avatar != $fields['avatar'])
   $values2['avatar'] = db::str($avatar);
  if (count($values2))
  {
   $values2['updated'] = 'now()';
   if (!$db->updateRows(self::TABLE, $values2, $values))
    return self::xerror($ajax, 'Error updating record in the database: ' . db::lastQuery());
  }
 }

 if ($ajax)
 {
  echo '{"ok":"true","guid":"' . $guid . '"}';
  return;
 }

 setcookie("GUID", $guid, time() + 86400 * 3660, '/', self::domain());
 $_COOKIE['GUID'] = $guid;
}

// is called from /?signout and /?a=signout to clear cookie
static function signout()
{
 http::deleteCookie('GUID', '/', self::domain());
}

// https://lh3.googleusercontent.com/a-/AOh14Gi3CiWN943GC2xGOiquD5lMoI9G_dC9nN43gl6x=s96-c

static function resizeAvatar($avatar, $size)
{
 if (!$avatar)
  return $avatar;
 $parts = explode('=', $avatar);
 if (!$parts || count($parts) < 2)
  return $avatar;
 $parts[count($parts) - 1] = 's' . $size . '-c';
 return implode('=', $parts);
}

private static function xerror($ajax, $error, $extra = null)
{
 if ($ajax)
  echo '{"error":"' . addslashes($error) . '"' . ($extra ? ',"extra":"' . addslashes($extra) . '"' : '') . '}';
 else
  self::error($error, $extra);
}

private static function error($error, $extra = null)
{
 //http::loc(self::uri('error=' . $error));
 http::error($error, $extra);
 die("$error\n$extra");
}

}
?>
