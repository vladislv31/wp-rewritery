<?php

if (!function_exists('add_action')) die('Hhmmm.....');

class API
{
    private $token;
    private $url;

    public function __construct($token) {
        $this->token = $token;
        $this->url = 'https://api.dofiltra.com/api/';
    }

    public function getBalance() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url.'balance/get?token='.$this->token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $output = curl_exec($ch);
        $json = json_decode($output, true);

        curl_close($ch);

        return $json['coins'];
    }

    public function getStatistics() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url.'stats/getRewritedCharsCount?token='.$this->token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $output = curl_exec($ch);
        $json = json_decode($output, true);

        curl_close($ch);

        return $json['history'];
    }

    public function addRewrite($blocks) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url.'rewriteText/add');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'token' => $this->token,
            'targetLang' => 'RU',
            'dataset' => 0,
            'power' => 0.5,
            'blocks' => $blocks
        ], true));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json'
        ));

        $output = curl_exec($ch);
        $json = json_decode($output, true);

        curl_close($ch);

        return $json;
    }

    public function getRewrite($id) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url.'rewriteText/get?id='.$id.'&token='.$this->token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $output = curl_exec($ch);
        $json = json_decode($output, true);

        curl_close($ch);

        return $json;
    }
}