<?php

namespace Drutiny\BlazeMeter;

use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Output\OutputInterface;
use Drutiny\Http\Client as HttpClient;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

class Client {

  /**
   * API base URL for Blazemeter.
   */
  const API_BASE = 'https://a.blazemeter.com/api/v4/masters/';

  /**
   * API key for authentication.
   */
  protected $key;

  /**
   * API Secrets for authentication.
   */
  protected $secret;

  /**
   * API constructor.
   */
  public function __construct($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  /**
   * Perform an API request to Blazemeter.
   *
   * @param string $method
   *   The HTTP method to use.
   * @param string $endpoint
   *   The API endpoint to hit. The endpoint is prefixed with the API_BASE.
   * @param array $payload
   *
   * @param bool $decodeBody
   *   Whether the body should be JSON decoded.
   * @return array|string
   *   Decoded JSON body of the API request, if the request was successful.
   *
   * @throws \Exception
   */
  public function request($method = 'GET', $endpoint, $payload = [], $decodeBody = TRUE) {
    $url = '';
    $time = 0;

    $client = new HttpClient([
      'base_uri' => self::API_BASE . $this->account_id . '/',
      'headers' => [
        'Authorization' => 'Basic ' . $this->key . $this->secret,
      ],
    ]);

    if (!empty($payload)) {
      $response = $client->request($method, $endpoint, [
        RequestOptions::JSON => $payload,
      ]);
    }
    else {
      $response = $client->request($method, $endpoint);
    }

    if (!in_array($response->getStatusCode(), [200, 204])) {
      throw new \Exception('Error: ' . (string) $response->getBody());
    }

    if ($decodeBody) {
      return json_decode($response->getBody(), TRUE);
    }
    return (string) $response->getBody();
  }

}
