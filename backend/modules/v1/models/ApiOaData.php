<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-06
 * Time: 10:53
 * Author: henry
 */
/**
 * @name ApiOaData.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-06 10:53
 */


namespace backend\modules\v1\models;



use backend\models\OaGoodsinfo;
use yii\data\ActiveDataProvider;

class ApiOaData
{

    public static function getOaData($condition)
    {

        $query = OaGoodsinfo::find()->join('LEFT JOIN','proCenter.oa_goods g','g.nid=goodsid');
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['achieveStatus'])) $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['like', 'stockUp', $condition['stockUp']]);
        if(isset($condition['isVar'])) $query->andFilterWhere(['like', 'isVar', $condition['isVar']]);
        if(isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if(isset($condition['wishpublish'])) $query->andFilterWhere(['like', 'wishpublish', $condition['wishpublish']]);
        if(isset($condition['SupplierName'])) $query->andFilterWhere(['like', 'SupplierName', $condition['SupplierName']]);
        if(isset($condition['completeStatus'])) $query->andFilterWhere(['like', 'completeStatus', $condition['completeStatus']]);
        if(isset($condition['StoreName'])) $query->andFilterWhere(['like', 'StoreName', $condition['StoreName']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['Purchaser'])) $query->andFilterWhere(['like', 'Purchaser', $condition['Purchaser']]);
        if(isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);
        $query->orderBy('id DESC');
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

}