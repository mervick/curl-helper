<?php

/**
 * Class CurlHelper
 */
class CurlHelper
{
    public $url;

    public $response;
    public $headers;
    public $content;
    public $cookies;
    public $status;

    public $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.76 Safari/537.36';
    public $timeout = 30;

    protected $ch;

    protected $get_params = [];
    protected $set_post = [];
    protected $post_raw;
    protected $set_cookies = [];
    protected $set_headers = [];

    protected $files = [];

    const CT_FORM_DATA      = 'multipart/form-data';
    const CT_JSON           = 'application/json';
    const CT_FORM_ENCODED   = 'application/x-www-form-urlencoded';


    function __construct($url=null)
    {
        $this->ch = curl_init();
        $this->url = $url;
    }

    protected function prepare()
    {
        $components = parse_url($this->url);
        parse_str($components['query'], $str);
        $components['query'] = http_build_query(array_merge($str, $this->get_params));
        $this->url = http_build_url($components);

        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function follow($follow=true)
    {
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
        return $this;
    }

    public function setUserAgent($ua)
    {
        $this->user_agent = $ua;
        return $this;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    function debug($debug=true)
    {
        curl_setopt($this->ch, CURLOPT_VERBOSE, $debug);
        return $this;
    }

    protected function getCookies()
    {
        if (preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $this->headers, $m)) {
            $cookies = [];
            foreach ($m[1] as $value) {
                $tmp = explode('=', $value, 2);
                $cookies[$tmp[0]] = explode(';', $tmp[1], 1)[0];
            }
            return $cookies;
        }
        return [];
    }

    function setPostRaw($raw)
    {
        $this->post_raw = $raw;
        return $this;
    }

    function setPostData($data)
    {
        $this->set_post = array_merge($this->set_post, $data);
        return $this;
    }

    function setGetData($data)
    {

    }

    function setHeaders($data)
    {
        $this->set_headers = array_merge($this->set_headers, $data);

        return $this;
    }

    function setCookies($data)
    {
        $this->set_cookies = array_merge($this->set_cookies, $data);

        return $this;
    }

    function postFile($filename, $field)
    {
        $eol = PHP_EOL;
        $BOUNDARY = '----' . md5(time());

        $this->setHeaders([
            'Content-Type' => 'multipart/form-data; boundary='.$BOUNDARY,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        if (!empty($this->set_cookies)) {
            $data = [];
            foreach ($this->set_cookies as $k => $v) {
                $data[] = "$k=$v";
            }
            curl_setopt($this->ch, CURLOPT_COOKIE, implode('; ', $data));
        }

        foreach ($this->set_post as $name => $data) {
            $BODY[] = "--$BOUNDARY$eol";
            $BODY[] = "Content-Disposition: form-data; name=\"$name\"$eol$eol";
            $BODY[] = "$data$eol";
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $filename);
        finfo_close($finfo);
        $basename = basename($filename);

        $BODY[] = "--$BOUNDARY$eol";
        $BODY[] = "Content-Disposition: form-data; name=\"$field\"; filename=\"$basename\"$eol";
        $BODY[] = "Content-Type: $type$eol$eol";

        $BODY[] = file_get_contents($filename) . $eol;
        $BODY[] = "--$BOUNDARY--$eol$eol";

        $BODY = implode('', $BODY);

        $this->setHeaders(['Content-Length' => strlen($BODY)]);

        if (!empty($this->set_headers)) {
            $data = [];
            foreach ($this->set_headers as $k => $v) {
                $data[] = "$k: $v";
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $BODY);

        $this->response = curl_exec($this->ch);
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $this->headers = substr($this->response, 0, $header_size);
        $this->content = substr($this->response, $header_size);
        $this->cookies = $this->getCookies();
        curl_close($this->ch);

        return $this;
    }

    function getJson()
    {
        return json_decode($this->content, true);
    }

    function exec()
    {
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        if (!empty($this->set_cookies)) {
            $data = [];
            foreach ($this->set_cookies as $k => $v) {
                $data[] = "$k=$v";
            }
            curl_setopt($this->ch, CURLOPT_COOKIE, implode('; ', $data));
        }
        if (!empty($this->set_post)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            if ($this->isJson) {
                $data = json_encode($this->set_post);
            } else {
                $data = http_build_query($this->set_post);
            }
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
            $this->setHeaders(['Content-Length' => strlen($data)]);
        }
        if (!empty($this->set_headers)) {
            $data = [];
            foreach ($this->set_headers as $k => $v) {
                $data[] = "$k: $v";
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        $this->response = curl_exec($this->ch);
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $this->headers = substr($this->response, 0, $header_size);
        $this->content = substr($this->response, $header_size);
        $this->cookies = $this->getCookies();
        $this->status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        curl_close($this->ch);

        return $this;
    }
}