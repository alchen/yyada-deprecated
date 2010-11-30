<?php

require_once('config.php');
require_once('core/twitteroauth.php');
require_once('core/cookie.php');
require_once('core/settings.php');
require_once('util/url.php');

function oauth() {
  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, NULL, NULL, OAUTH_PROXY);
  $request_token = $connection->getRequestToken(join_path(BASE_URL, 'login/callback'));

  $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
  $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

  switch ($connection->http_code) {
  case 200:
    $url = $connection->getAuthorizeURL($token);
    header('Location: ' . $url);
    break;
  default:
    Settings::purge();
    $_SESSION['status'] = 'login_fail';
    header('Location: /');
    break;
  }
}

function callback() {
  if (empty($_SESSION['oauth_token']) ||
      empty($_REQUEST['oauth_token']) ||
      $_SESSION['oauth_token'] != $_REQUEST['oauth_token']) {
error_log('fail');
    Settings::purge();
    $_SESSION['status'] = 'login_fail';
    header('Location: /');
    return;
  }

  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], OAUTH_PROXY);
  $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
  $_SESSION['access_token'] = $access_token;

  unset($_SESSION['oauth_token']);
  unset($_SESSION['oauth_token_secret']);

  if (200 != $connection->http_code) {
    Settings::purge();
    $_SESSION['status'] = 'login_fail';
    header('Location: /');
    return;
  }
  $_SESSION['status'] = 'verified';
  save_access_token($access_token);
  header('Location: /');
}

function clear() {
  Settings::purge();
  $_SESSION['status'] = 'logoff';
  header('Location: /');
}

function default_behavior() {
  global $theme, $content;

  switch ($_SESSION['status']) {
    case 'login_fail':
      Settings::purge();
      $_SESSION['status'] = 'logoff';
      $content['info'] = '<div class="warning">Sign in failed, please try again.</div>';
      $content['info'] .= login_html($echo=false);
      break;
    case 'invite_fail':
      Settings::purge();
      $_SESSION['status'] = 'logoff';
      $content['info'] = '<div class="warning">You are not invited by administrator.</div>';
      $content['info'] .= login_html($echo=false);
      break;
    case 'logoff':
      Settings::purge();
      $content['info'] = login_html($echo=false);
      break;
  }

  $theme->include_html('info');
}

?>
