<?php

//require_once dirname(__FILE__) . '/OAuth.php'; 
require Kohana::find_file('vendor', 'OAuth');  


/**
 * Twitter for PHP - library for sending messages to Twitter and receiving status updates.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2008 David Grudl
 * @license    New BSD License
 * @link       http://phpfashion.com/
 * @see        http://dev.twitter.com/doc
 * @version    2.0
 */  
 
class Twitter
{
	/**#@+ Timeline {@link Twitter::load()} */
	const MENTIONS = 'mentions';
	const USER = 'user';
	const HOME = 'home';
	/**#@-*/

	/** @var int */
	public static $cacheExpire = 1800; // 30 min

	/** @var string */
	public static $cacheDir;

	/** @var OAuthSignatureMethod */
	private $signatureMethod;

	/** @var OAuthConsumer */
	private $consumer;

	/** @var OAuthConsumer */
	private $token;



	/**
	 * Creates object using consumer and access keys.
	 * @param  string  consumer key
	 * @param  string  app secret
	 * @param  string  optional access token
	 * @param  string  optinal access token secret
	 * @throws TwitterException when CURL extension is not loaded
	 */
	public function __construct($consumerKey = NULL, $consumerSecret = NULL, $accessToken = NULL, $accessTokenSecret = NULL)
	{
		if (!extension_loaded('curl')) {
			throw new TwitterException('PHP extension CURL is not loaded.');
		}
		
		//default keys
		if(is_null($consumerKey)){
		    // $consumerKey = Kohana::config('twitter.consumerKey');
		    $consumerKey = Kohana::$config->load('twitter.consumerKey');
		}
		
		if(is_null($consumerSecret)){
		    $consumerSecret = Kohana::$config->load('twitter.consumerSecret');
		}
		
		if(is_null($accessToken)){
		    $accessToken = Kohana::$config->load('twitter.accessToken');
		} 
		
		if(is_null($accessTokenSecret)){
		    $accessTokenSecret = Kohana::$config->load('twitter.accessTokenSecret');
		}
		
		

		$this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumerKey, $consumerSecret);
		$this->token = new OAuthConsumer($accessToken, $accessTokenSecret);
	}



	/**
	 * Tests if user credentials are valid.
	 * @return boolean
	 * @throws TwitterException
	 */
	public function authenticate()
	{
		try {
			$res = $this->request('account/verify_credentials', NULL, 'GET');
			return !empty($res->id);

		} catch (TwitterException $e) {
			if ($e->getCode() === 401) {
				return FALSE;
			}
			throw $e;
		}
	}


	/**
	 * Returns the most recent statuses.
	 * @param  const
	 * @param  int    number of statuses to retrieve
	 * @param  int    page of results to retrieve
	 * @return mixed
	 * @throws TwitterException
	 */
	public function load($timeline = self::USER, $screen_name = NULL, $count = 20)
	{
		static $timelines = array(self::USER => 'user_timeline', self::HOME => 'home_timeline', self::MENTIONS => 'mentions_timeline');

		if (!array_key_exists($timeline,$timelines)) {
			$timeline = self::USER;
		} 

		return $this->cachedRequest('statuses/' . $timelines[$timeline] . '.json', array(
			'count' => $count,
			'screen_name' => $screen_name,
			'include_rts' => 1,
		));
	}


	/**
	 * Returns tweets that match a specified query.
	 * @param  string|array   query
	 * @param  int      format (JSON | ATOM)
	 * @return mixed
	 * @throws TwitterException
	 */
	public function search($query)
	{
		return $this->cachedRequest(
			'search/tweets.json',
			is_array($query) ? $query : array('q' => $query),
			'GET'
		)->statuses;
	}



	/**
	 * Process HTTP request.
	 * @param  string  URL or twitter command
	 * @param  array   data
	 * @param  string  HTTP method
	 * @return mixed
	 * @throws TwitterException
	 */
	public function request($request, $data = NULL, $method = 'POST')
	{
		if (!strpos($request, '://')) {
			if (!strpos($request, '.')) {
				$request .= '.json';
			}
			$request = 'https://api.twitter.com/1.1/' . $request;
		}

		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $request, $data);
		$request->sign_request($this->signatureMethod, $this->consumer, $this->token);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // no echo, just return result
		curl_setopt($curl, CURLOPT_USERAGENT, 'Twitter for PHP');
		if ($method === 'POST') {
			curl_setopt($curl, CURLOPT_POST, TRUE);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request->to_postdata());
			curl_setopt($curl, CURLOPT_URL, $request->get_normalized_http_url());
		} else {
			curl_setopt($curl, CURLOPT_URL, $request->to_url());
		}

		$result = curl_exec($curl); 
		//echo Kohana::debug($result);
		
		if (curl_errno($curl)) {
			throw new TwitterException('Server error: ' . curl_error($curl));
		}

		$type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
		if (strpos($type, 'json')) {
			$payload = @json_decode($result); // intentionally @
		}

		if (empty($payload)) {
			throw new TwitterException('Invalid server response');
		}

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($code >= 400) {
			//throw new TwitterException(isset($payload->error) ? $payload->error : "Server error #$code", $code);
		}

		return $payload;
	}



	/**
	 * Cached HTTP request.
	 * @param  string  URL or twitter command
	 * @param  array
	 * @param  int
	 * @return mixed
	 */
	public function cachedRequest($request, $data = NULL, $cacheExpire = NULL)
	{
		if (!self::$cacheDir) {
			return $this->request($request, $data, 'GET');
		}
		if ($cacheExpire === NULL) {
			$cacheExpire = self::$cacheExpire;
		}

		$cacheFile = self::$cacheDir . '/twitter.' . md5($request . json_encode($data) . serialize(array($this->consumer, $this->token)));
		$cache = @file_get_contents($cacheFile); // intentionally @
		$cache = strncmp($cache, '<', 1) ? @json_decode($cache) : @simplexml_load_string($cache); // intentionally @
		if ($cache && @filemtime($cacheFile) + $cacheExpire > time()) { // intentionally @
			return $cache;
		}

		try {
			$payload = $this->request($request, $data, 'GET');
			file_put_contents($cacheFile, $payload instanceof SimpleXMLElement ? $payload->asXml() : json_encode($payload));
			return $payload;

		} catch (TwitterException $e) {
			if ($cache) {
				return $cache;
			}
			throw $e;
		}
	}



	/**
	 * Makes twitter links, @usernames and #hashtags clickable.
	 * @param  string
	 * @return string
	 */
	public static function clickable($s)
	{
		return preg_replace_callback(
			'~(?<!\w)(https?://\S+\w|www\.\S+\w|@\w+|#\w+)|[<>&]~u',
			array(__CLASS__, 'clickableCallback'),
			html_entity_decode($s, ENT_QUOTES, 'UTF-8')
		);
	}



	private static function clickableCallback($m)
	{
		$m = htmlspecialchars($m[0]);
		if ($m[0] === '#') {
			$m = substr($m, 1);
			return "<a href='http://twitter.com/search?q=%23$m'>#$m</a>";
		} elseif ($m[0] === '@') {
			$m = substr($m, 1);
			return "@<a href='http://twitter.com/$m'>$m</a>";
		} elseif ($m[0] === 'w') {
			return "<a href='http://$m'>$m</a>";
		} elseif ($m[0] === 'h') {
			return "<a href='$m'>$m</a>";
		} else {
			return $m;
		}
	}



	/**
	 * Shortens URL using http://is.gd API.
	 * @param  array
	 * @return string
	 */
	private function shortenUrl($m)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://is.gd/api.php?longurl=' . urlencode($m[0]));
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		return curl_errno($curl) || $code >= 400 ? $m[0] : $result;
	}



	private static function getFormat($flag)
	{
		static $formats = array(self::XML => 'xml', self::JSON => 'json', self::RSS => 'rss', self::ATOM => 'atom');
		$flag = $flag & 0x30;
		if (isset($formats[$flag])) {
			return $formats[$flag];
		} else {
			throw new InvalidArgumentException('Invalid format');
		}
	}

}



/**
 * An exception generated by Twitter.
 */
class TwitterException extends Exception
{
}