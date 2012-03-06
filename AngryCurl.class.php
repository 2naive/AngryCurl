<?php

/**
 * AngryCurl - Anonymized Rolling Curl class
 *
 * @author Nedzelsky Alexander <to.naive@gmail.com>
 * @version 0.2
 *
 * @uses RollingCurl
 * @uses cURL
 * 
 * @var array   $debug_info         -   debug information
 * @var array   $array_alive_proxy  -   alive proxy array needed to transfer data from proxy filtering function in its callback
 * @var array   $array_proxy        -   proxy list
 * @var array   $array_url          -   url list to parse
 * @var array   $array_useragent    -   useragents to change
 * @var bool    $use_proxy_list     -
 * @var bool    $use_useragent_list -
 * @var bool    $console_mode       -   Enable/disable autoupdated echo using JS // not implemented yet
 * @var bool    $error_limit        -   Limit of invalid http responses before die, 0 - unlimited // not implemented yet
 * @var bool    $array_valid_http_code- Array of valid http response codes, default  // not implemented yet
 * @var int     $n_proxy            -   proxies amount
 * @var int     $n_useragent        -   useragents amount
 * @var int     $n_url              -   urls amount
 * @var string  $proxy_test_url     -   url address to connect to for testing proxies
 * @var string  $proxy_valif_regexp -   regexp needed to be shure that response hasn`t been modified by proxy
 */
class AngryCurl extends RollingCurl {
    public static $debug_info       =   array();

    protected static $array_alive_proxy=array();
    protected $array_proxy          =   array();
    protected $array_url            =   array();
    protected $array_useragent      =   array();
    
    protected $use_proxy_list       =   false;
    protected $use_useragent_list   =   false;
    protected static $console_mode  =   false;
    
    protected $error_limit          =   0; // not implemented yet
    protected $array_valid_http_code=   array(200); // not implemented yet
    
    protected $n_proxy              =   0;
    protected $n_useragent          =   0;
    protected $n_url                =   0;
    
    protected $proxy_test_url       =   'http://google.com';
    protected static $proxy_valid_regexp   =   '';
    
    //protected $curl_options         =   array();

    function __construct($callback = null)
    {
        # writing debug
        self::add_debug_msg("# Building");
        parent::__construct($callback);
    }

