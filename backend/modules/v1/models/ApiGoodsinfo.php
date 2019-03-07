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


use backend\models\OaGoods;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use yii\data\ActiveDataProvider;

class ApiGoodsinfo
{
    /**
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    const GoodsInfo = 1;
    const PictureInfo = 2;
    const PlatInfo = 3;

    public static function getOaGoodsInfoList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $currentPage = isset($condition['currentPage']) ? $condition['currentPage'] : 1;
        $type= $condition['type'];
        $query = OaGoodsinfo::find();
        if ($type === 'goods-info') {
            $query->where(['filterType' => self::GoodsInfo]);
        }
        elseif ($type === 'picture-info')
        {
            $query->where(['filterType' => self::PictureInfo]);
        }
        elseif ($type === 'plat-info') {
            $query->where(['filterType' => self::PlatInfo]);
        }
        else {
            return [];
        }
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
        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
                'Page' => $currentPage -1
            ],
        ]);
        return $provider;
    }

    /**
     * @brief get one attribute entry by id
     * @param $condition
     * @return mixed
     */
    public static function getAttributeById($condition)
    {
        $id = isset($condition['id'])? $condition['id']: '';
        if(empty($id)){
            return [];
        }
        return OaGoodsinfo::find()->with('oaGoods')->where(['id'=>$id])->asArray()->one();
    }

    public static function deleteAttributeById($id)
    {
        $ret = OaGoodsinfo::deleteAll(['id'=>$id]);
        if ($ret) {
            return ['success'];
        }
        return ['failure'];
    }

    public static function getAttributeInfo($condition)
    {
        $id = isset($condition['id'])? $condition['id']: '';
        if(empty($id)) {
            return [];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$id]);
        if($goodsInfo === null) {
            return [];
        }
        $oaGoods = OaGoods::find()
            ->select('nid,cate,subCate,vendor1,vendor2,vendor3,origin1,origin2,origin3')
            ->where(['nid'=>$goodsInfo->goodsid])->one();
        if ($oaGoods === null) {
            $oaGoods = [
                'nid' => $goodsInfo->goodsid,
                'cate' => '',
                'subCate' => '',
                'vendor1' => '',
                'vendor2' => '',
                'vendor3' => '',
                'origin1' => '',
                'origin2' => '',
                'origin3' => '',
            ];
        }
        $skuInfo = OaGoodsSku::findAll(['infoId'=>$id]);
        return [
            'basicInfo' => [
                'goodsInfo' => $goodsInfo,
                'oaGoods' => $oaGoods,
            ],
            'skuInfo' => $skuInfo
        ];
    }

    public static function importToShopElf($condition)
    {
        $id = isset($condition['id'])? $condition['id']:'';
        if(empty($id)) {
            return [];
        }
        return ['success'];
    }

    /**
     * @param $condition
     * @return array
     * @throws \Throwable
     */
    public static function finishAttribute($condition)
    {
        $id = isset($condition['id'])? $condition['id']:'';
        if(empty($id)) {
            return ['failure'];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$id]);
        if($goodsInfo === null) {
            return ['failure'];
        }
        //属性信息标记完善，图片信息为待处理
        try {
            $goodsInfo->achieveStatus = '已完善';
            if(empty($goodsInfo->picStatus)) {
                $goodsInfo->picStatus = '待处理';
            }
            if ($goodsInfo->update()) {
                return ['success'];
            }
        }
        catch (\Exception  $why) {
           return ['failure'];
        }
        return ['failure'];
    }

    /**
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function saveAttribute($condition)
    {
        $attributeInfo = $condition['basicInfo']['goodsInfo'];
        $oaInfo = $condition['basicInfo']['oaGoods'];
        $skuInfo = $condition['skuInfo'];
        $infoId = $attributeInfo['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$infoId]);
        if($goodsInfo === null) {
            return ['failure'];
        }
        foreach ($skuInfo as $skuRow) {
            $skuId = isset($skuRow['id']) ? $skuRow['id']: '';
            $skuModel = OaGoodsSku::findOne(['id'=>$skuId]);
            if($skuModel === null) {
                $skuModel = new OaGoodsSku();
                $skuRow['id'] = $skuModel->id;
                $skuRow['pid'] = $infoId;
            }
            $skuModel->setAttributes($skuRow);
            $skuModel->save();
        }

        $oaGoods = OaGoods::findOne(['nid'=>$oaInfo['nid']]);
        if ($oaGoods === null) {
            $oaGoods =  new OaGoods();
            $oaGoods->nid = $oaInfo['nid'];
        }
        $oaGoods->setAttributes($oaInfo);
        $goodsInfo->setAttributes($attributeInfo);
        if( $goodsInfo->save() && $oaGoods->save()) {
                return ['success'];
        }
    }

}