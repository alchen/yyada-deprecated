<?php

function cookie_get($key, $default = NULL) {
  if (array_key_exists($key, $_COOKIE)) {
    return $_COOKIE[$key];
  }
  return $default;
}

function cookie_set($key, $value) {
  $duration = time() + (3600 * 24 * 30); // one month
  setcookie($key, $value, $duration, '/');
}

function cookie_set_secret($key, $value) {
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
  mcrypt_generic_init($td, SECRET_KEY, $iv);
  $crypt_text = mcrypt_generic($td, $value);
  mcrypt_generic_deinit($td);
  cookie_set($key, base64_encode($iv . $crypt_text));
}

function cookie_get_secret($key, $default = NULL) {
  $value = cookie_get($key, $default);
  if (empty($value)) return null;

  $crypt_text = base64_decode($value);
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $ivsize = mcrypt_enc_get_iv_size($td);
  $iv = substr($crypt_text, 0, $ivsize);
  $crypt_text = substr($crypt_text, $ivsize);
  mcrypt_generic_init($td, SECRET_KEY, $iv);
  $ret = mdecrypt_generic($td, $crypt_text);
  mcrypt_generic_deinit($td);

  return $ret;
}

function cookie_clear() {
  $ignore_keys = array(
    'PHPSESSID',
  );

  $duration = time() - 3600;
  foreach (array_keys($_COOKIE) as $key) {
    if (in_array($key, $ignore_keys)) continue;
    setcookie($key, NULL, $duration, '/');
    setcookie($key, NULL, $duration);
  }
}

function logout() {
  session_unset();
  cookie_clear();
}

function check_invite($user) {
  $invite_file = __DIR__ . '/' . '../invite.txt';
  if (ENABLE_INVITE != 'true') return true;
  if (!is_file($invite_file)) return false;

  $allowed_users = file('invite.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!in_array(strtolower($user), $allowed_users)) {
    $_SESSION['status'] = 'invite_fail';
    return false;
  }
  return true;
}

?>