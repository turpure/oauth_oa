<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-24
 * Time: 11:52
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiPerform;
use backend\modules\v1\utils\ExportTools;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;

class PerformController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiPerform';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


    public function behaviors()
    {
        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'perform' => ['post', 'options'],
                    'sales' => ['post', 'options'],
                    'cost' => ['post', 'options'],
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;
    }

    /**
     * 新品开发表现
     * @return mixed
     */
    public function actionPerform()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        //日期为空的话不能显示表了 可以前端验证 required
        if (empty($cond['beginDate']) || empty($cond['endDate'])) {
            return [
                'code' => 400,
                'message' => 'BeginDate and EndDate can not be empty！'
            ];
        }
        $condition = [
            'beginDate' => $cond['beginDate'],
            'endDate' => $cond['endDate'],
            'createBeginDate' => $cond['createBeginDate'],
            'createEndDate' => $cond['createEndDate'],
        ];
        $ret = ApiPerform::getNewProductDevelopmentPerformance($condition);
        return $ret;
    }


    /**
     * 销售变化表
     * @return array|string
     */
    public  function actionSales()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        $condition = [
            'suffix' => $cond['suffix'],
            'plat' => $cond['plat'],
            'salerName' => $cond['saler'],
            'page' => Yii::$app->request->get('page',1),
            'pageSize' => $cond['pageSize'],
        ];
        $ret = ApiPerform::getSalesChange($condition);
        return $ret;
    }

    /** 销售变化表
     * Date: 2020-08-03 15:59
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public  function actionSalesExport()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        $condition = [
            'suffix' => $cond['suffix'],
            'plat' => $cond['plat'],
            'salerName' => $cond['saler'],
            'page' => Yii::$app->request->get('page',1),
            'pageSize' => 10000,
        ];
        $ret = ApiPerform::getSalesChange($condition);
        $name = 'sale-changes';
        $title = ['商品编码','商品名称','商品状态','类目','归属1','归属2','创建日期','近1天销量','上1天销量','1天销量变化',
            '近5天销量','上5天销量','5天销量变化','近10天销量','上10天销量','10天销量变化'];
        $data = $ret->getModels();
        ExportTools::toExcelOrCsv($name, $data, 'Xls', $title);
    }

    /**
     * 获取物流公司
     * @return array|string
     */
    public function actionLogistics()
    {
        $list = Yii::$app->py_db->createCommand("SELECT * FROM T_Express WHERE used=0 ORDER BY code")->queryAll();
        return ArrayHelper::map($list,'Name', 'Name');
    }

    /**
     * 平台物流费用
     * @return array|string
     */
    public function actionCost()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        //日期为空的话不能显示表了 可以前端验证 required
        if (empty($cond['beginDate']) || empty($cond['endDate'])) {
            return [
                'code' => 400,
                'message' => 'BeginDate and EndDate can not be empty！'
            ];
        }
        $condition = [
            'beginDate' => $cond['beginDate'],
            'endDate' => $cond['endDate'],
            'wlCompany' => $cond['wlCompany'],
        ];
        $ret = ApiPerform::getLogisticsCost($condition);
        return $ret;
    }




}
