<?php

/**
 * AngryCurl - Anonymized Rolling Curl class
 *
 * @author  Nedzelsky Alexander <to.naive@gmail.com>
 * @link    http://stupid.su/php-curl_multi/
 * @licence GPL
 * @version 0.4
 *
 * @todo stop on error_limit exceed
 * @todo "on the fly" change AngryCurlRequest fix
 * 
 * @uses RollingCurl
 * @uses cURL
 * 
 * @var array   $debug_info         -   debug information
 * @var bool    $debug_log          -   Enable/disable debug log
 * @var bool    $console_mode       -   Enable/disable loggin information direct to 'user's browser on a fly'
 * @var array   $array_alive_proxy  -   alive proxy array needed to transfer data from proxy filtering function in its callback
 * @var array   $array_proxy        -   proxy list
 * @var array   $array_url          -   url list to parse
 * @var array   $array_useragent    -   useragents to change
 * @var bool    $error_limit        -   Limit of invalid http responses before die, 0 - unlimited // not implemented yet
 * @var bool    $array_valid_http_code- Array of valid http response codes, default  // not implemented yet
 * @var int     $n_proxy            -   proxies amount
 * @var int     $n_useragent        -   useragents amount
 * @var int     $n_url              -   urls amount
 * @var string  $proxy_test_url     -   url address to connect to for testing proxies
 * @var string  $proxy_valif_regexp -   regexp needed to be shure that response hasn`t been modified by proxy
 * @var bool    $use_proxy_list     -   Flag that is set in load_proxy_list method
 * @var bool    $use_useragent_list -   Flag that is set in load_useragent_list method
 */
class AngryCurl extends RollingCurl {
    public static $debug_info       =   array();
    public static $debug_log        =   false;
    protected static $console_mode  =   false;
    
    
    protected static $array_alive_proxy=array();
    protected $array_proxy          =   array();
    protected $array_url            =   array();
    protected $array_useragent      =   array();
    
    protected $error_limit          =   0; // not implemented yet
    protected $array_valid_http_code=   array(200); // not implemented yet
    
    protected $n_proxy              =   0;
    protected $n_useragent          =   0;
    protected $n_url                =   0;
    
    protected $proxy_test_url       =   'http://google.com';
    protected static $proxy_valid_regexp   =   '';
    
    private $use_proxy_list       =   false;
    private $use_useragent_list   =   false;
    
    /**
     * AngryCurl constructor
     *
     * @throws AngryCurlException
     * 
     * @param string $callback Callback function name
     * @param bool $debug_log Enable/disable writing log to $debug_info var (false by default to reduce memory consumption)
     * 
     * @return void
     */
    function __construct($callback = null, $debug_log = false)
    {
        self::$debug_log = $debug_log;
        
        # writing debug
        self::add_debug_msg("# Building");
        
        # checking if cURL enabled
        if(!function_exists('curl_init'))
        {
            throw new AngryCurlException("(!) cURL is not enabled");
        }
        
        parent::__construct($callback);
    }

