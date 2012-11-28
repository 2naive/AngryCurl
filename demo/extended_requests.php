<?php

# Setting time and memory limits
ini_set('max_execution_time',0);
ini_set('memory_limit', '128M');

# Including classes
require_once( dirname(__DIR__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'RollingCurl.class.php');
require_once( dirname(__DIR__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AngryCurl.class.php');

# Initializing AngryCurl instance with callback function named 'callback_function'
$AC = new AngryCurl('callback_function');

# Initializing so called 'web-console mode' with direct cosnole-like output
$AC->init_console();

# Setting amount of threads
$AC->__set('window_size', 200);

# Importing proxy and useragent lists, setting regexp, proxy type and target url for proxy check
$AC->load_proxy_list(
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'proxy_list.txt',
    'http',
    'http://google.com',
    'title>G[o]{2}gle'
);
$AC->load_useragent_list( dirname(__DIR__) . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'useragent_list.txt');

# Setting flags
$AC->__set('use_proxy_list', true);
$AC->__set('use_useragent_list', true);

# Basic request usage (for extended - see demo folder)
$AC->get('http://ya.ru');
$AC->get('http://ya.ru');
$AC->get('http://ya.ru');

# Starting
$AC->execute();

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
