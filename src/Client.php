<?php

namespace nymedia\SuperOffice;

use nymedia\SuperOffice\resources\Contact;
use nymedia\SuperOffice\resources\Person;
use nymedia\SuperOffice\resources\Project;
use nymedia\SuperOffice\resources\ProjectMember;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Client {

  /**
   * @var null|\GuzzleHttp\Client
   */
  protected $client;

  protected $url;

  protected $password;

  protected $user;

  protected $env;

  protected $accessToken;

  public function getClient()
  {
    if (!$this->client) {
      $this->client = new \GuzzleHttp\Client();
    }
    return $this->client;
  }

  public function __construct($url, $user = null, $password = null, $client = null)
  {
    $this->url = $url;
    $this->client = $client;
    $this->user = $user;
    $this->password = $password;
    $this->env = 'online';
    if (strpos($this->url, 'sod.superoffice.com')) {
      $this->env = 'sod';
    }
    if (strpos($this->url, 'qaonline.superoffice.com')) {
      $this->env = 'qaonline';
    }
  }

  public function getAccessToken($refresh_token, $client_id, $client_secret)
  {
    $url = sprintf('https://%s.superoffice.com/login/common/oauth/tokens?grant_type=refresh_token&client_id=%s&client_secret=%s&refresh_token=%s', $this->env, $client_id, $client_secret, $refresh_token);
    $response = $this->client->post($url);
    $data = (string) $response->getBody();
    if (empty($data)) {
      throw new BadRequestHttpException('No data in get access token response');
    }
    $json = @json_decode($data);
    if (empty($json)) {
      throw new BadRequestHttpException('Bad JSON in get access token response');
    }
    if (empty($json->access_token)) {
      throw new BadRequestHttpException('The JSON in access token response did not contain an access token');
    }
    // Probably we want to use it directly after.
    $this->accessToken = $json->access_token;
    return $this->accessToken;
  }

  public function projectMember()
  {
    return new ProjectMember($this);
  }

  public function person()
  {
    return new Person($this);
  }

  public function contact()
  {
    return new Contact($this);
  }

  public function project()
  {
    return new Project($this);
  }

  public function get($path, $data = null)
  {
    return $this->apiCall('GET', $this->url . '/' . $path, $data);
  }

  public function post($path, $data)
  {
    return $this->apiCall('POST', $this->url . '/' . $path, $data);
  }

  public function put($path, $data)
  {
    return $this->apiCall('PUT', $this->url . '/' . $path, $data);
  }

  protected function apiCall($method, $path, $data = null)
  {
    $opts = [
      'headers' => [
        'User-Agent' => 'Superoffice PHP SDK (https://github.com/nymedia/superoffice-php-sdk)',
        'Accept' => 'application/json',
      ],
    ];
    if ($this->user && $this->password) {
      $opts['auth'] = [
        $this->user,
        $this->password,
      ];
    }
    if ($this->accessToken) {
      $opts['headers']['Authorization'] = 'Bearer ' . $this->accessToken;
    }
    if ($data && $method != 'GET') {
      // Set all needed options with this shorthand.
      $opts['json'] = $data;
    }
    elseif ($data) {
      $opts['query'] = $data;
    }

    return $this->getClient()->request($method, $path, $opts);
  }
}
