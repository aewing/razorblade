<?php
namespace AEwing\RazorBlade;
class RazorBlade {
  protected $client;
  protected $queue = [];
  protected $data = [];

  public function __construct() {
    $this->client = new \Goutte\Client();
  }

  /**
   * Get the Goutte Client interface
   * @return Goutte\Client
   */
  public function client()
  {
    return $this->client;
  }

  /**
   * @param $url The URL to scrape
   * @return mixed The scraped content
   */
  public function scrape($url, $callback, $method='GET', $params=[]) {
    $following = [$url];
    $seen = [];
    $data = [];

    /** Analyze URL */
    if(stristr($url, '://')) {
      $parts = explode('://', $url);
      list($protocol, $url) = $parts;
    } else {
      $protocol = 'http';
    }
    $parts = explode('/', $url);
    $tld = array_shift($parts);
    $path = implode('/', $parts);

    while(!empty($following)) {
      $uri = array_shift($following);
      echo sprintf("\t%s\n", $uri);
      $seen[] = $uri;
      $crawler = $this->client()->request('GET', $uri, $params);
      $links = call_user_func_array($callback, [$uri, $crawler, &$data]);
      /** Sanitize and make sense of links */
      foreach($links as $link) {
        if(substr($link, 0, 1) == '/') {
          $link = $protocol . '://' . $tld . $link;
        }
        if(!stristr($link, '://')) {
          $link = 'http://' . $link;
        }
        $parts = explode('#', $link);
        $link = array_shift($parts);
        if(!in_array($link, $seen)) {
          $following[] = $link;
        }
      }
    }
    return $data;
  }
}