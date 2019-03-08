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

use yii\data\ActiveDataProvider;
use yii\db\Query;

class ApiOaData
{

    /**
     * @param $condition
     * Date: 2019-03-07 16:51
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaData($condition, $param = null)
    {
        //$query = OaGoodsinfo::find()
        $query = (new Query())
            ->select('gi.*,g.cate,g.subCate')
            ->from('proCenter.oa_goodsinfo gi')
            ->leftJoin('proCenter.oa_goods g','g.nid=goodsid');
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if(isset($condition['StoreName'])) $query->andFilterWhere(['like', 'StoreName', $condition['StoreName']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['like', 'stockUp', $condition['stockUp']]);
        if(isset($condition['wishpublish'])) $query->andFilterWhere(['like', 'wishpublish', $condition['wishpublish']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if(isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if(isset($condition['SupplierName'])) $query->andFilterWhere(['like', 'SupplierName', $condition['SupplierName']]);
        if(isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['Purchaser'])) $query->andFilterWhere(['like', 'Purchaser', $condition['Purchaser']]);
        if(isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);
        if(isset($condition['completeStatus'])) $query->andFilterWhere(['like', 'completeStatus', $condition['completeStatus']]);
        if(isset($condition['DictionaryName'])) $query->andFilterWhere(['like', 'DictionaryName', $condition['DictionaryName']]);
        if(isset($condition['isVar'])) $query->andFilterWhere(['like', 'isVar', $condition['isVar']]);
        if(isset($condition['goodsstatus'])) $query->andFilterWhere(['like', 'goodsstatus', $condition['goodsstatus']]);
        if(isset($condition['devDatetime']) && $condition['devDatetime']) $query->andFilterWhere(['between', 'devDatetime', $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if(isset($condition['updateDate']) && $condition['updateDate']) $query->andFilterWhere(['between', 'updateDate', $condition['updateDate'][0], $condition['updateDate'][1]]);
        //判断推广状态
        if(isset($condition['extendStatus'])) $query->andFilterWhere(['like', 'extendStatus', $condition['extendStatus']]);//TODO
        //判断是否为采集数据
        if(isset($condition['mid'])){
            if($condition['mid'] == '是'){
                $query->andFilterWhere(['>', 'mid', 0]);
            }else {
                $query->andFilterWhere(["IFNULL(mid,0)" => 0]);
            }
        }
        //备货天数
        if(isset($condition['stockdays'])) $query->andFilterWhere(['stockdays' => $condition['stockdays']]);
        //库存
        if(isset($condition['number'])) $query->andFilterWhere(['number' => $condition['number']]);

        //产品中心模块，去掉未完成的数据
        if ($param == 'product') {
            //print_r($param);exit;
            $query->andWhere(['<>', "IFNULL(completeStatus,'')", '']);
        }
        //Wish待刊登模块，只返回wish平台未完善数据
        if ($param == 'wish') {
            $query->andFilterWhere(['wishpublish' => 'Y']);
            $query->andFilterWhere(['not like', "IFNULL(DictionaryName,'')", 'wish']);
            $query->andFilterWhere(['not like', "IFNULL(completeStatus,'')", 'Wish已完善']);
        }
        
        $query->orderBy('id DESC');
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /**
     * @param $condition
     * Date: 2019-03-08 9:00
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaSalesData($condition)
    {


    }

}