<?php

ini_set('max_execution_time',0);
ini_set('memory_limit', '128M');

require("RollingCurl.class.php");
require("AngryCurl.class.php");

function nothing($response, $info, $request)
{
    if($info['http_code']!==200)
    {
        AngryCurl::$debug_info[] = "->\t".$request->options[CURLOPT_PROXY]."\tFAILED\t".$info['http_code']."\t".$info['total_time']."\t".$info['url'];
        return;
    }else
    {
        AngryCurl::$debug_info[] = "->\t".$request->options[CURLOPT_PROXY]."\tOK\t".$info['http_code']."\t".$info['total_time']."\t".$info['url'];
        return;
    }
    echo "nothing happens!\n";
}
$AC = new AngryCurl('nothing');
$AC->__set('window_size', 10);

$AC->load_proxy_list('./lib/proxy_list.txt','http','http://google.com','title>G[o]{2}gle');
$AC->load_useragent_list('./lib/useragent_list.txt');

$AC->__set('use_proxy_list',true);
$AC->__set('use_useragent_list',true);

$AC->get('http://ya.ru');
$AC->get('http://ya.ru');
$AC->get('http://ya.ru');
$AC->execute();
AngryCurl::print_debug();

unset($AC);