    /**
     * Initializing console mode
     *
     * @return void
     */
    public function init_console()
    {
        self::$console_mode = true;
        
        echo "<pre>";
        
        # Internal Server Error fix in case no apache_setenv() function exists
        if (function_exists('apache_setenv'))
        {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        for ($i = 0; $i < ob_get_level(); $i++)
            ob_end_flush();
        ob_implicit_flush(1);
        
        # writing debug
        self::add_debug_msg("# Console mode activated");
    }

    /**
     * Request execution overload
     *
     * @access public
     *
     * @throws AngryCurlException
     * 
     * @param string $url Request URL
     * @param enum(GET/POST) $method
     * @param array $post_data
     * @param array $headers
     * @param array $options
     * 
     * @return bool
     */
    public function request($url, $method = "GET", $post_data = null, $headers = null, $options = null)
    {
        if($this->n_proxy > 0 && $this->use_proxy_list)
        {
            $options[CURLOPT_PROXY]=$this->array_proxy[ mt_rand(0, $this->n_proxy-1) ];
        //    self::add_debug_msg("Using PROXY({$this->n_proxy}): ".$options[CURLOPT_PROXY]);
        }
        elseif($this->n_proxy < 1 && $this->use_proxy_list)
        {
            throw new AngryCurlException("(!) Option 'use_proxy_list' is set, but no alive proxy available");
        }
        
        if($this->n_useragent > 0 && $this->use_useragent_list)
        {
            $options[CURLOPT_USERAGENT]=$this->array_useragent[ mt_rand(0, $this->n_useragent-1) ];
        //    self::add_debug_msg("Using USERAGENT: ".$options[CURLOPT_USERAGENT]);
        }
        elseif($this->n_useragent < 1 && $this->use_useragent_list)
        {
            throw new AngryCurlException("(!) Option 'use_useragent_list' is set, but no useragents available");
        }

        parent::request($url, $method, $post_data, $headers, $options);
        return true;
    }
    
    /**
     * Starting connections function execution overload
     *
     * @access public
     *
     * @throws AngryCurlException
     *
     * @param int $window_size Max number of simultaneous connections
     *
     * @return string|bool
     */
    public function execute($window_size = null)
    {
        # checking $window_size var
        if($window_size == null)
        {
            self::add_debug_msg(" (!) Default threads amount value (5) is used");
        }
        elseif($window_size > 0 && is_int($window_size))
        {
            self::add_debug_msg(" * Threads set to:\t$window_size");
        }
        else
        {
            throw new AngryCurlException(" (!) Wrong threads amount in execute():\t$window_size");
        }
        
        # writing debug
        self::add_debug_msg(" * Starting connections");
        //var_dump($this->__get('requests'));
        
        $time_start = microtime(1);
        $result = parent::execute($window_size);
        $time_end = microtime(1);
        
        # writing debug
        self::add_debug_msg(" * Finished in ".round($time_end-$time_start,2)."s");
        
        return $result;
    }
    
    /**
     * Flushing requests map for re-using purposes
     *
     * @return void
     */
    public function flush_requests()
    {
        $this->__set('requests', array());
    }
    
    /**
     * Useragent list loading method
     *
     * @access public
     * 
     * @param string/array $input Input proxy data, could be an array or filename
     * @return integer Amount of useragents loaded
     */
    public function load_useragent_list($input)
    {
        # writing debug
        self::add_debug_msg("# Start loading useragent list");
        
        # defining proxiess
        if(is_array($input))
        {
            $this->array_useragent = $input;
        }
        else
        {        
            $this->array_useragent = $this->load_from_file($input);
        }
        
        # setting amount
        $this->n_useragent = count($this->array_useragent);
        
        # writing debug
        if($this->n_useragent > 0)
        {
            self::add_debug_msg("# Loaded useragents:\t{$this->n_useragent}");
        }
        else
        {
            throw new AngryCurlException("# (!) No useragents loaded");
        }
        
        # Setting flag to prevent using AngryCurl without useragents
        $this->use_useragent_list = true;
        
        return $this->n_useragent;
    }

    /**
     * Proxy list loading and filtering method
     *
     * @access public
     *
     * @throws AngryCurlException
     * 
     * @param string/array $input Input proxy data, could be an array or filename
     * @param integer $window_size Max number of simultaneous connections when testing
     * @param enum(http/socks5) $proxy_type
     * @param string $proxy_test_url URL needed for proxy test requests
     * @param regexp $proxy_valid_regexp Regexp needed to be shure that response hasn`t been modified by proxy
     * 
     * @return bool
     */
    public function load_proxy_list($input, $window_size = 5, $proxy_type = 'http', $proxy_test_url = 'http://google.com', $proxy_valid_regexp = null)
    {
        # writing debug
        self::add_debug_msg("# Start loading proxies");
        
        # defining proxiess
        if(is_array($input))
        {
            $this->array_proxy = $input;
        }
        else
        {
            $this->array_proxy = $this->load_from_file($input);
        }        
        
        # checking $window_size var
        if( intval($window_size) < 1 || !is_int($window_size) )
        {
            throw new AngryCurlException(" (!) Wrong threads amount in load_proxy_list():\t$window_size");
        }

        
        # setting proxy type
        if($proxy_type == 'socks5')
        {
            self::add_debug_msg(" * Proxy type set to:\tSOCKS5");
            $this->__set('options', array(CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5));
        }
        else
        {
            self::add_debug_msg(" * Proxy type set to:\tHTTP");
        }
            
        # setting amount
        $this->n_proxy = count($this->array_proxy);
        self::add_debug_msg(" * Loaded proxies:\t{$this->n_proxy}");
        
        # filtering alive proxies
        if($this->n_proxy>0)
        {
            # removing duplicates
            $n_dup = count($this->array_proxy);
            # by array_values bug was fixed in random array indexes using mt_rand in request()
            $this->array_proxy = array_values( array_unique( $this->array_proxy) );
            $n_dup -= count($this->array_proxy);
            
            self::add_debug_msg(" * Removed duplicates:\t{$n_dup}");
            unset($n_dup);
            
            # updating amount
            $this->n_proxy = count($this->array_proxy);
            self::add_debug_msg(" * Unique proxies:\t{$this->n_proxy}");
            
            # setting url for testing proxies
            $this->proxy_test_url = $proxy_test_url;
            self::add_debug_msg(" * Proxy test URL:\t{$this->proxy_test_url}");
            
            # setting regexp for testing proxies
            if( !empty($proxy_valid_regexp) )
            {
                self::$proxy_valid_regexp = $proxy_valid_regexp;
                self::add_debug_msg(" * Proxy test RegExp:\t".self::$proxy_valid_regexp);
            }
            
            $this->filter_alive_proxy($window_size); 
        }
        else
        {
            throw new AngryCurlException(" (!) Proxies amount < 0 in load_proxy_list():\t{$this->n_proxy}");
        }
        
        # Setting flag to prevent using AngryCurl without proxies
        $this->use_proxy_list = true;   
    }
    
    /**
     * Filtering proxy array method, choosing alive proxy only
     *
     * @return void
     */
    public static function callback_proxy_check($response, $info, $request)
    {
        static $rid = 0;
        $rid++;
    
        if($info['http_code']!==200)
        {
            self::add_debug_msg("   $rid->\t".$request->options[CURLOPT_PROXY]."\tFAILED\t".$info['http_code']."\t".$info['total_time']."\t".$info['url']);
            return;
        }

        if(!empty(self::$proxy_valid_regexp) && !@preg_match('#'.self::$proxy_valid_regexp.'#', $response) )
        {
            self::add_debug_msg("   $rid->\t".$request->options[CURLOPT_PROXY]."\tFAILED\tRegExp match:\t".self::$proxy_valid_regexp."\t".$info['url']);
            return;
        }
            self::add_debug_msg("   $rid->\t".$request->options[CURLOPT_PROXY]."\tOK\t".$info['http_code']."\t".$info['total_time']."\t".$info['url']);
            self::$array_alive_proxy[] = $request->options[CURLOPT_PROXY];
    }
    
    /**
     * Filtering proxy array, choosing alive proxy only
     *
     * @throws AngryCurlException
     *
     * @param integer $window_size Max number of simultaneous connections when testing
     *
     * @return void
     */
    protected function filter_alive_proxy($window_size = 5)
    {
        # writing debug
        self::add_debug_msg("# Start testing proxies");
        
        # checking $window_size var
        if( intval($window_size) < 1 || !is_int($window_size) )
        {
            throw new AngryCurlException(" (!) Wrong threads amount in filter_alive_proxy():\t$window_size");
        }
        
        $buff_callback_func = $this->__get('callback');
        $this->__set('callback',array('AngryCurl', 'callback_proxy_check'));

        # adding requests to stack
        foreach($this->array_proxy as $id => $proxy)
        {
            # there won't be any regexp checks, just this :)
            if( strlen($proxy) > 4)
                $this->request($this->proxy_test_url, $method = "GET", null, null, array(CURLOPT_PROXY => $proxy) );
        }

        # run
        $this->execute($window_size);
        
        #flushing requests
        $this->__set('requests', array());

        # writing debug
        self::add_debug_msg("# Alive proxies:\t".count(self::$array_alive_proxy)."/".$this->n_proxy);
        
        # updating params
        $this->n_proxy = count(self::$array_alive_proxy);
        $this->array_proxy = self::$array_alive_proxy;
        $this->__set('callback', $buff_callback_func);
    }

    /**
     * Loading info from external files
     *
     * @access private
     * @param string $filename
     * @param string $delim
     * @return array
     */
    protected function load_from_file($filename, $delim = "\n")
    {
        $data;
        $fp = @fopen($filename, "r");
        
        if(!$fp)
        {
            self::add_debug_msg("(!) Failed to open file: $filename");
            return array();
        }
        
        $data = @fread($fp, filesize($filename) );
        fclose($fp);
        
        if(strlen($data)<1)
        {
            self::add_debug_msg("(!) Empty file: $filename");
            return array();
        }
        
        $array = explode($delim, $data);
        
        if(is_array($array) && count($array)>0)
        {
            foreach($array as $k => $v)
            {
                if(strlen( trim($v) ) > 0)
                    $array[$k] = trim($v);
            }
            return $array;
        }
        else
        {
            self::add_debug_msg("(!) Empty data array in file: $filename");
            return array();
        }
    }
    
    /**
     * Printing debug information method
     *
     * @access public
     * @return void
     */
    public static function print_debug()
    {
        echo "<pre>";
        echo htmlspecialchars( implode("\n", self::$debug_info) );
        echo "</pre>";
    }
    
    /**
     * Logging method
     *
     * @access public
     * @param string $msg message
     * @return void
     */
    public static function add_debug_msg($msg)
    {
        if(self::$debug_log)
        {
            self::$debug_info[] = $msg;
        }
        
        if(self::$console_mode)
        {
            echo htmlspecialchars($msg)."\r\n";
        }
    }

    /**
     * AngryCurl destructor
     * 
     * @return void
     */
    function __destruct()
    {
        self::add_debug_msg("# Finishing ...");
        parent::__destruct();
    } 
}

/**
 * AngryCurl custom exception
 */
class AngryCurlException extends Exception
{
    public function __construct($message = "", $code = 0 /*For PHP < 5.3 compatibility omitted: , Exception $previous = null*/)
    {
        AngryCurl::add_debug_msg($message);
        parent::__construct($message, $code);
    }
}

/**
 * Class that represent a single curl request
 */
class AngryCurlRequest extends RollingCurlRequest
{
    
}

?>
