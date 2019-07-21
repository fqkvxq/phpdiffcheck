<?php
function screenshotlayer($url, $args) {

    // set access key
    $access_key = "c9810d7fb7b6973980df6c6436b9793e";
    
    // set secret keyword (defined in account dashboard)
    $secret_keyword = "takuyakawai123$%^7789";
  
    // encode target URL
    $params['url'] = urlencode($url);
  
    $params += $args;
  
    // create the query string based on the options
    foreach($params as $key => $value) { $parts[] = "$key=$value"; }
  
    // compile query string
    $query = implode("&", $parts);
  
    // generate secret key from target URL and secret keyword
    $secret_key = md5($url . $secret_keyword);
  
    return "https://api.screenshotlayer.com/api/capture?access_key=$access_key&secret_key=$secret_key&$query";
  
  }
  
  // set optional parameters (leave blank if unused)
  $params['fullpage']  = '';    
  $params['width'] = '';      
  $params['viewport']  = '';  
  $params['format'] = '';      
  $params['css_url'] = '';      
  $params['delay'] = '';      
  $params['ttl'] = '';  
  $params['force']     = '';     
  $params['placeholder'] = '';      
  $params['user_agent'] = '';      
  $params['accept_lang'] = '';      
  $params['export'] = './';      
  
  // capture
  $call = screenshotlayer("google.com", $params);   
?>