<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-26 16:36
 */

namespace backend\modules\v1\utils;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Yii;


class ExportTools
{

    /**
     * @param $fileName
     * @param $data
     * @param $type
     * @param $title
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function toExcelOrCsv($fileName,$data, $type='Xls',$title=[])
    {
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        $fileName .= date('-YmdHis');
        $sheet = new Spreadsheet();
        $workSheet = $sheet->getActiveSheet();
        $cellName = $data ? array_keys($data[0]) : [];
        $len = count($cellName);

        //set title
        if(!empty($title)) {
            foreach ($title as $index => $value) {
                $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(2 * count($value));
            }
        }
        else {
            foreach ($cellName as $index => $value) {
                $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(count($value));
            }
        }

        // set cell value
        foreach ($data as $key => $row) {
            for ($index=0; $index<$len;$index++) {
                $workSheet->setCellValueByColumnAndRow($index + 1, $key + 2,  $row[$cellName[$index]]);
            }
        }

        //set header
        header('pragma:public');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'.'.$type.'"');
        header('Cache-Control: max-age=0');
        header('Access-Control-Expose-Headers: Content-Disposition');
        $writer = IOFactory::createWriter($sheet, $type);
        if((strtolower($type) === 'csv')) {
            $writer->setUseBOM(true);
        }
        $writer->save('php://output');
        exit;
    }

    /**   PhpSpreadsheet 保存xls文件
     * @param $fileName
     * @param $data
     * @param $type
     * @param $title
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function saveToExcelOrCsv($fileName,$data, $type='Xls',$title=[])
    {
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        $sheet = new Spreadsheet();
        $workSheet = $sheet->getActiveSheet();
        $cellName = $data ? array_keys($data[0]) : [];
        $len = count($cellName);

        //set title
        if(!empty($title)) {
            foreach ($title as $index => $value) {
                $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(2 * count($value));
            }
        }
        else {
            foreach ($cellName as $index => $value) {
                $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(count($value));
            }
        }
        // set cell value
        foreach ($data as $key => $row) {
            for ($index=0; $index<$len;$index++) {
                $workSheet->setCellValueByColumnAndRow($index + 1, $key + 2,  $row[$cellName[$index]]);
                if($row[$cellName[$index]] > 1000000){
//                    $value = html_entity_decode("&iuml;&raquo;&iquest;" . $value);
//                    $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                    $columnAndRowNum = strtoupper(chr(65 + $index)) . ($key + 2);
//                    var_dump($columnAndRowNum);exit;
                    $workSheet->getStyle($columnAndRowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                    $workSheet->setCellValueExplicit($columnAndRowNum, $row[$cellName[$index]], DataType::TYPE_STRING);
                }
            }
        }

        $filename = $fileName . '.' . $type;
        $filename = iconv("UTF-8","GBK//IGNORE", $filename);
        $writer = IOFactory::createWriter($sheet, $type);
        $writer->save($filename);
        return $filename;
    }


    /** PHP 原生方法保存CSV
     * @param $fileName
     * @param $data
     * @param array $title
     * Date: 2020-12-16 15:55
     * Author: henry
     */
    public static function saveToCsv($fileName, $data, $title = []){
        //生成临时文件
        $filename = $fileName . '.csv';
        $filename = iconv("UTF-8","GBK//IGNORE", $filename);
        $fp = fopen($filename, 'w');
        //Windows下使用BOM来标记文本文件的编码方式,否则输出的数据乱码
        fwrite($fp,chr(0xEF).chr(0xBB).chr(0xBF));

        if(!$title){
            foreach ($data[0] as $k => $val){
                $title[] = $k;
            }
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $title);
        foreach($data as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);  //每生成一个文件关闭
        return $filename;
    }

    /**
     * @param $fileName
     * @param $data = [['title'=>[],'name' => '', 'data'=>[]]]  title 字段名称 ，name sheet名称，data 导出数据
     * @param $type
     * @param $title
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function toExcelMultipleSheets($fileName, $data, $type='Xls')
    {
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        $fileName .= date('-YmdHis');
        $sheet = new Spreadsheet();
        foreach ($data as $k => $v){
            if($k == 0){
                $workSheet = $sheet->getActiveSheet()->setTitle($v['name']);
            }else{
                $workSheet = new Worksheet($sheet, $v['name']);
                $sheet->addSheet($workSheet);
            }

            $cellName = $v['data'] ? array_keys($v['data'][0]) : [];
            $len = count($cellName);

            //set title
            if(!empty($v['title'])) {
                foreach ($v['title'] as $index => $value) {
                    $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                    $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                    $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(2 * count($value));
                }
            }
            else {
                foreach ($cellName as $index => $value) {
                    $workSheet->setCellValueByColumnAndRow($index + 1, 1, $value);
                    $workSheet->getStyleByColumnAndRow($index + 1, 1)->getFont()->setBold(true);
                    $workSheet->getColumnDimensionByColumn($index + 1)->setAutoSize(count($value));
                }
            }

            // set cell value
            foreach ($v['data'] as $key => $row) {
                for ($index=0; $index<$len;$index++) {
                    $workSheet->setCellValueByColumnAndRow($index + 1, $key + 2,  $row[$cellName[$index]]);
                }
            }
        }


        //set header
        header('pragma:public');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'.'.$type.'"');
        header('Cache-Control: max-age=0');
        header('Access-Control-Expose-Headers: Content-Disposition');
        $writer = IOFactory::createWriter($sheet, $type);
        if((strtolower($type) === 'csv')) {
            $writer->setUseBOM(true);
        }
        $writer->save('php://output');
        exit;
    }



}
