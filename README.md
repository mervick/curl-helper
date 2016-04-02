CurlHelper
==========

Small useful helper for PHP Curl.  
Require PHP >= 5.4


## Installation
Install via `composer`:
```bash
composer require mervick/curl-helper
```
or download `CurlHelper.php` and include it in yours php files.


## Usage
```php
$url = 'http://example.com/path/script?get1=val1';

// Simple GET request
$response = CurlHelper::factory($url)->exec();

var_dump($response);
// Will be output array with few keys:
// $response['status']      - http code, example "200' on success
// $response['type']        - `Content-Type` from headers, example "application/json; charset=utf-8"
// $response['headers']     - assoc array with all parsed headers
// $response['cookies']     - assoc array with cookies from `Set-Cookie` headers
// $response['headers_raw'] - headers raw
// $response['content']     - content
// $response['data']        - assoc array of json decoded content or false
// $response['xpath']       - array with parsed xpath values or null


// Add and/or modify some GET params 
$response = CurlHelper::factory($url)
    ->setGetParams(['get2' => 'val2'])
    ->exec();

// Follow location
$response = CurlHelper::factory($url)
    ->follow(true)
    ->exec();

// Xpath
$response = CurlHelper::factory($url)
    ->xpath([
        'title' => '//title',
        'token' => '*/meta[@name="csrf-token"]/@content'
    ])
    ->exec();

// Writes verbose information to STDERR
$response = CurlHelper::factory($url)
    ->debug(true)
    ->exec();

// Writes verbose information to the file
$response = CurlHelper::factory($url)
    ->debug('/path/to/file')
    ->exec();

// POST request with headers
$response = CurlHelper::factory($url)
    // Content-Type: application/x-www-form-urlencoded
    // this one is default for POST, so you can skip it
    ->setHeaders(['Content-Type' => CurlHelper::MIME_X_WWW_FORM])
    ->setHeaders([
        'Some-Header1' => 'SomeValue1',
        'Some-Header2' => 'SomeValue2',
    ])
    ->setPostFields([
        'somePostField' => 'somePostVal',
        'somePostArray' => [ // POST array
            'item1',
            'item2',
        ],
    ])
    ->exec();

// JSON POST request
$response = CurlHelper::factory($url)
    // Content-Type: application/json
    ->setHeaders(['Content-Type' => CurlHelper::MIME_JSON])
    ->setPostFields(['somePostField' => 'somePostVal'])
    ->exec();

// Set cookies
$response = CurlHelper::factory($url)
    ->setCookies(['someField' => 'someVal'])
    ->exec();

// Send file
$response = CurlHelper::factory($url)
    // Content-Type: multipart/form-data
    // this is default for sending files, so you can skip it
    ->setHeaders(['Content-Type' => CurlHelper::MIME_FORM_DATA])
    ->putFile('fieldName', '/path/to/file')
    ->exec();

// Send multiple files 
$response = CurlHelper::factory($url)
    ->putFile('fieldName', '/path/to/file1')
    ->putFile('fieldNameArr[]', '/path/to/file2')
    ->putFile('fieldNameArr[]', '/path/to/file3')
    ->exec();

// Send raw file
$file_contents = file_get_contents('/file/to/path');
$response = CurlHelper::factory($url)
    ->putFileRaw('fieldName', $file_contents, 'some.name', 'mime-type')
    ->exec();
    
// Send POST contents raw
$response = CurlHelper::factory($url)
    ->setPostRaw($postRawContents)
    ->exec();
    
// Save and read cookies from/to the file
$response = CurlHelper::factory($url)
    ->setCookieFile('/path/to/file')
    ->exec();
    
// Use proxy
$response = CurlHelper::factory($url)
    ->useProxy('192.168.1.1:8080', 'login', 'password')
    ->exec();
    
// Set custom CURL options
$response = CurlHelper::factory($url)
    ->setOptions([
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
    ])
    ->exec();

```

## License
MIT
