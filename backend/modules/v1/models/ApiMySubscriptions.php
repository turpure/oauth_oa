<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-10-28
 * Time: 17:18
 * Author: henry
 */

/**
 * @name ApiMySubscriptions.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-10-28 17:18
 */


namespace backend\modules\v1\models;


use backend\models\EbayShopData;
use yii\data\ActiveDataProvider;

class ApiMySubscriptions
{

    /** 获取eBay订阅店铺listing列表
     * @param $condition
     * Date: 2019-10-29 8:58
     * Author: henry
     * @return array|ActiveDataProvider
     */
    public static function getApiMySubscriptionsList($condition)
    {
        try {
            $query = EbayShopData::find()
                ->andfilterWhere(['ItemID' => $condition['ItemID']])
                ->andfilterWhere(['like', 'CategoryName', $condition['CategoryName']])
                ->andfilterWhere(['like', 'Title', $condition['Title']])
                ->andfilterWhere(['like', 'SKU', $condition['SKU']])
                ->andfilterWhere(['like', 'Location', $condition['Location']])
                ->andfilterWhere(['like', 'Site', $condition['Site']])
                ->andfilterWhere(['like', 'StoreName', $condition['StoreName']])
                ->orderBy('QuantitySold DESC');
            $data = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
            return $data;
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

}