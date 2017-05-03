<?php
/**
 * Slince yark
 * @author Tao <taosikai@sina.cn>
 */
namespace Slince\Yark;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class CrawlCommand extends Command
{
    /**
     * @var Client
     */
    protected $httpClient;

    public function configure()
    {
        $this->setName('crawl');
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->httpClient = new Client([
            'verify' => false,
            'proxy' => '40.74.112.196:80',
            'timeout' => 60,
            'connection_timeout' => 60
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $urlTemplate = "https://myip.ms/ajax_table/sites/{page}/ipID/23.227.38.0/ipIDii/23.227.38.255/sort/2/asc/1";
        $rows = [];
        for ($i = 1; $i <= 606; $i++)  {
            $url = str_replace('{page}', $i, $urlTemplate);
            $rows = array_merge($rows, $this->processUrl($url));
        }
    }

    protected function processUrl($url)
    {
//        $url = 'http://www.ip.cn';
        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Cookie' => 's2_uLang=en; PHPSESSID=vafdirlk9ot1pkog7954ilrbp1; s2_uID=30212; s2_uKey=00928125d7ea46883e238bb97cf3c28ff3750f4b; s2_uDer=f9e5131966edfb57505448b597fa5bde53f64107; s2_theme_ui=red; s2_csrf_cookie_name=cb3f55822249d75f73bab6af191c137f; __unam=737437c-15b7ee8e5b8-6a5e10fa-8; s2_csrf_cookie_name=cb3f55822249d75f73bab6af191c137f; _ga=GA1.2.787444202.1492482844; sw=190.3; sh=39.9',
                    'Referer' => 'https://myip.ms/browse/sites/1/ipID/23.227.38.0/ipIDii/23.227.38.255/sort/2/asc/1/',
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'form_params' => [
                    'getpage' => 'yes',
                    'lang' => 'en'
                ]
            ]);
        } catch (\Exception $exception) {
            print_r($exception->getMessage());
            exit;
        }
        $crawler = new Crawler((string)$response->getBody());
        var_dump((string)$response->getBody());exit;
        $rows = $crawler->filter('tr')->each(function(Crawler $node, $i){
            return [
                'domain' => $node->filter('td.row_name')->text(),
                'ip' => $node->filter('td:nth-child(3)')->text()
            ];
        });
        print_r($rows);
        exit;
        return $rows;
    }
}