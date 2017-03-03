<?php
/**
 * Slince yark
 * @author Tao <taosikai@sina.cn>
 */
namespace Slince\Yark;

use Slince\Event\Dispatcher;
use Slince\Event\Event;
use Symfony\Component\Filesystem\Filesystem;

class Yark
{
    /**
     * 原始文件
     * @var array
     */
    protected $srcFiles = [];

    /**
     * @var string
     */
    protected $priceFile;

    /**
     * 报价
     * [
     *     'sku' => [
     *          'AU' => '12',
     *          'UK' => '12',
     *          'US' => '12',
     *     ]
     * ]
     * @var array
     */
    protected $prices;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * 输出目录
     * @var string
     */
    protected $outputPath;

    /**
     * @var \PHPExcel_Writer_IWriter
     */
    protected $outputFormat;

    /**
     * 开始处理
     * @var string
     */
    const EVENT_BEGIN_HANDLE = 'beginHandle';

    /**
     * 处理完毕
     * @var string
     */
    const EVENT_COMPLETE_HANDLE = 'completeHandle';

    public function __construct(array $srcFiles, $priceFile, $outputPath = './', $outputFormat = 'Excel2007')
    {
        $this->srcFiles = $srcFiles;
        $this->priceFile = $priceFile;
        $this->outputPath = $outputPath;
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($outputPath);
        $this->outputFormat = $outputFormat;
        $this->dispatcher = new Dispatcher();
    }

    /**
     * 开始处理
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        if (!is_file($this->priceFile)) {
            throw new \Exception(sprintf("Price File [%s] is invalid", $this->priceFile));
        }
        $this->prices = $this->handlePriceExcel($this->readExcelFile($this->priceFile));
        foreach ($this->srcFiles as $srcFile) {
            $this->handleSrcFile($srcFile);
        }
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * 处理原文件
     * @param $srcFile
     */
    protected function handleSrcFile($srcFile)
    {
        $srcExcel = $this->readExcelFile($srcFile);
        //调度事件
        $this->dispatcher->dispatch(static::EVENT_BEGIN_HANDLE, new Event(static::EVENT_BEGIN_HANDLE, $this, [
            'srcFile' => $srcFile
        ]));
        $sheets = $srcExcel->getAllSheets();
        foreach ($sheets as $sheet) {
            $orderItems = $this->handleSrcSheet($sheet);
            foreach ($orderItems as $key => $orderItem) {
                $orderItem['Lineitem sku'] = trim($orderItem['Lineitem sku']);
                $mainSku = strpos($orderItem['Lineitem sku'], '-') === false ? $orderItem['Lineitem sku'] : strstr($orderItem['Lineitem sku'], '-', true);
                $shippingCountry = strtoupper($orderItem['Shipping Country']);
                if (!empty($this->prices[$mainSku][$shippingCountry])) {
                    $total = $this->prices[$mainSku][$shippingCountry] * intval($orderItem['Lineitem quantity']);
                } else {
                    $total = '###';
                }
                $orderItem['Shipping Total Price'] = $total;
                $orderItems[$key] = $orderItem;
            }
            $outputRecords[] = array_keys($orderItem); //第一行是标题
            foreach ($orderItems as $orderItem) {
                $outputRecords[] = array_values($orderItem);
            }
            $sheet->fromArray($outputRecords);
        }
        $writer = \PHPExcel_IOFactory::createWriter($srcExcel, $this->outputFormat);
        $writer->save($this->outputPath .  '/' . basename($srcFile));
        //调度事件
        $this->dispatcher->dispatch(static::EVENT_COMPLETE_HANDLE, new Event(static::EVENT_COMPLETE_HANDLE, $this, [
            'srcFile' => $srcFile
        ]));
    }

    /**
     * 处理src sheet下的字段
     * @param \PHPExcel_Worksheet $sheet
     * @return array
     */
    protected function handleSrcSheet(\PHPExcel_Worksheet $sheet)
    {
        $rawOrderItems = $sheet->toArray();
        $fields = array_shift($rawOrderItems);
        $orderItems = [];
        foreach ($rawOrderItems as $orderItem) {
            $orderItems[] = array_combine($fields, $orderItem);
        }
        return $orderItems;
    }

    /**
     * 处理报价excel
     * @param \PHPExcel $excel
     * @return array
     */
    protected function handlePriceExcel(\PHPExcel $excel)
    {
        $sheets = $excel->getAllSheets();
        $prices = [];
        foreach ($sheets as $sheet) {
            $sku = $sheet->getTitle();
            $prices[$sku] = $this->handlePriceSheet($sheet);
        }
        return $prices;
    }

    /**
     * 处理sheet内价格数据
     * @param \PHPExcel_Worksheet $sheet
     * @return array
     */
    protected function handlePriceSheet(\PHPExcel_Worksheet $sheet)
    {
        $rawPrices = $sheet->toArray();
        $prices = [];
        foreach ($rawPrices as $price) {
            $prices[strtoupper($price[0])] = $price[1];
        }
        return $prices;
    }

    /**
     * @param $file
     * @return \PHPExcel
     */
    protected function readExcelFile($file)
    {
        return \PHPExcel_IOFactory::load($file);
    }
}