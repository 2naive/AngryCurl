<?php
/**
 * AngryCurl - Anonymized Rolling Curl class
 *
 * @author Nedzelsky Alexander <to.naive@gmail.com>
 * @version 0.1
 *
 * @uses RollingCurl
 * @uses cURL
 * 
 * @var array   $debug_info         -   debug information
 * @var array   $array_alive_proxy  -   alive proxy array needed to transfer data from proxy filtering function in its callback
 * @var array   $array_proxy        -   proxy list
 * @var array   $array_url          -   url list to parse
 * @var array   $array_useragent    -   useragents to change
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
    
    protected $n_proxy              =   0;
    protected $n_useragent          =   0;
    protected $n_url                =   0;
    
    protected $proxy_test_url       =   'http://google.com';
    protected static $proxy_valid_regexp   =   '';
    
    //protected $curl_options         =   array();

    function __construct($callback = null)
    {
        # writing debug
        $this->add_debug_msg("# Building");
        parent::__construct($callback);
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
        $this->add_debug_msg("# Start loading proxies");
        
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
            $this->add_debug_msg("Proxy type: SOCKS5");
            $this->__set('options', array(CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5));
        }else
            $this->add_debug_msg("Proxy type: HTTP");
        
        # setting url for testing proxies
        $this->proxy_test_url = $proxy_test_url;
        
        # setting regexp for testing proxies
        if( !empty($proxy_valid_regexp) )
        {
            self::$proxy_valid_regexp = $proxy_valid_regexp;
            $this->add_debug_msg("Proxy test RegExp: ".self::$proxy_valid_regexp);
        }
        
        # writing debug
        $this->add_debug_msg("Proxy test URL: {$this->proxy_test_url}");
        $this->add_debug_msg("Loaded proxies: {$this->n_proxy}");

        
        # filtering alive proxies
        if($this->n_proxy>0)
            $this->filter_alive_proxy();
        else
            $this->add_debug_msg("# Testing proxies aborted");        

        
    }
    
    /**
     * Filtering proxy array method, choosing alive proxy only
     *
     * @return void
     */
    public static function callback_proxy_check($response, $info, $request)
    {
        if($info['http_code']!==200)
        {
            self::$debug_info[] = "->\t".$request->options[CURLOPT_PROXY]."\tFAILED\t".$info['http_code']."\t".$info['total_time']."\t".$info['url'];
            return;
        }

        if(!empty(AngryCurl::$proxy_valid_regexp) && !@preg_match('#'.AngryCurl::$proxy_valid_regexp.'#', $response) )
        {
            self::$debug_info[] = "->\t".$request->options[CURLOPT_PROXY]."\tFAILED\tRegExp match:\t".AngryCurl::$proxy_valid_regexp."\t".$info['url'];
            return;
        }
            self::$debug_info[] = "->\t".$request->options[CURLOPT_PROXY]."\tOK\t".$info['http_code']."\t".$info['total_time']."\t".$info['url'];
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
        $this->add_debug_msg("# Start testing proxies");
        
        $buff_callback_func = $this->__get('callback');
        $this->__set('callback',array('AngryCurl', 'callback_proxy_check'));
        
        # adding requests to stack
        foreach($this->array_proxy as $id => $proxy)
        {
                $this->request($this->proxy_test_url, $method = "GET", null, null, array(CURLOPT_PROXY => $proxy) );
        }

        # run
        $this->add_debug_msg("# Running proxy test connections");
        
        $time_start = microtime(1);
        $this->execute();
        $time_end = microtime(1);
        
        #flushing requests
        $this->__set('requests', array());

        # writing debug
        $this->add_debug_msg("Alive proxies: ".count(self::$array_alive_proxy)."/".$this->n_proxy);
        
        # updating params
        $this->n_proxy = count(self::$array_alive_proxy);
        $this->array_proxy = self::$array_alive_proxy;
        $this->__set('callback', $buff_callback_func);
        
        $this->add_debug_msg("# Testing proxies finished in ".round($time_end-$time_start,2)."s");
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
            return array();
        }
        
        $data = fread($fp, filesize($filename) );
        fclose($fp);
        
        if(strlen($data)<1)
            return array();
        
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
            return array();
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
     * @access protected
     * @var string $msg message
     * @return void
     */
    protected static function add_debug_msg($msg)
    {
        self::$debug_info[] = $msg;
    }

    function __destruct() {
        parent::__destruct();
    } 
}
?>