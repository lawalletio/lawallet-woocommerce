<?php

class LaWallet {

    protected $federationEndpoint;

    // constructor
    public function __construct($federationEndpoint="https://api.lawallet.ar") {
        if (!$federationEndpoint) {
            throw new Exception("Federation ID is required");
        }
        $this->federationEndpoint = $federationEndpoint;
    }

    public function fetchByFilter($filter) {
        $data = json_encode((object) $filter);

        // Create the context for the request
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'=> "Content-type: application/json\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data
            ]
        ];
        $context  = stream_context_create($options);

        // The URL to which you want to send the POST request
        $url = "$this->federationEndpoint/nostr/fetch/";

        // Make the request
        $result = file_get_contents($url, false, $context);
        return json_decode($result);
    }
}