<?php

# Setting time and memory limits
ini_set('max_execution_time',0);
ini_set('memory_limit', '128M');

define('AC_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

# Including classes
require_once( AC_DIR  . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'RollingCurl.class.php');
require_once( AC_DIR  . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AngryCurl.class.php');

# Initializing AngryCurl instance with callback function named 'callback_function'
$AC = new AngryCurl('callback_function');

# Initializing so called 'web-console mode' with direct cosnole-like output
$AC->init_console();

# Importing proxy and useragent lists, setting regexp, proxy type and target url for proxy check
# You may import proxy from an array as simple as $AC->load_proxy_list($proxy array);
$AC->load_proxy_list(
    AC_DIR  . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'proxy_list.txt',
    # optional: number of threads
    200,
    # optional: proxy type
    'http',
    # optional: target url to check
    'http://google.com',
    # optional: target regexp to check
    'title>G[o]{2}gle'
);
$AC->load_useragent_list( AC_DIR  . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'useragent_list.txt');

/* NOTE: IF USING request() - "on the fly" proxy server changing WILL apply
 * 
 * You may use request(URL, METHOD, POST_DATA, HEADERS, CURL OPTIONS) to create add new requests
 *  METHOD        may be GET or POST
 *  POST_DATA     may be an array of POST-params
 *  HEADERS       may be any HTTP headers
 *  CURL OPTIONS  may be any of supported by CURL
 */
$AC->request('http://ya.ru');

/* NOTE: IF USING get() - "on the fly" proxy server changing WILL apply
 * 
 * You may use shorcut get(URL, HEADERS, CURL OPTIONS) to create add new GET requests
 *  HEADERS       may be any HTTP headers
 *  CURL OPTIONS  may be any of supported by CURL
 */
$AC->get('http://ya.ru');

/* NOTE: IF USING post() - "on the fly" proxy server changing WILL apply
 *
 * You may use shorcut post(URL, POST_DATA, HEADERS, CURL OPTIONS) to create add new GET requests
 *  POST_DATA     may be an array of POST-params
 *  HEADERS       may be any HTTP headers
 *  CURL OPTIONS  may be any of supported by CURL
 */
$AC->post('http://ya.ru');

/* WARNING: IF USING AngryCurlRequest - no "on the fly" proxy server changing will apply
 *
 * You may use AngryCurlRequest(URL, METHOD, POST_DATA, HEADERS, CURL OPTIONS) to create add new requests
 *  METHOD        may be GET or POST
 *  POST_DATA     may be an array of POST-params
 *  HEADERS       may be any HTTP headers
 *  CURL OPTIONS  may be any of supported by CURL
 *
 * Properties are public, they may be passed to constructer on changed after as in example below
 */
$request = new AngryCurlRequest('http://ya.ru');
$request->options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
$AC->add($request);

# Starting with number of threads = 200
$AC->execute(200);

# You may pring debug information, if console_mode is NOT on ( $AC->init_console(); )
//AngryCurl::print_debug(); 

# Destroying
unset($AC);

# Callback function example
function callback_function($response, $info, $request)
{
    if($info['http_code']!==200)
    {
        AngryCurl::add_debug_msg(
            "->\t" .
            $request->options[CURLOPT_PROXY] .
            "\tFAILED\t" .
            $info['http_code'] .
            "\t" .
            $info['total_time'] .
            "\t" .
            $info['url']
        );
    }else
    {
        AngryCurl::add_debug_msg(
            "->\t" .
            $request->options[CURLOPT_PROXY] .
            "\tOK\t" .
            $info['http_code'] .
            "\t" .
            $info['total_time'] .
            "\t" .
            $info['url']
        );

    }
    
    return;
}
