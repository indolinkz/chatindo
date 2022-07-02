<?php

class tiktok
{
    public $enable_proxies = false;
    private $cookie_file = __DIR__ . "/../storage/tiktok-cookie.txt";

    public function get($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_COOKIEFILE => $this->cookie_file,
            CURLOPT_COOKIEJAR => $this->cookie_file,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $data;
    }

    public function get_redirect_url($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_COOKIEFILE => $this->cookie_file,
            CURLOPT_COOKIEJAR => $this->cookie_file,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $url;
    }

    private function download_video($url, $file_path)
    {
        $fp = fopen($file_path, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_REFERER, "https://www.tiktok.com/");
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    private function get_video_key($file_data)
    {
        $key = "";
        preg_match("/vid:([a-zA-Z0-9]+)/", $file_data, $matches);
        if (isset($matches[1])) {
            $key = $matches[1];
        }
        return $key;
    }

    public function media_info($url)
    {
        preg_match('/#\/@\w{2,32}\/video\/\d{2,32}/', $url, $matches);
        if (count($matches) === 1) {
            $url = "https://www.tiktok.com" . ltrim($matches[0], "#");
        }
        $host = str_replace("www.", "", parse_url($url, PHP_URL_HOST));
        if ($host != "tiktok.com") {
            $url = unshorten($url, $this->enable_proxies);
        }
        preg_match('/https:\/\/www\.tiktok\.com\/@(.*?)\/video\/([0-9]+)/', $url, $matches);
        if (count($matches) < 3) {
            return false;
        }
        $share_url = "https://www.tiktok.com/node/share/video/@" . $matches[1] . "/" . $matches[2];
        $share_data = $this->get($share_url);
        $share_data = json_decode($share_data, true);
        if (!isset($share_data["itemInfo"]["itemStruct"]["video"]) || empty($share_data["itemInfo"]["itemStruct"]["video"])) {
            return false;
        }
        $video["source"] = "tiktok";
        $video["title"] = $share_data["itemInfo"]["itemStruct"]["desc"];
        if (empty($video["title"])) {
            $video["title"] = $share_data["metaParams"]["title"];
        }
        $video["thumbnail"] = "https://www.tiktok.com/api/img/?itemId=" . $matches[2] . "&location=0";
        $video["duration"] = format_seconds($share_data["itemInfo"]["itemStruct"]["video"]["duration"]);
        $video["links"] = array();
        $i = 0;
        if (!empty($share_data["itemInfo"]["itemStruct"]["video"]["downloadAddr"])) {
            $track_id = rand(0, 4);
            $cache_file = __DIR__ . "/../storage/temp/tiktok-" . $track_id . ".mp4";
            $website_url = json_decode(option("general_settings"), true)["url"];
            $this->download_video($share_data["itemInfo"]["itemStruct"]["video"]["downloadAddr"], $cache_file);
            $video_key = $this->get_video_key(file_get_contents($cache_file));
            $video["links"][$i]["url"] = $website_url . "/system/storage/temp/tiktok-" . $track_id . ".mp4";
            $video["links"][$i]["type"] = "mp4";
            $video["links"][$i]["quality"] = $share_data["itemInfo"]["itemStruct"]["video"]["ratio"];
            $video["links"][$i]["bytes"] = filesize($cache_file);
            $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
            $video["links"][$i]["mute"] = false;
            $i++;
            if (!empty($video_key)) {
                $nwm_video = "https://api2-16-h2.musical.ly/aweme/v1/play/?video_id=$video_key&vr_type=0&is_play_url=1&source=PackSourceEnum_PUBLISH&media_type=4";
                $nwm_video = $this->get_redirect_url($nwm_video);
                if (filter_var($nwm_video, FILTER_VALIDATE_URL)) {
                    $video["links"][$i]["url"] = $nwm_video;
                    $video["links"][$i]["type"] = "mp4";
                    $video["links"][$i]["quality"] = $video["links"][$i - 1]["quality"];
                    $video["links"][$i]["bytes"] = get_file_size($nwm_video, $this->enable_proxies, false);
                    $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
                    $video["links"][$i]["mute"] = false;
                    $i++;
                }
            }
        }
        if (!empty($share_data["itemInfo"]["itemStruct"]["music"]["playUrl"])) {
            $video["links"][$i]["url"] = $share_data["itemInfo"]["itemStruct"]["music"]["playUrl"];
            $video["links"][$i]["type"] = "mp3";
            $video["links"][$i]["quality"] = "128 kbps";
            $video["links"][$i]["bytes"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies, false);
            $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
            $video["links"][$i]["mute"] = false;
            $i++;
        }
        return $video;
    }
}