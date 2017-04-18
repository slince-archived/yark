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
            'verify' => false
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
        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
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