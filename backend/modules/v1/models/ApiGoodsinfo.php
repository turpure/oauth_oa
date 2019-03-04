<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 9:26
 * Author: henry
 */
/**
 * @name ApiGoodsinfo.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 9:26
 */


namespace backend\modules\v1\models;


use backend\models\OaGoodsinfo;
use yii\data\ActiveDataProvider;

class ApiGoodsinfo
{
    public static function getOaGoodsInfoList($condition)
    {
        $query = OaGoodsinfo::find();
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['achieveStatus'])) $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['AliasCnName'])) $query->andFilterWhere(['like', 'AliasCnName', $condition['AliasCnName']]);
        if(isset($condition['AliasEnName'])) $query->andFilterWhere(['like', 'AliasEnName', $condition['AliasEnName']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['stockUp' => $condition['stockUp']]);
        if(isset($condition['IsLiquid'])) $query->andFilterWhere(['IsLiquid' => $condition['IsLiquid']]);
        if(isset($condition['IsPowder'])) $query->andFilterWhere(['IsPowder' => $condition['IsPowder']]);
        if(isset($condition['isMagnetism'])) $query->andFilterWhere(['isMagnetism' => $condition['isMagnetism']]);
        if(isset($condition['IsCharged'])) $query->andFilterWhere(['IsCharged' => $condition['IsCharged']]);
        if(isset($condition['IsCharged'])) $query->andFilterWhere(['IsCharged' => $condition['IsCharged']]);
        $data = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 6,
            ],
        ]);
        return $data;
    }

}