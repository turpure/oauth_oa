<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:15
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiMine;
use backend\modules\v1\models\ApiOverseas;
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
    public function actionSkuStockInfo(){


    }

    /**
     * 创建调拨单
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionCreateStockChange(){


    }

    /**
     * 更新调拨单
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionUpdateStockChange(){


    }

    /**
     * 作废调拨单
     * Date: 2021-04-22 18:00
     * Author: henry
     * @return array | bool
     */
    public function actionDeleteStockChange(){


    }





}
