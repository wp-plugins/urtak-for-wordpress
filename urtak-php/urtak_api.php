<?php
/**
 * Urtak v1 API Wrapper for PHP
 * --------------------------------
 * @version:        0.9.7
 * @author:         Kunal Shah <kunal@urtak.com>
 * @creation date:  September 08, 2011
 * @link:           https://urtak.com/dev
 * @copyright:      Copyright (c) 2011. For this version.
 */

class Urtak {

  protected $publication_key    = ''; // Publication Key
  protected $api_key            = ''; // API Key
  
  protected $urtak_home   = 'https://urtak.com';      // Home Url
  protected $api_home     = 'https://urtak.com/api';  // API Url
  protected $api_format   = 'JSON';                   // XML or JSON
  protected $client_name  = 'Urtak API Wrapper for PHP v0.9.7';

  public function __construct($config = array())
  {
    if(!empty($config))   {
      $this->initialize($config);
    }
  }

  // --------------------------------------------------------------------

  /**
   * Initialize preferences
   *
   * @access  public
   * @param array
   * @return  void
   */
  function initialize($config = array())
  {
    extract($config);
    if(!empty($publication_key))
    {
      $this->publication_key = $publication_key;
    }
    if(!empty($api_key))
    {
      $this->api_key = $api_key;
    }
    if(!empty($client_name))
    {
      $this->client_name = $client_name;
    }
    if(!empty($api_home))
    {
      $this->api_home = $api_home;
    }
    if(!empty($urtak_home))
    {
      $this->urtak_home = $urtak_home;
    }
    if(!empty($api_format))
    {
      $this->api_format = $api_format;
    }
  }
  
  // --------------------------------------------------------------------
  //                                ACCOUNTS
  // --------------------------------------------------------------------

  /** Lookup An Account
   * 
   * @access  @public
   * @params  Lookup _your_ acccount
   * @return  UrtakResponse
   */
  public function get_account($id) {
    return $this->curl_request('/accounts/'.$id, 'GET', array());
  }

  /** Create an Account
   * 
   * @access  @public
   * @params  Create an acccount
   * @return  UrtakResponse
   */
  public function create_account($options) {
    return $this->curl_request('/accounts', 'POST', array("account" => $options));
  }

  // --------------------------------------------------------------------
  //                                PUBLICATIONS
  // --------------------------------------------------------------------

  /** Lookup a Publication
   * 
   * @access  @public
   * @params  Lookup a publication
   * @return  UrtakResponse
   */
  public function get_publication($key) {
    return $this->curl_request('/publications/'.$key, 'GET', array());
  }

  /** Create a Publication
   * 
   * @access  @public
   * @params  Create a publication
   * @return  UrtakResponse
   */
  public function create_publication($property, $value, $options) {
    $data = array($property => $value, 'publication' => $options);
    return $this->curl_request('/publications', 'POST', $data);
  }

  /** Update a Publication
   * 
   * @access  @public
   * @params  Updates a publication
   * @return  UrtakResponse
   */
  public function update_publication($key, $options) {
    return $this->curl_request('/publications/'.$key, 'PUT', array('publication' => $options));
  }

  // --------------------------------------------------------------------
  //                                URTAKS
  // --------------------------------------------------------------------

  /** Get Urtaks
   * 
   * @access  @public
   * @params  property to lookup by, value of lookup property
   * @return  UrtakResponse
   */
  public function get_urtaks($options) {
    return $this->curl_request('/urtaks', 'GET', $options);
  }

