<?php

class tiktok
{
    public $enable_proxies = false;
    private $cookie_file = __DIR__ . "/../storage/tiktok-cookie.txt";

    public function http($url, $method = 'POST')
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://snaptik.app/action-2021.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => 'url='.urlencode($url),
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_HTTPHEADER => array(
                'authority: snaptik.app',
                'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'accept: */*',
                'origin: https://snaptik.app',
                'sec-fetch-site: same-origin',
                'sec-fetch-mode: cors',
                'sec-fetch-dest: empty',
                'referer: https://snaptik.app/tr',
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function media_info($url)
    {
        $data = $this->http($url);
        $video["source"] = "tiktok";
        $video["title"] = get_string_between($data, "<p><span>", "</span></p>");
        if (empty($video["title"])) {
            return false;
        }
        $video["thumbnail"] = get_string_between($data, '<img src="', '"');
        preg_match_all('/\'click_download_mp4_sv\d\'\);" href=\'(.*?)\'/', $data, $matches);
        if (empty($matches)) {
            return false;
        }
        $i = (int)(count($matches[1]) / 2) - 1;
        if (!isset($matches[1][$i])) {
            return false;
        }
        $video["links"] = array();
        $size = get_file_size($matches[1][$i], $this->enable_proxies, false);
        array_push($video["links"], [
            "url" => $matches[1][$i],
            "quality" => "HD",
            "type" => "mp4",
            "bytes" => $size,
            "size" => format_size($size),
            "mute" => false
        ]);
        return $video;
    }
}