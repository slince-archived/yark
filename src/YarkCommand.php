<?php
/**
 * Slince yark
 * @author Tao <taosikai@sina.cn>
 */
namespace Slince\Yark;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class YarkCommand extends Command 
{
    /**
     * 事件名
     * @var string
     */
    const NAME = 'yark';

    public function configure()
    {
        $this->setName(static::NAME)
            ->addArgument('price-file', InputArgument::OPTIONAL, "Price Excel file", getcwd() . '/pricelist.xlsx')
            ->addArgument('output-format', InputArgument::OPTIONAL, 'Output excel file format',  'Excel2007')
            ->addArgument('output-path', InputArgument::OPTIONAL, 'Output excel file path', getcwd() . '/dst');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $priceFile = $input->getArgument('price-file');
        $outputFormat = $input->getArgument('output-format');
        $outputPath = $input->getArgument('output-path');

        $finder = new Finder();
        $finder->files()->in(getcwd())->name('*.xlsx')->notName('*' . basename($priceFile))->depth('== 0');
        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }
        $progressBar = new ProgressBar($output, count($files));
        $yark = new Yark($files, $priceFile, $outputPath, $outputFormat);
        $yark->getDispatcher()->bind(Yark::EVENT_COMPLETE_HANDLE, function () use ($progressBar){
            $progressBar->advance(1);
        });
        $yark->handle();
        $output->writeln('');
    }
}