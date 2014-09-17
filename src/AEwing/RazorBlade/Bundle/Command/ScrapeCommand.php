<?php
namespace AEwing\RazorBlade\Bundle\Command;
use AEwing\RazorBlade\Bundle\DependencyInjection\RazorBladeExtension;
use AEwing\RazorBlade\RazorBlade;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('razor:scrape')
      ->addArgument('url', InputArgument::REQUIRED)
      ->addOption('--fields', '-f', InputOption::VALUE_OPTIONAL)
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $url = $input->getArgument('url');

    /** @var $fields The fields to search for */
    $fields = $input->getOption('fields');
    if(!is_null($fields)) { $fields = explode(',', $fields); } else { $fields = []; }
    $fields = $this->_convertFields($fields);

    /*$client_class = $this->getContainer()->getParameter('http.client');
    if(!class_exists($client_class)) {
      throw new Exception(sprintf("Invalid HTTP Client class name provided: %s", $client_class));
    }*/

    $scraper = new RazorBlade();
    $scraped = [];

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
    $url = $protocol . '://' . $url;

    $output->writeln(sprintf('Scraping %s', $url));

    /** Scrape a list of state links */
    $states = $scraper->scrape($url, function($uri, $crawler, &$data) {
      $data['links'] = $crawler->filter('area[shape=poly]')->each(function($node) {
        return $node->attr('href');
      });
      return [];
    });

    /** Scrape individual state pages */
    foreach($states['links'] as $link) {
      if(substr($link, 0, 1) == '/') {
        $link = $protocol . '://' . $tld . $link;
      }
      if(!stristr($link, '://')) {
        $link = 'http://' . $link;
      }
      $parts = explode('#', $link);
      $link = array_shift($parts);

      // We have a clean URL
      $tscraper = new RazorBlade();
      $rows = $tscraper->scrape($link, function($uri, $crawler, &$data) {
        $data = $crawler->filter('table#skateparklist tr')->each(function($node) {
          if(trim($node->attr('class')) == 'headerrow') {
            $data = $node->filter('td')->each(
              function ($td) {
                return $td->text();
              }
            );
            $data['_type'] = 'header';
          } else {
            $data = $node->filter('td')->each(
              function ($td) {
                return $td->text();
              }
            );
            $data['_type'] = 'row';
          }
          return $data;
        });
        return [];
      });
      $scraped[$link] = $rows;
    }
    var_dump($scraped);
  }
  private function _convertFields(array $fields) {
    $converted = [];
    foreach($fields as $field) {
      if(stristr($field, ':')) {
        $parts = explode(':', $field);
        $name = array_shift($parts);
        $converted[$name] = $parts;
      } else {
        $converted[$field] = true;
      }
    }
    return $converted;
  }
}