    /**
     * Initializing console mode
     *
     * @return voide
     */
    public function init_console()
    {
        self::$console_mode = true;
        
        echo "<pre>";
        
        @apache_setenv('no-gzip', 1);
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
     * @var string $url Request URL
     * @var enum(GET/POST) $method
     * @var array $post_data
     * @var array $headers
     * @var array $options
     * 
     * @return bool
     */
    public function request($url, $method = "GET", $post_data = null, $headers = null, $options = null) {
        
        if($this->n_proxy > 0 && $this->use_proxy_list)
        {
            $options[CURLOPT_PROXY]=$this->array_proxy[ rand(0, $this->n_proxy-1) ];
        //    self::add_debug_msg("Using PROXY({$this->n_proxy}): ".$options[CURLOPT_PROXY]);
        }
        if($this->n_useragent > 0 && $this->use_useragent_list)
        {
            $options[CURLOPT_USERAGENT]=$this->array_useragent[ rand(0, $this->n_useragent-1) ];
        //    self::add_debug_msg("Using USERAGENT: ".$options[CURLOPT_USERAGENT]);
        }
        
        parent::request($url, $method, $post_data, $headers, $options);
        return true;
    }
    
    /**
     * Useragent list loading method
     *
     * @access public
     * 
     * @var string/array $input Input proxy data, could be an array or filename
     * @return bool
     */
    public function load_useragent_list($input)
    {
        # writing debug
        self::add_debug_msg("# Start loading useragent list");
        
        # defining proxiess
        if(is_array($input))
        {
            $this->array_useragent = $input;
        }else
        {        
            $this->array_useragent = $this->load_from_file($input);
        }
        
        # setting amount
        $this->n_useragent = count($this->array_useragent);
        
        # writing debug
        if($this->n_useragent > 0)
            self::add_debug_msg("# Loaded useragents: {$this->n_useragent}");
    }

    /**
     * Proxy list loading and filtering method
     *
     * @access public
     * 
     * @var string/array $input Input proxy data, could be an array or filename
     * @var enum(http/socks5) $proxy_type
     * @var string $proxy_test_url URL needed for proxy test requests
     * @var regexp $proxy_valid_regexp Regexp needed to be shure that response hasn`t been modified by proxy
     * 
     * @return bool
     */
    public function load_proxy_list($input, $proxy_type = 'http', $proxy_test_url = 'http://google.com', $proxy_valid_regexp = null)
    {
        # writing debug
        self::add_debug_msg("# Start loading proxies");
        
        # defining proxiess
        if(is_array($input))
        {
            $this->array_proxy = $input;
        }else
        {        
            $this->array_proxy = $this->load_from_file($input);
        }
        
        # setting amount
        $this->n_proxy = count($this->array_proxy);
        
        # setting proxy type
        if($proxy_type == 'socks5')
        {
            self::add_debug_msg("Proxy type: SOCKS5");
            $this->__set('options', array(CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5));
        }else
            self::add_debug_msg("Proxy type: HTTP");
        
        # setting url for testing proxies
        $this->proxy_test_url = $proxy_test_url;
        
        # setting regexp for testing proxies
        if( !empty($proxy_valid_regexp) )
        {
            self::$proxy_valid_regexp = $proxy_valid_regexp;
            self::add_debug_msg("Proxy test RegExp: ".self::$proxy_valid_regexp);
        }
        
        # writing debug
        self::add_debug_msg("Proxy test URL: {$this->proxy_test_url}");
        self::add_debug_msg("Loaded proxies: {$this->n_proxy}");

        
        # filtering alive proxies
        if($this->n_proxy>0)
            $this->filter_alive_proxy();
        else
            self::add_debug_msg("# Testing proxies aborted");        

        
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
            self::add_debug_msg("$rid->\t".$request->options[CURLOPT_PROXY]."\tFAILED\t".$info['http_code']."\t".$info['total_time']."\t".$info['url']);
            return;
        }

        if(!empty(self::$proxy_valid_regexp) && !@preg_match('#'.self::$proxy_valid_regexp.'#', $response) )
        {
            self::add_debug_msg("$rid->\t".$request->options[CURLOPT_PROXY]."\tFAILED\tRegExp match:\t".self::$proxy_valid_regexp."\t".$info['url']);
            return;
        }
            self::add_debug_msg("$rid->\t".$request->options[CURLOPT_PROXY]."\tOK\t".$info['http_code']."\t".$info['total_time']."\t".$info['url']);
            self::$array_alive_proxy[] = $request->options[CURLOPT_PROXY];
    }
    
    /**
     * Filtering proxy array, choosing alive proxy only
     *
     * @return void
     */
    protected function filter_alive_proxy()
    {
        # writing debug
        self::add_debug_msg("# Start testing proxies");
        
        $buff_callback_func = $this->__get('callback');
        $this->__set('callback',array('AngryCurl', 'callback_proxy_check'));
        
        # adding requests to stack
        foreach($this->array_proxy as $id => $proxy)
        {
                $this->request($this->proxy_test_url, $method = "GET", null, null, array(CURLOPT_PROXY => $proxy) );
        }

        # run
        self::add_debug_msg("# Running proxy test connections");
        
        $time_start = microtime(1);
        $this->execute();
        $time_end = microtime(1);
        
        #flushing requests
        $this->__set('requests', array());

        # writing debug
        self::add_debug_msg("Alive proxies: ".count(self::$array_alive_proxy)."/".$this->n_proxy);
        
        # updating params
        $this->n_proxy = count(self::$array_alive_proxy);
        $this->array_proxy = self::$array_alive_proxy;
        $this->__set('callback', $buff_callback_func);
        
        self::add_debug_msg("# Testing proxies finished in ".round($time_end-$time_start,2)."s");
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
        $fp = @fopen($filename, "r");
        
        if(!$fp)
        {
            self::add_debug_msg("# Failed to open file: $filename");
            return array();
        }
        
        $data = fread($fp, filesize($filename) );
        fclose($fp);
        
        if(strlen($data)<1)
        {
            self::add_debug_msg("# Empty file: $filename");
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
            self::add_debug_msg("# Empty data array in file: $filename");
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
     * @var string $msg message
     * @return void
     */
    public static function add_debug_msg($msg)
    {
        self::$debug_info[] = $msg;

        if(self::$console_mode)
        {
            echo htmlspecialchars($msg)."\r\n";
        }
    }

    function __destruct() {
        parent::__destruct();
    } 
}
?>