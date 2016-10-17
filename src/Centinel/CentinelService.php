<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Centinel;

use StephenLake\Iveri\Centinel\CentinelClient;
use StephenLake\Iveri\Centinel\Util\Array2XML;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;

class CentinelService {

    private $payload;

    public function buildPayload($data) {
        $xml      = Array2XML::createXML('CardinalMPI', $data)->saveXML();
        $this->payload = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml);

        return $this->payload;
    }

    public function submit($url) {
        $client = new HttpClient();

        $httpRequest = new HttpRequest('POST', "{$url}?cmpi_msg={$this->payload}");
        $httpResponse = $client->send($httpRequest);

        return Array2XML::XML_TO_ARR($httpResponse->getBody()->getContents());
    }
}
