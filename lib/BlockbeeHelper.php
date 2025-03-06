<?php

namespace Blockbee\Blockbee\lib;

use Exception;

class BlockbeeHelper
{
    private static $base_url = "https://api.blockbee.io";
    private $bb_params = [];
    private $parameters = [];
    private $api_key = null;

    public function __construct($api_key, $parameters = [], $bb_params = [])
    {
        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $this->bb_params = $bb_params;
        $this->parameters = $parameters;
        $this->api_key = $api_key;
    }

    /**
     * Handles request to payments.
     * @return array
     */
    public function payment_request($redirect_url, $notify_url, $value)
    {
        if (empty($redirect_url) || empty($value)) {
            return null;
        }

        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $redirect_url   = "{$redirect_url}?{$req_parameters}";
            $notify_url   = "{$notify_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'redirect_url' => $redirect_url,
            'notify_url' => $notify_url,
            'apikey' => $this->api_key,
            'value' => $value
        ], $this->bb_params);

        return BlockbeeHelper::_request(null, 'checkout/request', $bb_params);
    }


    private static function _request($coin, $endpoint, $params = [], $assoc = false)
    {

        $base_url = BlockbeeHelper::$base_url;

        if (!empty($params)) {
            $data = http_build_query($params);
        }

        if (!empty($coin)) {
            $coin = str_replace('_', '/', $coin);
            $url = "{$base_url}/{$coin}/{$endpoint}/";
        } else {
            $url = "{$base_url}/{$endpoint}/";
        }

        if (!empty($data)) {
            $url .= "?{$data}";
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);

        $json = [];

        if (curl_error($curl)) {
            $json['error'] = 'ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);
            return $json;
        } elseif ($response) {
            return json_decode($response, $assoc);
        }
    }
}
