<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-06-28
 * Time: 10:01
 */

namespace backend\modules\v1\models;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yii;

class ApiSettings
{
    const DEVELOP = '开发';
    const SALES = '销售';
    const POSSESS = '美工';
    const PURCHASE = '采购';

    const DEAD_FEE = '死库费用';
    const OPERATE_FEE = '运营杂费';

    /**
     * get the rate
     * @return array|false
     * @throws \yii\db\Exception
     */
    public static function getExchangeRate()
    {
        $sql = "SELECT * FROM Y_RateManagement";
        return Yii::$app->py_db->createCommand($sql)->queryOne();
    }

    /**
     * update rate
     * @param $condition
     * @return array
     * @throws \yii\db\Exception
     */
    public static function updateExchangeRate($condition)
    {
        $sql = "UPDATE Y_RateManagement SET salerRate='{$condition['salerRate']}',devRate='{$condition['devRate']}'";
        $res = Yii::$app->py_db->createCommand($sql)->execute();
        if ($res) {
            $result = true;
        } else {
            $result = [
                'code' => 400,
                'message' => 'Data update failed！',
            ];
        }
        return $result;
    }


    /**
     *
     * @param $condition
     * @return array
     * @throws \yii\db\Exception
     */
    public static function addDiebaseFeeFile($condition)
    {
        $sql = "UPDATE Y_RateManagement SET salerRate='{$condition['salerRate']}',devRate='{$condition['devRate']}'";
        $res = Yii::$app->py_db->createCommand($sql)->execute();
        if ($res) {
            $result = true;
        } else {
            $result = [
                'code' => 400,
                'message' => 'Data insert failed！',
            ];
        }

        return $result;
    }


