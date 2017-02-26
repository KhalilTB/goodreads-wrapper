<?php
namespace jarboleda;
use CurlWrapper;

class Goodreads{
  private $consumer_key;
  private $consumer_secret;
  private $consumer;
  private $oauth_token;
  private $oauth_token_secret;
  private $token;
  private $base_url = '';

  const REQUEST_TOKEN_URL = 'http://www.goodreads.com/oauth/request_token';
  const AUTHORIZE_URL = 'http://www.goodreads.com/oauth/authorize';
  const ACCESS_TOKEN_URL = 'http://www.goodreads.com/oauth/access_token';

  function __construct(){

  }

  public static function create(){
    return new self();
  }

  private function set_consumer_key($consumer_key){
    $this->consumer_key = $consumer_key;
    return $this;
  }

  private function set_consumer_secret($consumer_secret){
    $this->consumer_secret = $consumer_secret;
    return $this;
  }

  private function set_oauth_token($oauth_token){
    $this->oauth_token = $oauth_token;
    return $this;
  }

  private function set_oauth_token_secret($oauth_token_secret){
    $this->oauth_token_secret = $oauth_token_secret;
    return $this;
  }

  public function set_consumer($consumer_key, $consumer_secret){
    $this->set_consumer_key($consumer_key);
    $this->set_consumer_secret($consumer_secret);
    $this->consumer = new OAuth\OAuthConsumer($consumer_key, $consumer_secret);
    return $this;
  }

  public function set_token($oauth_token, $oauth_token_secret){
    $this->set_oauth_token($oauth_token);
    $this->set_oauth_token_secret($oauth_token_secret);
    $this->token = new OAuth\OAuthConsumer($oauth_token, $oauth_token_secret);
    return $this;
  }

  function request_token($endpoint){
    $parsed = parse_url($endpoint);
    $params = array();
    if(isset($parsed['query'])){
      parse_str($parsed['query'], $params);
    }

    $req_req = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, NULL, "GET", $endpoint, $params);
    $req_req->sign_request(new OAuth\OAuthSignatureMethodHMACSHA1(), $this->consumer, NULL);

    $r = file_get_contents($req_req->to_url());
    $data = array();
    parse_str($r, $data);
    return $data;
  }

  function authorize($endpoint, $base_url, $redirect = false){
    $suffix = strpos($base_url, '?') !== false ? '&' : '?';
    $callback_url = $base_url;
    $auth_url = $endpoint . $suffix. "oauth_token=$this->oauth_token&oauth_callback=".urlencode($callback_url);
    if($redirect){
      Header("Location: $auth_url");
    }
    else{
      return $auth_url;
    }
  }

  function access_token($endpoint, $params = array()){
    $acc_req = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, $this->token, "GET", $endpoint, $params);
    $acc_req->sign_request(new OAuth\OAuthSignatureMethodHMACSHA1(), $this->consumer, $this->token);

    $r = file_get_contents($acc_req->to_url());
    $data = array();
    parse_str($r, $data);
    return $data;
  }

  private function generic_request($endpoint, $method = 'GET', $data = null){
    try {
        $curl = new CurlWrapper();
    } catch (CurlWrapperException $e) {
        echo $e->getMessage();
        return;
    }
    // we already have an access token
    $request = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $endpoint, $data);
    $request->sign_request(new OAuth\OAuthSignatureMethodHMACSHA1(), $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        $response = $curl->get($request->to_url(), null);
        break;
      case 'POST':
        $response = $curl->post($endpoint, $request->to_postdata());
        break;
      default:
        die('Not supported');
        break;
    }
    return (new \SimpleXMLElement($response, LIBXML_NOCDATA));
  }

  function get_auth_user($endpoint = 'http://www.goodreads.com/api/auth_user', $data = null){
    return $this->generic_request($endpoint, 'GET', $data);
  }

  function get_user_name($user_id, $endpoint = 'https://www.goodreads.com/user/show/', $data = null){
    $endpoint .= $user_id . '.xml';
    return $this->generic_request($endpoint, 'GET', $data)->user->user_name;
  }

  function get_reviews($data = array(), $endpoint = 'https://www.goodreads.com/review/list/'){
    $endpoint .= $data['id'] . '.xml';
    return $this->generic_request($endpoint, 'GET', $data);
  }

  function get_review($review_id, $endpoint = 'https://www.goodreads.com/review/show.xml'){
    $data = array('id' => $review_id);
    return $this->generic_request($endpoint, 'GET', $data)->review;
  }

  function edit_review($data = array(), $endpoint){
    return $this->generic_request($endpoint, 'POST', $data);
  }

  function test_api_key($key){
    return $this->generic_request('https://www.goodreads.com/book/show/50.xml?key='. $key , 'GET');
  }

  public static function debug($var = null, $return = false, $line_info = false) {
    $trace = debug_backtrace();
    $rootPath = dirname(dirname(__FILE__));
    $file = str_replace($rootPath, '', $trace[0]['file']);
    $line = $trace[0]['line'];
    if(is_null($var)){
        $var = $trace[0]['args'][0];
    }
    $lineInfo = $line_info == true ? sprintf('<div><strong>%s</strong> (line <strong>%s</strong>)</div>', $file, $line) : '';
    $debugInfo = sprintf('<pre>%s</pre>', print_r($var, true));
    if($return){
        return $lineInfo.$debugInfo;
    }
    else{
        print_r($lineInfo.$debugInfo);
    }
  }
}
