<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:15
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiMine;
use backend\modules\v1\models\ApiOverseas;
use backend\modules\v1\models\ApiWarehouseTools;
use backend\modules\v1\utils\AttributeInfoTools;
use Codeception\Template\Api;
use Yii;
use yii\data\ArrayDataProvider;

class OverseasController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiOverseas';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }

    //==================================海外仓 调拨单=============================================

    /**
     * @brief 分拣人
     * @return array
     */
    public function actionMember()
    {
        return ApiWarehouseTools::getWarehouseMember('all');
    }

    /**
     * 调拨单列表
     * Date: 2021-03-31 18:00
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStockChangeList()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $pageSize = $condition['pageSize'] ?? 20;
            $data = ApiOverseas::getStockChangeList($condition);
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['MakeDate', 'Billnumber', 'Memo', 'StoreOutName', 'StoreInName', 'Recorder',
                        'checkflag', 'Audier', 'AudieDate', 'StoreInMan', 'StoreOutMan', 'FinancialMan', 'FinancialTime',
                        'PackPersonFee', 'PackMaterialFee', 'HeadFreight', 'Tariff', 'TotalAmount', 'TotalMoney', 'TotalinMoney',
                        'logicsWayName', 'expressName', 'logicsWayNumber', 'RealWeight', 'ThrowWeight', 'Archive'],
                    'defaultOrder' => [
                        'MakeDate' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 查询调拨SKU信息
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionSkuStockInfo()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiOverseas::getSkuStockInfo($condition);
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * 创建/编辑调拨单
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionSaveStockChange()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $stockChangeNID = ApiOverseas::saveStockChange($condition);
            var_dump($stockChangeNID);
            return ApiOverseas::updateStockChangeInPrice($stockChangeNID);
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取调拨单详情
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionGetStockChange()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiOverseas::getStockChange($condition);
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * 审核调拨单
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionCheckStockChange()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiOverseas::checkStockChange($condition);
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }


}
