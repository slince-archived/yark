<?php
/**
 * Slince yark
 * @author Tao <taosikai@sina.cn>
 */
namespace Slince\Yark;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\ProgressBar;

class RepairCommand extends Command
{
    protected $address = [];

    /**
     * 输出目录
     * @var string
     */
    protected $outputPath = './dst';

    protected $outputFormat = 'Excel2007';

    public function configure()
    {
        $this->setName('fix');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()->in(getcwd())->name('*.xlsx')->depth('== 0');
        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }
        $progressBar = new ProgressBar($output, count($files));
        foreach ($files as $file) {
            $this->processExcel($file);
            $progressBar->advance(1);
        }
        $output->writeln('OK');
    }

    protected function processExcel($srcFile)
    {
        $excel = \PHPExcel_IOFactory::load($srcFile);
        $sheets = $excel->getAllSheets();
        foreach ($sheets as $sheet) {
            $orderItems = $this->processSheet($sheet);
            foreach ($orderItems as $key => $orderItem) {
                $orderItem['Name'] = trim($orderItem['Name']);
                if (empty($orderItem['Shipping Name']) && isset($this->address[$orderItem['Name']])) {
                    $targetItem = $this->address[$orderItem['Name']];
                    $orderItem['Shipping Name'] = $targetItem['Shipping Name'];
                    $orderItem['Shipping Street'] = $targetItem['Shipping Street'];
                    $orderItem['Shipping Address1'] = $targetItem['Shipping Address1'];
                    $orderItem['Shipping Address2'] = $targetItem['Shipping Address2'];
                    $orderItem['Shipping City'] = $targetItem['Shipping City'];
                    $orderItem['Shipping Zip'] = $targetItem['Shipping Zip'];
                    $orderItem['Shipping Province'] = $targetItem['Shipping Province'];
                    $orderItem['Shipping Country'] = $targetItem['Shipping Country'];
                    $orderItem['Shipping Phone'] = $targetItem['Shipping Phone'];
                }
                $orderItems[$key] = $orderItem;
            }
            $outputRecords[] = array_keys($orderItem); //第一行是标题
            foreach ($orderItems as $orderItem) {
                $outputRecords[] = array_values($orderItem);
            }
            $sheet->fromArray($outputRecords);
        }
        $writer = \PHPExcel_IOFactory::createWriter($excel, $this->outputFormat);
        $writer->save($this->outputPath .  '/' . basename($srcFile));
    }

    protected function processSheet(\PHPExcel_Worksheet $sheet)
    {
        $rawOrderItems = $sheet->toArray();
        $fields = array_shift($rawOrderItems);
        $orderItems = [];
        foreach ($rawOrderItems as $orderItem) {
            $orderItem = array_combine($fields, $orderItem);
            $orderItems[] = $orderItem;
            if (!empty($orderItem['Shipping Name']) && !isset($this->address[$orderItem['Name']])) {
                $this->address[$orderItem['Name']] = $orderItem;
            }
        }
        return $orderItems;
    }
}