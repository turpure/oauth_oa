<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-27 14:15
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiSettings;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;

class SettingsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiSettings';

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'exchange' => ['get', 'post', 'options'],
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * @brief exchange rate
     * @return array|mixed
     * @throws
     */
    public function actionExchange()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $post = $request->post();
            $cond = $post['condition'];
            if (!$cond['devRate'] && !$cond['saleRate']) {
                return [
                    'code' => 400,
                    'message' => 'The salerRate and the devRate can not be empty at the same time！',
                    //'message' => '销售汇率和开发汇率不能同时为空！',
                ];
            }
            $condition = [
                'devRate' => $cond['devRate'],
                'salerRate' => $cond['saleRate'],
            ];
            $ret = ApiSettings::updateExchangeRate($condition);
            return $ret;
        }
        if ($request->isGet) {
            return ApiSettings::getExchangeRate();
        }
    }

    /**
     * @brief 销售死库费用
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionSalesDeadFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'deadfee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }
            //获取上传excel文件的内容并保存
//            $res = ApiUpload::getExcelData($result,ApiUpload::SALES, ApiUpload::DEAD_FEE);
//            if($res !== true) return ['code' => 400, 'message' => $res];
    }

    /**
     * @brief 开发死库费用
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionDevDeadFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'deadfee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::DEVELOP, ApiSettings::DEAD_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }

    /**
     * @brief 美工死库费用
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionPosDeadFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'deadfee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::POSSESS, ApiSettings::DEAD_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }

    /**
     * @brief 采购死库费用
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionPurDeadFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'deadfee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::PURCHASE, ApiSettings::DEAD_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }


    /**
     * @brief 销售运营杂费
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionSalesoperatefee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'opreatefee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::SALES, ApiSettings::OPERATE_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }

    /**
     * @brief 开发运营杂费
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionDevOperateFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'opreatefee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::DEVELOP, ApiSettings::OPERATE_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }

    /**
     * @brief 美工运营杂费
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionPosOperateFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'opreatefee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::POSSESS, ApiSettings::OPERATE_FEE);
            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }


    /**
     * @brief 采购运营杂费
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionPurOperateFee()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if($extension != '.xlsx') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' format"];

        //文件上传
        $result = ApiSettings::file($file, 'opreatefee');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            //获取上传excel文件的内容并保存
            $res = ApiSettings::getExcelData($result,ApiSettings::PURCHASE, ApiSettings::OPERATE_FEE);

            if($res !== true) return ['code' => 400, 'message' => $res];
        }
    }


}