<pre>
<?php

ini_set('max_execution_time',0);
ini_set('memory_limit', '128M');

require("RollingCurl.class.php");
require("AngryCurl.class.php");

function nothing($response, $info, $request)
{
    AngryCurl::$debug_info[] = $info['url']."\t".$info['http_code']."\t".$info['total_time']."\tOK";
    echo "nothing happens!\n";
}
$AC = new AngryCurl('nothing');
$AC->__set('window_size', 10);

$AC->load_proxy_list('./lib/proxy_list.txt','http','http://google.com','title>G[o]{2}gle');


$AC->get('http://ya.ru');
$AC->execute();
AngryCurl::print_debug();


unset($AC);