  /** Get an Urtak
   * 
   * @access  @public
   * @params  property to lookup by, value of lookup property
   * @return  UrtakResponse
   */
  public function get_urtak($property, $value, $options) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value;
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value;
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value;
    } 

    return $this->curl_request($path, 'GET', $options);
  }

  /** Create an Urtak, with optional questions
   * 
   * @access  @public
   * @params  urtak attribute array, array of questions
   * @return  UrtakResponse
   */
  public function create_urtak($urtak_attributes, $questions) {
    $data = array('urtak' => array_merge($urtak_attributes, array('questions' => $questions)));

    return $this->curl_request('/urtaks', 'POST', $data);
  }

  /** Update an Urtak
   * 
   * @access  @public
   * @params  property to lookup by, urtak attribute array
   * @return  UrtakResponse
   */
  public function update_urtak($property, $urtak_attributes) {
    $value = $urtak_attributes[$property];

    if($property == 'id') { 
      $path = '/urtaks/'.$value;
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value;
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value;
    }
    
    // this is not a property we're trying to update just do a lookup by, so remove it
    unset($urtak_attributes[$property]);

    $data = array('urtak' => $urtak_attributes);

    return $this->curl_request($path, 'PUT', $data);
  }

  // --------------------------------------------------------------------
  //                                QUESTIONS
  // --------------------------------------------------------------------

  /** Retrieve Questions on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function get_urtak_questions($property, $value, $options) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions';
    } 

    return $this->curl_request($path, 'GET', array());
  }

  /** Retrieve a Question on an Urtak by Urtak ID, post id, or post permalink and Question ID
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function get_urtak_question($property, $value, $question_id) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions/'.$question_id;
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions/'.$question_id;
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions/'.$question_id;
    }

    return $this->curl_request($path, 'GET', array());
  }

  /** Create 1 or More Questions on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink'), the value ('1', '3-my-article', 'http://foo.com/my-great-content), questions array
   * @return  UrtakResponse
   */
  public function create_urtak_questions($property, $value, $questions) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions';
    } 

    return $this->curl_request($path, 'POST', array('questions' => $questions));
  }


  /** Approve a Question on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function approve_urtak_question($property, $value, $question_id) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions/'.$question_id.'/approve';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions/'.$question_id.'/approve';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions/'.$question_id.'/approve';
    } 

    return $this->curl_request($path, 'POST', array());
  }

  /** Reject a Question on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function reject_urtak_question($property, $value, $question_id) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions/'.$question_id.'/reject';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions/'.$question_id.'/reject';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions/'.$question_id.'/reject';
    } 

    return $this->curl_request($path, 'POST', array());
  }

  /** Mark as spam a Question on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function spam_urtak_question($property, $value, $question_id) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions/'.$question_id.'/spam';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions/'.$question_id.'/spam';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions/'.$question_id.'/spam';
    } 

    return $this->curl_request($path, 'POST', array());
  }

  /** Mark as ham a Question on an Urtak by Urtak ID, post id, or post permalink
   * 
   * @access  @public
   * @params  property ('id', 'post_id', 'permalink') and the value ('1', '3-my-article', 'http://foo.com/my-great-content)
   * @return  UrtakResponse
   */
  public function ham_urtak_question($property, $value, $question_id) {
    if($property == 'id') { 
      $path = '/urtaks/'.$value.'/questions/'.$question_id.'/ham';
    } elseif($property == 'post_id') {
      $path = '/urtaks/post/'.$value.'/questions/'.$question_id.'/ham';
    } elseif($property == 'permalink') {
      $path = '/urtaks/permalink/'.$value.'/questions/'.$question_id.'/ham';
    } 

    return $this->curl_request($path, 'POST', array());
  }

  // --------------------------------------------------------------------
  //                                PRIVATE
  // --------------------------------------------------------------------

  /**
   * Sign API Request
   *
   * @access  private
   * @params  url | data
   * @return  
   */
  private function create_signature() {
    $timenow   = gmdate("U");
    $signature = sha1($timenow.' '.$this->api_key);
    return array('timestamp' => $timenow, 'signature' => $signature, 'publication_key' => $this->publication_key);
  }
  
  /** Content Negotiation
   * 
   * @access  @public
   * @params  null
   * @return  string
   */
  private function media_types() 
  {
    // Return output as XML/JSON w/ headers
    if(strtoupper($this->api_format) == 'XML')
    {
      return "application/vnd.urtak.urtak+xml; version=1.0";
    } elseif(strtoupper($this->api_format) == 'JSON') {   
      return "application/vnd.urtak.urtak+json; version=1.0";
    }
  }

  /**
   * cURL Request
   *
   * @access  private
   * @params  url | data
   * @return  array
   */
  private function curl_request($path, $method, $data = array())
  {
    // TODO - fallover to streams if cURL is not available.
    if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
      die('PHP-CURL is not installed, please run apt-get install php5-curl');
    }

    $headers     = array();
    $curl_handle = curl_init();

    curl_setopt($curl_handle, CURLOPT_USERAGENT, $this->client_name);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handle, CURLOPT_ENCODING, "");
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl_handle, CURLOPT_HEADER, true);

    $headers[] = $method." ".$path." HTTP/1.1";
    $headers[] = "Host: ".preg_replace('/https?:\/\//', '', $this->urtak_home);
    $headers[] = "Accept: ".$this->media_types();

    if($method == 'GET') {
      $url = $this->api_home.$path.'?'.http_build_query(array_merge($data, $this->create_signature()));
      curl_setopt($curl_handle, CURLOPT_URL, $url);

    } elseif($method == 'POST') {

      $url = $this->api_home.$path;
      $json_data = json_encode(array_merge($data, $this->create_signature()));

      curl_setopt($curl_handle, CURLOPT_URL, "$url");
      curl_setopt($curl_handle, CURLOPT_POST, 1);
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "$json_data");

      $headers[] = "Content-type: application/json";
      $headers[] = "Content-length: ".strlen($json_data);

    } elseif($method == 'PUT') {
    
      $url = $this->api_home.$path;
      $json_data = json_encode(array_merge($data, $this->create_signature()));

      curl_setopt($curl_handle, CURLOPT_URL, "$url");
      curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "$json_data");

      $headers[] = "Content-type: application/json";
      $headers[] = "Content-length: ".strlen($json_data);

    } else {
      // unimplemented! 501!
      die("Unimplemented");
    }

    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl_handle);
    $code     = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);
    
    if($this->api_format == "JSON") {
      return new UrtakResponse($response, $code, 'JSON');
    } elseif($this->api_format == "XML") {
      return new UrtakResponse($response, $code, 'XML');
    }
  }
}