    /**
     * 文件上传
     *
     * @param $file
     * @param string $model
     * @param array $thumb [['prefix'=>'l', 'width'=>'800', 'height'=>'600']]
     * @return bool|string
     */
    public static function file($file, $model = 'deadfee')
    {
        $file_name = time() . rand(1000, 9999) . self::get_extension($file['name']);
        $savePath = '/uploads/' . $model . '/' . date("Ymd", time());
        $model_path = Yii::$app->basePath . '/uploads/' . $model;
        $path = Yii::$app->basePath . $savePath . '/';
        //print_r($model_path);exit;

        if (!file_exists($model_path)) mkdir($model_path, 0777);
        if (!file_exists($path)) mkdir($path, 0777);
        $targetFile = str_replace('//', '/', $path) . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) return false;
        return $savePath . '/' . $file_name;
    }


    /**
     * 获取文件名后缀
     *
     * @param $filename
     * @return string
     */
    static function get_extension($filename)
    {
        $x = explode('.', $filename);
        return '.' . end($x);
    }


    /**
     * 获取excel内容并保存数据表
     * @param $file
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    static function getExcelData($file, $role = self::SALES, $type = self::DEAD_FEE)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        //$reader->setLoadSheetsOnly(["Sheet 1"]);
        $spreadsheet = $reader->load(Yii::$app->basePath . $file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        // print_r($highestRow);exit;
        try {
            for ($i = 2; $i <= $highestRow; $i++) {
                if ($type == self::DEAD_FEE) {
                    switch ($role) {
                        case self::SALES:


                            //销售死库
                            $data['plat'] = $sheet->getCell("A" . $i)->getValue();
                            $data['suffix'] = $sheet->getCell("B" . $i)->getValue();
                            $data['diefeeZn'] = $sheet->getCell("C" . $i)->getValue();

                            if (!$data['plat'] && !$data['suffix'] && !$data['diefeeZn']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("D" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['ClearanceDate'] = date("Y-m-d", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_offlineclearn WHERE suffix='$data[suffix]' AND  ClearanceDate='$data[ClearanceDate]' ";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();
                            if (!$res) {//插入
                                $sql = "insert into Y_offlineclearn (plat,suffix,diefeeZn,ClearanceDate) values ('$data[plat]','$data[suffix]',$data[diefeeZn],'$data[ClearanceDate]')";
                            } else {
                                $sql = "UPDATE Y_offlineclearn SET plat='$data[plat]',suffix='$data[suffix]',diefeeZn='$data[diefeeZn]',ClearanceDate='$data[ClearanceDate]' WHERE suffix='$data[suffix]' AND ClearanceDate='$data[ClearanceDate]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::DEVELOP:

                            //开发死库
                            $data['SalerName'] = $sheet->getCell("A" . $i)->getValue();
                            $data['SalerName2'] = $sheet->getCell("B" . $i)->getValue();
                            $data['TimeGroup'] = $sheet->getCell("C" . $i)->getValue();
                            $data['Amount'] = $sheet->getCell("D" . $i)->getValue();

                            if (!$data['SalerName'] && !$data['SalerName2'] && !$data['TimeGroup'] && !$data['Amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("E" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['devClearnTime'] = date('Y-m-d', $stamp_date);//这个就是excel表中的数据了，棒棒的！
                            $data['storename'] = $sheet->getCell("F" . $i)->getValue();

                            $sql_select = "select * from Y_devOfflineClearn WHERE SalerName='$data[SalerName]' AND  SalerName2='$data[SalerName2]' AND  devClearnTime='$data[devClearnTime]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryAll();
                            if (!$res) {//插入
                                $sql = "INSERT INTO Y_devOfflineClearn (SalerName,SalerName2,TimeGroup,Amount,devClearnTime) VALUES('$data[SalerName]','$data[SalerName2]','$data[TimeGroup]','$data[Amount]','$data[devClearnTime]')";
                            } else {//更新
                                $sql = "UPDATE Y_devOfflineClearn SET SalerName='$data[SalerName]',SalerName2='$data[SalerName2]',TimeGroup='$data[TimeGroup]',Amount='$data[Amount]', devClearnTime='$data[devClearnTime]'
                                        WHERE SalerName='$data[SalerName]' AND  SalerName2='$data[SalerName2]' AND  devClearnTime='$data[devClearnTime]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::POSSESS:

                            //美工死库
                            $data['Possess'] = $sheet->getCell("A" . $i)->getValue();
                            $data['TimeGroup'] = $sheet->getCell("B" . $i)->getValue();
                            $data['Amount'] = $sheet->getCell("C" . $i)->getValue();

                            if (!$data['Possess'] && !$data['TimeGroup'] && !$data['Amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("D" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['PossessClearnTime'] = date("Y-m-d ", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_PossessOfflineClearn WHERE Possess='$data[Possess]' AND  PossessClearnTime='$data[PossessClearnTime]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_PossessOfflineClearn (Possess,TimeGroup,Amount,PossessClearnTime) VALUES('$data[Possess]','$data[TimeGroup]','$data[Amount]','$data[PossessClearnTime]')";
                            } else {
                                $sql = "UPDATE Y_PossessOfflineClearn SET  Possess='$data[Possess]',TimeGroup='$data[TimeGroup]',Amount='$data[Amount]', PossessClearnTime='$data[PossessClearnTime]'
                        WHERE Possess='$data[Possess]' AND  PossessClearnTime='$data[PossessClearnTime]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::PURCHASE:


                            //采购死库
                            $data['purchaser'] = $sheet->getCell("A" . $i)->getValue();
                            $data['amount'] = $sheet->getCell("B" . $i)->getValue();

                            if (!$data['purchaser'] && !$data['amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("C" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['createdDate'] = date("Y-m-d ", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_purOfflineClearn WHERE purchaser='$data[purchaser]' AND  createdDate='$data[createdDate]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_purOfflineClearn (purchaser,amount,createdDate) VALUES('$data[purchaser]','$data[amount]','$data[createdDate]')";
                            } else {
                                $sql = "UPDATE Y_purOfflineClearn SET purchaser='$data[purchaser]',amount='$data[amount]', createdDate='$data[createdDate]'
                        WHERE purchaser='$data[purchaser]'  AND  createdDate='$data[createdDate]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                    }
                } else {
                    switch ($role) {
                        case self::SALES:


                            //销售
                            $data['plat'] = $sheet->getCell("A" . $i)->getValue();
                            $data['suffix'] = $sheet->getCell("B" . $i)->getValue();
                            $data['saleopefeezn'] = $sheet->getCell("C" . $i)->getValue();

                            if (!$data['plat'] && !$data['suffix'] && !$data['saleopefeezn']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("D" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['saleopetime'] = date("Y-m-d", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_saleOpeFee WHERE suffix='$data[suffix]' AND  saleopetime='$data[saleopetime]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_saleOpeFee (plat,suffix,saleopefeezn,saleopetime) VALUES('$data[plat]','$data[suffix]','$data[saleopefeezn]','$data[saleopetime]')";
                            } else {
                                $sql = "UPDATE Y_saleOpeFee SET plat='$data[plat]',suffix='$data[suffix]',saleopefeezn='$data[saleopefeezn]',saleopetime='$data[saleopetime]' WHERE suffix='$data[suffix]' AND saleopetime='$data[saleopetime]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::DEVELOP:

                            //开发
                            $data['SalerName'] = $sheet->getCell("A" . $i)->getValue();
                            $data['SalerName2'] = $sheet->getCell("B" . $i)->getValue();
                            $data['TimeGroup'] = $sheet->getCell("C" . $i)->getValue();
                            $data['Amount'] = $sheet->getCell("D" . $i)->getValue();

                            if (!$data['SalerName'] && !$data['SalerName2'] && !$data['TimeGroup'] && !$data['Amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("E" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['devOperateTime'] = date('Y-m-d', $stamp_date);//这个就是excel表中的数据了，棒棒的！


                            $sql_select = "select * from Y_devOperateFee WHERE SalerName='$data[SalerName]' AND  SalerName2='$data[SalerName2]' AND  devOperateTime='$data[devOperateTime]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_devOperateFee (SalerName,SalerName2,TimeGroup,Amount,devOperateTime) VALUES('$data[SalerName]','$data[SalerName2]','$data[TimeGroup]','$data[Amount]','$data[devOperateTime]')";
                                //不存在做插入
                            } else {
                                $sql = "UPDATE Y_devOperateFee SET SalerName='$data[SalerName]',SalerName2='$data[SalerName2]',TimeGroup='$data[TimeGroup]',Amount='$data[Amount]', devOperateTime='$data[devOperateTime]'
                                        WHERE SalerName='$data[SalerName]' AND  SalerName2='$data[SalerName2]' AND  devOperateTime='$data[devOperateTime]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::POSSESS:

                            //美工死库
                            $data['Possess'] = $sheet->getCell("A" . $i)->getValue();
                            $data['TimeGroup'] = $sheet->getCell("B" . $i)->getValue();
                            $data['Amount'] = $sheet->getCell("C" . $i)->getValue();

                            if (!$data['Possess'] && !$data['TimeGroup'] && !$data['Amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("D" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['PossessOperateTime'] = date("Y-m-d ", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_PossessOperateFee WHERE Possess='$data[Possess]' AND TimeGroup='$data[TimeGroup]' AND  PossessOperateTime='$data[PossessOperateTime]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_PossessOperateFee (Possess,TimeGroup,Amount,PossessOperateTime) VALUES('$data[Possess]','$data[TimeGroup]','$data[Amount]','$data[PossessOperateTime]')";
                                //不存在做插入
                            } else {
                                $sql = "UPDATE Y_PossessOperateFee SET Possess='$data[Possess]',TimeGroup='$data[TimeGroup]',Amount='$data[Amount]', PossessOperateTime='$data[PossessOperateTime]'
                        WHERE Possess='$data[Possess]' AND AND TimeGroup='$data[TimeGroup]' AND  PossessOperateTime='$data[PossessOperateTime]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                        case self::PURCHASE:


                            //采购
                            $data['purchaser'] = $sheet->getCell("A" . $i)->getValue();
                            $data['amount'] = $sheet->getCell("B" . $i)->getValue();

                            if (!$data['purchaser'] && !$data['amount']) break;//取到数据为空时跳出循环

                            $date = (string)$sheet->getCell("C" . $i)->getValue();
                            $stamp_date = Date::excelToTimestamp($date);//将获取的奇怪数字转成时间戳，该时间戳会自动带上当前日期
                            $data['createdDate'] = date("Y-m-d ", $stamp_date);//这个就是excel表中的数据了，棒棒的！

                            $sql_select = "select * from Y_purOperateFee WHERE purchaser='$data[purchaser]' AND  createdDate='$data[createdDate]'";
                            $res = Yii::$app->py_db->createCommand($sql_select)->queryOne();

                            if (!$res) {
                                $sql = "INSERT INTO Y_purOperateFee (purchaser,amount,createdDate) VALUES('$data[purchaser]','$data[amount]','$data[createdDate]')";
                                //不存在做插入
                            } else {
                                $sql = "UPDATE Y_purOperateFee SET purchaser='$data[purchaser]',amount='$data[amount]', createdDate='$data[createdDate]'
                                        WHERE purchaser='$data[purchaser]' AND  createdDate='$data[createdDate]'";
                            }
                            Yii::$app->py_db->createCommand($sql)->execute();
                            break;
                    }
                }
            }
            $res = true;
        } catch (\Exception $e) {
            $res = $e->getMessage();
        }

        return $res;
    }


}