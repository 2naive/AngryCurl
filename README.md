# AngryCurl
- used for parsing information from remote resourse using user-predefined amount of simultaneous connections over proxies-list.

## Basic information

### Depencies:

* PHP 5 >= 5.1.0
* RollingCurl
* cURL
 
### Use cases:

* multi-threaded parsing over proxy
* overcoming simple parsing protection by using User-Agent header and proxy-lists
* proxy list checking
* validating proxies' response
 
### Main features

* loading proxy-list from file or array
* removing duplicates
* filtering alive proxies
* checking if proxy given response content is correct
* loading useragent-list from file or array
* changing proxy/useragent "on the fly"
* preventing direct connections without any proxy/useragent if such options are set
* multi-thread connections
* callback functions
* working with chains of requests
* web-console mode
* logging

## Documentation

### Preferred environment configuration

* PHP as Apache module
* safe_mode Off
* open_basedir is NOT set
* PHP cURL installed
* gzip Off

### Basic usage

```php
require("RollingCurl.class.php");
require("AngryCurl.class.php");

function my_callback($response, $info, $request)
{
    // callback function here
}

// sending callback function name as param
$AC = new AngryCurl('my_callback');
// initializing console-style output
$AC->init_console();


// Importing proxy and useragent lists, setting regexp, proxy type and target url for proxy check
// You may also import proxy from an array as simple as $AC->load_proxy_list($proxy array);
$AC->load_proxy_list(
    // path to proxy-list file
    'proxy_list.txt',
    // optional: number of threads
    200,
    // optional: proxy type
    'http',
    // optional: target url to check
    'http://google.com',
    // optional: target regexp to check
    'title>G[o]{2}gle'
);
// You may also import useragents from an array as simple as $AC->load_useragent_list($proxy array);
$AC->load_useragent_list('useragent_list.txt');

while(/* */)
{
    $url = /**/;
    // adding URL to queue
    $AC->get($url);
    
    // you may also use 
    // $AC->post($url, $post_data = null, $headers = null, $options = null);
    // $AC->get($url, $headers = null, $options = null);
    // $AC->request($url, $method = "GET", $post_data = null, $headers = null, $options = null);
    // as well
}

// setting amount of threads and starting connections
$AC->execute(200);

// if console_mode is off
//AngryCurl::print_debug(); 

unset($AC);
```

### cURL options

You may also pass cURL options for each url before adding to queue like here:
```php
// Define HTTP headers (CURLOPT_HTTPHEADER) if needed, or just set to NULL
$headers = array('Content-type: text/plain', 'Content-length: 100');
// Define cURL options (will be passed through curl_setopt_array) if needed, or just set to NULL
$options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
// Define post-data array to send in case of POST method, or just set to NULL
$post_data = array('param' => 'value');

// Add request
$AC->get($url, $headers, $options);
// or
$AC->post($url, $post_data, $headers, $options) ;
// or
$AC->request($url, $method = "GET", $post_data, $headers, $options);

// ATTENTION: temporary "on-the-fly" proxy/useragents lists are not
// working with AngryCurlRequest. Keep it in mind if you will use code below
// as alternative to written above.

$request = new AngryCurlRequest($url);
// $url, $method, $post_data, $headers, $options - public properties of AngryCurlRequest
$request->options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
$AC->add($request);
```

Because this class is kind of extension of RollingCurl class you may use any constructions RollingCurl has.
For other information read here:
http://code.google.com/p/rolling-curl/source/browse/trunk/

## TODO
* chains of requests
* stop on error_limit exceed
* better documentation and examples

## Credits
You may join this class discussion here:
http://stupid.su/php-curl_multi/
Any questions, change requests and other things you may send to my email written in class comments.

Thank you for reading.
- naive
