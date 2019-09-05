<?php

namespace mdlutz24\mflconnector;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

/**
 * Provides a connector to the MFL api.
 */
class Connector implements ConnectorInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var string
   */
  protected $host;

  /**
   * @var string
   */
  protected $scheme;

  /**
   * @var string
   */
  protected $year;

  /**
   * @var string
   */
  protected $leagueId;

  /**
   * @var string
   */
  protected $authCookie;

  /**
   * Constructs a Connector object.
   *
   * @param string $league_id
   * @param string $host
   * @param string $year
   * @param string $scheme
   */
  public function __construct($league_id, $host = '', $year = '', $scheme = 'https') {
    if (!$host) {
      $host = static::getHostFromLeagueId($league_id);
    }
    if (!$year) {
      $year = date('Y');
    }

    $this->client = new Client();
    $this->leagueId = $league_id;
    $this->host = $host;
    $this->year = $year;
    $this->scheme = $scheme;
  }

  /**
   * Gets the value of $Host.
   *
   * @return string
   */
  public function getHost(): string {
    return $this->host;
  }

  /**
   * Sets the value of $host.
   *
   * @param string $host
   *
   * @return Connector
   */
  public function setHost(string $host): Connector {
    $this->host = $host;
    return $this;
  }

  /**
   * Gets the value of $Scheme.
   *
   * @return string
   */
  public function getScheme(): string {
    return $this->scheme;
  }

  /**
   * Sets the value of $scheme.
   *
   * @param string $scheme
   *
   * @return Connector
   */
  public function setScheme(string $scheme): Connector {
    $this->scheme = $scheme;
    return $this;
  }

  /**
   * Gets the value of $Year.
   *
   * @return string
   */
  public function getYear(): string {
    return $this->year;
  }

  /**
   * Sets the value of $year.
   *
   * @param string $year
   *
   * @return Connector
   */
  public function setYear(string $year): Connector {
    $this->year = $year;
    return $this;
  }

  /**
   * Gets the value of $LeagueId.
   *
   * @return string
   */
  public function getLeagueId(): string {
    return $this->leagueId;
  }

  /**
   * Sets the value of $leagueId.
   *
   * @param string $leagueId
   *
   * @return Connector
   */
  public function setLeagueId(string $leagueId): Connector {
    $this->leagueId = $leagueId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function makeCall($command = 'export', $args = []) {
    $uri = static::getUri($this->scheme, $this->host, $this->year, $command);
    $options = [RequestOptions::QUERY => $args + [static::JSON => '1']];
    if (!empty($this->auth_cookie)){
      $options[RequestOptions::COOKIES] = CookieJar::fromArray([static::AUTH_COOKIE => $this->auth_cookie], $uri->getHost());
    }
    $response = $this->client->get($uri, $options);
    if ($response->getStatusCode() === 200) {
      return \GuzzleHttp\json_decode($response->getBody());
    }
    return FALSE;
  }

  public function export($type, array $args) {
    return $this->makeCall('export', $args + [static::TYPE => $type]);
  }

  public function import($type, array $args) {
    return $this->makeCall('import', $args + [static::TYPE => $type]);
  }

  public function getPlayers($since = 0, array $players = [], bool $details = FALSE) {
    $args = [];
    if ($since) {
      $args[static::SINCE] = $since;
    }
    if ($players) {
      $args[static::PLAYERS] = explode(',', $players);
    }
    if ($details) {
      $args[static::DETAILS] = '1';
    }
    return $this->export('players', $args);
  }


  /**
   * @param string $scheme
   * @param string $host
   * @param string $year
   * @param string $command
   * @param array $args
   * @param string $auth_cookie
   *
   * @return object|bool
   */
  public static function makeStaticCall($scheme, $host, $year, $command, array $args = [], $auth_cookie = '') {
    $uri = static::getUri($scheme, $host, $year, $command);
    return static::makeStaticCallFromUri($uri, $args, $auth_cookie);

  }

  /**
   * @param \Psr\Http\Message\UriInterface $uri
   * @param array $args
   * @param string $auth_cookie
   *
   * @return object|bool
   *   a json_decoded object representation of the output.
   */
  public static function makeStaticCallFromUri(UriInterface $uri, array $args = [], $auth_cookie = '') {
    $config = [
      'base_uri' => $uri,
      RequestOptions::QUERY => $args,
    ];
    if (!empty($auth_cookie)){
      $config[RequestOptions::COOKIES] = CookieJar::fromArray([static::AUTH_COOKIE => $auth_cookie], $uri->getHost());
    }
    $client = new Client($config);
    $response = $client->get();
    if ($response->getStatusCode() === 200)  {
      return \GuzzleHttp\json_decode($response->getBody());
    }
    return FALSE;
  }

  /**
   * Gets a base Uri from the scheme, host, year and command.
   *
   * @param string $scheme
   * @param string $host
   * @param string $year
   * @param string $command
   *
   * @return \Psr\Http\Message\UriInterface
   */
  public static function getUri($scheme, $host, $year, $command) {
    return (new Uri())
      ->withScheme($scheme)
      ->withHost($host)
      ->withPath(sprintf("%s/%s", $year, $command));
  }

  /**
   * @param string $league_id
   * @param string $year
   *
   * @return string|bool
   *   The host for this league Id, or False if not found.
   */
  public static function getHostFromLeagueId(string $league_id, string $year = '') {
    if (!$year) {
      $year = date('Y');
    }
    $host = FALSE;
    $client = new Client();
    $uri = (new Uri())
      ->withScheme('http')
      ->withHost('api.myfantasyleague.com')
      ->withPath(sprintf("%s/export", $year))
      ->withQuery(sprintf("TYPE=league&L=%s&JSON=1", $league_id));
    $response =  $client->get($uri);
    if ($response->getStatusCode() !== 200) {
      return FALSE;
    }
    $data = \GuzzleHttp\json_decode($response->getBody());
    if ($data && $data->league) {
      $baseURL = $data->league->baseURL ?? '';
      $host = explode("://", $baseURL)[1] ?? FALSE;
    }
    return $host;
  }

}
