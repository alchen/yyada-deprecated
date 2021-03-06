<?php

require_once('core/twitteroauth.php');
require_once('core/theme.php');
require_once('core/settings.php');
require_once('core/monitor.php');
require_once('util/url.php');
require_once('util/tweet.php');

// environment
global $content, $monitor;

// settings
global $settings, $theme, $access_token, $conn;

function dispatch_url() {
  global $access_token;

  // pharse URI
  $base = join_path(preg_replace('/^\w+:\/+s*/' , '' , BASE_URL), '/');
  $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

  // remove the query string
  $url = preg_replace('/\?.*$/','',$url);

  // get the relative URI based on the BASE URL
  $r_uri = str_ireplace($base , '' , $url);

  $uri = explode('/' , $r_uri, 3);

  if (!isset($_SESSION['status']) || $_SESSION['status'] != 'verified') {
    $controller = 'login.php';
  } else {
    $controller = 'tweet.php';

    if (isset($uri[0]) && !empty($uri[0]))
      $controller = $uri[0] . ".php";
  }
  include 'controller/' . $controller;

  $action = 'default';
  if (isset($uri[1]) && !empty($uri[1]))
    $action = $uri[1];

  $args = '';
  if (isset($uri[2]) && !empty($uri[2]))
    $args = $uri[2];

  $func = array_get($controller_router, $action, $controller_router['default']);
  $func($args);
}

function init_environment() {
  global $theme, $settings, $access_token, $conn, $content, $monitor;

  session_start();

  $settings = new Settings(cookie_get('config'));
  $theme = new Theme($settings->theme);
  $access_token = load_access_token();
  $content = array();
  $monitor = new Monitor();
  if (isset($access_token)) {
    $conn = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $conn->timeout = 10;
    $conn->connecttimeout = 10;
  }
  else
    $conn = null;
}

?>
