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

$AC->load_useragent_list( AC_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'useragent_list.txt');

?>

# Adding first 10 requests

<?
for($i=1; $i<=10; $i++)
    $AC->get("http://ya.ru/?$i");
?>

# Starting with number of threads = 10
# After each of first 5 finished request - new one will be added
# They all will proceed to execute immediatly (see callback_function)

<?
$AC->execute(10);
?>

# Totally run 15 requests

# Let's add 2 more

<?
$AC->get('http://ya.ru/?after_finished_1');
$AC->get('http://ya.ru/?after_finished_2');
?>

# Nothing happend, lets run execute() again

<?
$AC->execute(10);
?>

# Previous 15 request + 2 new executed
# Let's flush them and add new requests

<?
$AC->flush_requests();

$AC->get('http://ya.ru/?after_flushed_1');
$AC->get('http://ya.ru/?after_flushed_2');
$AC->get('http://ya.ru/?after_flushed_3');

$AC->execute(10);
?>

# We see just 3 new requests

<?
# Callback function
function callback_function($response, $info, $request)
{
    global $AC;
    static $count=0;
    
    if($count<5)
    {
        $count++;
        $AC->get("http://ya.ru/?callback$count");
    }
    
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
