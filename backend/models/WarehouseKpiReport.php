<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for collection "warehouse_kpi_report".
 *
 * @property mixed $id
 * @property mixed $name
 * @property mixed $dt
 * @property mixed $job
 * @property mixed $index
 * @property mixed $isShopeeVerified
 * @property mixed $likedCountEnd
 * @property mixed $likedCountStart
 * @property mixed $merchant
 * @property mixed $merchantStatus
 * @property mixed $orderColumn
 * @property mixed $pageSize
 * @property mixed $paymentEnd
 * @property mixed $paymentStart
 * @property mixed $paymentThreeDay1End
 * @property mixed $paymentThreeDay1Start
 * @property mixed $pid
 * @property mixed $pidOrTitle
 * @property mixed $pidStatus
 * @property mixed $priceEnd
 * @property mixed $priceStart
 * @property mixed $ratingCountEnd
 * @property mixed $ratingCountStart
 * @property mixed $ratingEnd
 * @property mixed $ratingStart
 * @property mixed $sort
 * @property mixed $salesThreeDay1End
 * @property mixed $salesThreeDay1Start
 * @property mixed $salesThreeDayGrowthEnd
 * @property mixed $salesThreeDayGrowthStart
 * @property mixed $shopLocation
 * @property mixed $shopLocationStatus
 * @property mixed $soldEnd
 * @property mixed $soldStart
 * @property mixed $title
 * @property mixed $titleStatus
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 * @property mixed $ruleName
 * @property mixed $ruleMark
 * @property mixed $ruleType
 * @property mixed $listedTime
 *
 */
class WarehouseKpiReport extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return 'warehouse_kpi_report';
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            'name', 'dt', 'job', 'pur_in_package_num', 'marking_stock_order_num', 'marking_sku_num',
            'labeling_sku_num', 'labeling_goods_num', 'labeling_order_num',
            'pda_in_storage_sku_num', 'pda_in_storage_goods_num', 'pda_in_storage_location_num',
            'single_sku_num', 'single_goods_num', 'single_location_num', 'single_order_num',
            'multi_sku_num', 'multi_goods_num', 'multi_location_num', 'multi_order_num',
            'pack_single_order_num', 'pack_single_goods_num', 'pack_multi_order_num', 'pack_multi_goods_num',
            'integral', 'update_date'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'dt', 'job'], 'string'],
            [['pur_in_package_num', 'marking_stock_order_num', 'marking_sku_num',
                'labeling_sku_num', 'labeling_goods_num', 'labeling_order_num',
                'pda_in_storage_sku_num', 'pda_in_storage_goods_num', 'pda_in_storage_location_num',
                'single_sku_num', 'single_goods_num', 'single_location_num', 'single_order_num',
                'multi_sku_num', 'multi_goods_num', 'multi_location_num', 'multi_order_num',
                'pack_single_order_num', 'pack_single_goods_num', 'pack_multi_order_num', 'pack_multi_goods_num',
                'integral'], 'integer'],
            [['update_date'], 'safe']
        ];
    }

}
