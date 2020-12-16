<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-26 16:36
 */

namespace backend\modules\v1\utils;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

    /**
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
        //header('pragma:public');
        //header('Access-Control-Allow-Origin: *');
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="'.$fileName.'.'.$type.'"');
        //header('Cache-Control: max-age=0');
        //header('Access-Control-Expose-Headers: Content-Disposition');
        //$writer = IOFactory::createWriter($sheet, $type);

        $writer = new Xls($sheet);
        //$writer->save('1.xlsx');
//        $writer->save('php://output');

        $writer->save('C:\Users\Administrator\Downloads'.$fileName);
       exit;
    }

    public static function saveToExcel($fileName,$data, $type='Xls',$title=[]){
        //$file_name = mt_rand(9000, 10000) . iconv('utf-8', 'GBK',$file['name']);
        $savePath = '/uploads/zip/' . date("Ymd", time());
        $model_path = Yii::$app->basePath . '/uploads/zip';
        $path = Yii::$app->basePath . $savePath . '/';
        if (!file_exists($model_path)) mkdir($model_path, 0777);
        if (!file_exists($path)) mkdir($path, 0777);
        //$targetFile = str_replace('//', '/', $path) . $file_name;
        //if (!move_uploaded_file($file['tmp_name'], $targetFile)) return false;


        $elsFile = $path . $fileName . '.xls';
        $file = fopen($elsFile, 'w');
        fwrite($file, $data);
        fclose($file);
        return  $elsFile;
    }



}
