<?php

/**
 * Class CurlHelper
 */
class CurlHelper
{
    /**
     * @var string
     */
    public $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.76 Safari/537.36';
    /**
     * @var int
     */
    public $timeout = 30;

    /**
     * @var resource
     */
    protected $ch;
    /**
     * @var null|string
     */
    protected $url;
    /**
     * @var array
     */
    protected $get_data = [];
    /**
     * @var array
     */
    protected $post_data = [];
    /**
     * @var null|string
     */
    protected $post_raw;
    /**
     * @var array
     */
    protected $cookies = [];
    /**
     * @var array
     */
    protected $headers = [];
    /**
     * @var array
     */
    protected $files = [];


    const CT_FORM_DATA      = 'multipart/form-data';
    const CT_JSON           = 'application/json';
    const CT_FORM_ENCODED   = 'application/x-www-form-urlencoded';


    /**
     * @param string $url [optional]
     */
    function __construct($url=null)
    {
        $this->ch = curl_init();
        $this->url = $url;
    }

    /**
     * @return $this
     */
    protected function prepare()
    {
        $components = parse_url($this->url);
        parse_str($components['query'], $str);
        $components['query'] = http_build_query(array_merge($str, $this->get_data));
        $this->url = http_build_url($components);

        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param bool $follow [optional]
     * @return $this
     */
    public function follow($follow=true)
    {
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
        return $this;
    }

    /**
     * @param string $ua
     * @return $this
     */
    public function setUserAgent($ua)
    {
        $this->user_agent = $ua;
        return $this;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @param bool $debug [optional]
     * @return $this
     */
    function debug($debug=true)
    {
        curl_setopt($this->ch, CURLOPT_VERBOSE, $debug);
        return $this;
    }

    /**
     * @param string $raw
     * @return $this
     */
    function setPostRaw($raw)
    {
        $this->post_raw = $raw;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    function setPostData($data)
    {
        $this->post_data = array_merge($this->post_data, $data);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    function setGetData($data)
    {

    }

    function setHeaders($data)
    {
        $this->headers = array_merge($this->headers, $data);

        return $this;
    }

    function setCookies($data)
    {
        $this->cookies = array_merge($this->cookies, $data);

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

        if (!empty($this->cookies)) {
            $data = [];
            foreach ($this->cookies as $k => $v) {
                $data[] = "$k=$v";
            }
            curl_setopt($this->ch, CURLOPT_COOKIE, implode('; ', $data));
        }

        foreach ($this->post_data as $name => $data) {
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

        if (!empty($this->headers)) {
            $data = [];
            foreach ($this->headers as $k => $v) {
                $data[] = "$k: $v";
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $BODY);

        return $this->getResponse();
    }

    /**
     * @return array
     */
    protected function getResponse()
    {
        $response = curl_exec($this->ch);
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        curl_close($this->ch);

        $headers = [];
        foreach (explode("\n", $header) as $line) {
            $line = explode(':', $line, 2);
            if (isset($line[1])) {
                list($key, $value) = $line;
                $value = ($value = trim($value)) && !empty($value) ? $value : null;
                if (isset($headers[$key]) && $headers[$key] != $value) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = [$headers[$key]];
                    }
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = $value;
                }
            }
        }

        $cookies = [];
        if (isset($headers['Set-Cookie'])) {
            foreach (is_array($headers['Set-Cookie']) ? $headers['Set-Cookie'] : [$headers['Set-Cookie']] as $cookie)
            {
                $cookie = explode('=', explode(';', $cookie, 2)[0]);
                if (isset($cookie[1])) {
                    $cookies[$cookie[0]] = $cookie[1];
                }
            }
        }

        $type = isset($headers['Content-Type']) ? is_array($headers['Content-Type']) ?
            $headers['Content-Type'][0] : $headers['Content-Type'] : 'text/plain';

        $json_data = !empty($content) && in_array($content{0}, ['{', '[']) ? json_decode($content, true) : false;

        return [
            'status' => $status,
            'type' => $type,
            'headers' => $headers,
            'cookies' => $cookies,
            'headers_raw' => $header,
            'content' => $content,
            'data' => $json_data,
        ];
    }

    function exec()
    {
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        if (!empty($this->cookies)) {
            $data = [];
            foreach ($this->cookies as $k => $v) {
                $data[] = "$k=$v";
            }
            curl_setopt($this->ch, CURLOPT_COOKIE, implode('; ', $data));
        }
        if (!empty($this->post_data)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            if ($this->isJson) {
                $data = json_encode($this->post_data);
            } else {
                $data = http_build_query($this->post_data);
            }
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
            $this->setHeaders(['Content-Length' => strlen($data)]);
        }
        if (!empty($this->headers)) {
            $data = [];
            foreach ($this->headers as $k => $v) {
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