class UrtakResponse {
  public $raw     = '';       // Raw Response
  public $raw_body= '';       // Raw Response
  public $body    = '{}';     // Parsed Response Body
  public $headers = array();  // Headers available as an Array
  public $code    = 0;        // HTTP Status Code
  public $format  = 'JSON';   // Set by Urtak Class

  public function __construct($raw, $code, $format)
  {
    // this parsing code is Copyright (c) 2008 Sean Huber - shuber@huberry.com
    // available under the MIT License at https://github.com/shuber/curl

    # Headers regex
    $this->raw    = $raw;
    $this->code   = $code;
    $this->format = $format;

    $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

    # Extract headers from response
    preg_match_all($pattern, $raw, $matches);
    $headers_string = array_pop($matches[0]);
    $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

    # Remove headers from the response body
    $this->raw_body = str_replace($headers_string, '', $raw);

    # Extract the version and status from the first header
    $version_and_status = array_shift($headers);
    preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
    $this->headers['Http-Version'] = $matches[1];
    $this->headers['Status-Code'] = $matches[2];
    $this->headers['Status'] = $matches[2].' '.$matches[3];

    # Convert headers into an associative array
    foreach ($headers as $header) {
      preg_match('#(.*?)\:\s(.*)#', $header, $matches);
      $this->headers[$matches[1]] = $matches[2];
    }

    if($this->format == 'JSON') {
      $this->body = json_decode($this->raw_body, true);
    } else {
      $this->body = new SimpleXMLElement($this->raw_body);
    }
  }
  
  /**
   * error message, returns error message if one exists
   *
   * @access public
   * @params
   * @return string
   */
  public function error()
  {
    return $this->body['error']['message'];
  }
  
  /**
   * success, returns true if Urtak API Response was in the 200 or 300 range
   *
   * @access  public
   * @params  
   * @return  boolean
   */
  public function success()
  {
    return (($this->code >= 200) && ($this->code < 400));
  }
  
  /**
   * failure, returns true if Urtak API Response was in the 400 or 500 range
   *
   * @access  public
   * @params  
   * @return  boolean
   */
  public function failure()
  {
    return ($this->code >= 400);
  }
  
  /**
   * server error, returns true if Urtak API Response was in the 500 range
   *
   * @access  public
   * @params  
   * @return  boolean
   */
  public function server_error()
  {
    return ($this->code >= 500);
  }
  
  /**
   * found, returns true if the requested record was found
   *
   * @access  public
   * @params  
   * @return  boolean
   */
  public function found()
  {
    // todo fix this naive implementation
    return (($this->code == 200) || ($this->code == 304));
  }

  /**
   * not found, returns true if requested record was not found
   *
   * @access  public
   * @params  
   * @return  boolean
   */
  public function not_found()
  {
    return ($this->code == 404);
  }
}

?>