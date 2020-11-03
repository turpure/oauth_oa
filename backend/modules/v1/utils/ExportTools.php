<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-26 16:36
 */

namespace backend\modules\v1\utils;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


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

}
