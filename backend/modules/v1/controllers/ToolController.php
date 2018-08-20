<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiEbayTool;
use backend\modules\v1\models\ApiSmtTool;
use backend\modules\v1\models\ApiTool;
use backend\modules\v1\models\ApiWishTool;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
class ToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTool';

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'account' => ['get','post','options'],
                    'site' => ['get','post','options'],
                    'size' => ['get','post','options'],
                    'color' => ['get','post','options'],
                    'ebay-template' => ['post','options'],
                    'ebaysku' => ['post','options'],
                    'ebaysku-template' => ['post','options'],
                    'smtsku' => ['post','options'],
                    'smtsku-template' => ['post','options'],
                    'wishsku' => ['post','options'],
                    'wishsku-template' => ['post','options'],
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * 获取对应平台账号
     * @return mixed
     */
    public function actionAccount(){
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'type' => $cond['type'],
        ];
        $ret = ApiTool::getAccount($condition);
        return $ret;
    }

    /**
     * 获取eBay平台站点
     * @return mixed
     */
    public function actionSite(){
        return ApiTool::getSite();
    }

    /**
     * 下载eBay账号商品模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionEbayTemplate(){
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'suffix' => $cond['suffix'],
            'goodsCode' => $cond['goodsCode'],
        ];
        ApiTool::handelInfo($condition);
    }

    /**
     * 获取eBay账号商品SKU列表
     * @return mixed
     */
     public function actionEbaysku(){
         $request = Yii::$app->request->post();
         $cond= $request['condition'];
         $condition= [
             'suffix' => $cond['suffix'],
             'goodsCode' => $cond['goodsCode'],
             'Site' => $cond['Site'],
             'Cat1' => $cond['Cat1'],
             'Cat2' => $cond['Cat2'],
             'price' => $cond['price'],
             'shipping1' => $cond['shipping1'],
             'shipping2' => $cond['shipping2'],
         ];
         return ApiEbayTool::getEbaySkuList($condition);
     }

    /**
     * 下载eBay账号商品SKU模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
     public function actionEbayskuTemplate(){
         $request = Yii::$app->request->post();
         $condition= $request['condition'];
         ApiEbayTool::handelInfo($condition);
     }

    /**
     * 获取商品码号
     * @return mixed
     */
    public function actionSize(){
         return ApiSmtTool::getSize();
    }

    /**
     * 获取商品颜色
     * @return mixed
     */
    public function actionColor(){
        return ApiSmtTool::getColor();
    }

     /**
     * 获取SMT账号商品SKU列表
     * @return mixed
     */
     public function actionSmtsku(){
         $request = Yii::$app->request->post();
         $cond= $request['condition'];
         $condition= [
             'suffix' => $cond['suffix'],
             'goodsCode' => $cond['goodsCode'],
             'price' => $cond['price'],
         ];
         return ApiSmtTool::getSmtSkuList($condition);
     }

    /**
     * 下载SMT账号商品SKU模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
     public function actionSmtskuTemplate(){
         $request = Yii::$app->request->post();
         $condition= $request['condition'];
         ApiSmtTool::handelInfo($condition);
     }



    /**
     * 获取Wish账号商品SKU列表
     * @return mixed
     */
    public function actionWishsku(){
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'suffix' => $cond['suffix'],
            'goodsCode' => $cond['goodsCode'],
            'price' => $cond['price'],//售价
            'msrp' => $cond['msrp'],//保留价
            'shipping' => $cond['shipping'],//运费
        ];
        return ApiWishTool::getWishSkuList($condition);
    }
    /**
     * 下载Wish账号商品SKU模板
     * @return mixed
     */
    public function actionWishskuTemplate(){
        $request = Yii::$app->request->post();
        $condition= $request['condition'];
        ApiWishTool::handelInfo($condition);
    }








}