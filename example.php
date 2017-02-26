<?php
  session_start();
  require('vendor/autoload.php');
  use jarboleda\Goodreads as Goodreads;

  define('REQUEST_TOKEN_URL', 'http://www.goodreads.com/oauth/request_token');
  define('ACCESS_TOKEN_URL', 'http://www.goodreads.com/oauth/access_token');
  define('AUTHORIZE_URL', 'http://www.goodreads.com/oauth/authorize');
  define('BASE_URL', 'your_url');

  $consumer_key = 'your_consumer_key';
  $consumer_secret = 'your_consumer_secret';

  if(!isset($_GET['authorize'])){
    /* REQUEST TOKEN */
    $api = Goodreads::create()->set_consumer($consumer_key, $consumer_secret);
    $request_token = $api->request_token(REQUEST_TOKEN_URL);
    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    /* AUTHORIZE */
    $api->set_token($request_token['oauth_token'], $request_token['oauth_token_secret']);
    $api->authorize(AUTHORIZE_URL, BASE_URL, true);
  }

  elseif($_GET['authorize'] === '1'){
    // check token
    if($_GET['oauth_token'] != $_SESSION['oauth_token']){
      die('Sorry, something went wrong');
    }
    $api = Goodreads::create()->set_consumer($consumer_key, $consumer_secret);
    $api->set_token($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    unset($_SESSION['oauth_token'],  $_SESSION['oauth_token_secret']);
    /* ACCESS TOKEN */
    $access_token = $api->access_token(ACCESS_TOKEN_URL);
    $api->set_token($access_token['oauth_token'], $access_token['oauth_token_secret']);
    // at this point you can save store tokens (using sessions, database ...) to use them later

    $auth_user_id = $api->get_auth_user();
    $auth_user_id = (string) $auth_user_id->user['id'];
    $username = $api->get_user_name($auth_user_id);

    $books = $api->get_reviews(array('id'       => $auth_user_id,
                                   'v'        => 2,
                                   'shelf'    => 'read',
                                   'per_page' => 6,
                                   'sort'     => 'date_read',
                                   'order'    => 'd'));

    header("Content-type: text/xml");
    echo $books->asXML();
    // Goodreads::debug($api->get_review('1101942234'));
    // Goodreads::debug($books);
  }
