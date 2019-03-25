<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-25
 * Time: 13:24
 * Author: henry
 */
/**
 * @name ApiBasicInfo.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-25 13:24
 */


namespace backend\modules\v1\models;


use backend\models\OaEbaySuffix;
use backend\models\OaJoomSuffix;
use backend\models\OaShippingService;
use backend\models\OaWishSuffix;
use yii\data\ActiveDataProvider;

class ApiBasicInfo
{
    ##############################   ebay suffix   ###############################
    /** get ebay suffix list
     * @param $condition
     * Date: 2019-03-25 14:16
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getEbaySuffixList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaEbaySuffix::find();
        if(isset($condition['ebayName'])) $query->andFilterWhere(['like', 'ebayName', $condition['ebayName']]);
        if(isset($condition['ebaySuffix'])) $query->andFilterWhere(['like', 'ebaySuffix', $condition['ebaySuffix']]);
        if(isset($condition['nameCode'])) $query->andFilterWhere(['like', 'nameCode', $condition['nameCode']]);
        if(isset($condition['mainImg'])) $query->andFilterWhere(['like', 'mainImg', $condition['mainImg']]);
        if(isset($condition['ibayTemplate'])) $query->andFilterWhere(['like', 'ibayTemplate', $condition['ibayTemplate']]);
        if(isset($condition['storeCountry'])) $query->andFilterWhere(['like', 'storeCountry', $condition['storeCountry']]);
        if(isset($condition['high'])) $query->andFilterWhere(['like', 'high', $condition['high']]);
        if(isset($condition['low'])) $query->andFilterWhere(['like', 'low', $condition['low']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);
        return $dataProvider;
    }

    /**
     * @param $condition
     * Date: 2019-03-25 14:30
     * Author: henry
     * @return array|OaEbaySuffix
     */
    public static function createEbaySuffix($condition)
    {
        $model = new OaEbaySuffix();
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * @param $condition
     * Date: 2019-03-25 14:40
     * Author: henry
     * @return array|bool|null|static
     */
    public static function updateEbaySuffix($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaEbaySuffix::findOne($id);
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    ##############################   wish suffix   ###############################

    /** get wish suffix list
     * @param $condition
     * Date: 2019-03-25 15:20
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getWishSuffixList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaWishSuffix::find();
        if(isset($condition['ibaySuffix'])) $query->andFilterWhere(['like', 'ibaySuffix', $condition['ibaySuffix']]);
        if(isset($condition['shortName'])) $query->andFilterWhere(['like', 'shortName', $condition['shortName']]);
        if(isset($condition['suffix'])) $query->andFilterWhere(['like', 'suffix', $condition['suffix']]);
        if(isset($condition['mainImg'])) $query->andFilterWhere(['like', 'mainImg', $condition['mainImg']]);
        if(isset($condition['parentCategory'])) $query->andFilterWhere(['like', 'parentCategory', $condition['parentCategory']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);
        return $dataProvider;
    }

    /**
     * @param $condition
     * Date: 2019-03-25 15:40
     * Author: henry
     * @return array|OaEbaySuffix
     */
    public static function createWishSuffix($condition)
    {
        $model = new OaWishSuffix();
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * @param $condition
     * Date: 2019-03-25 14:46
     * Author: henry
     * @return array|bool|null|static
     */
    public static function updateWishSuffix($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaWishSuffix::findOne($id);
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }



    ##############################   joom suffix   ###############################

    /** get joom suffix list
     * @param $condition
     * Date: 2019-03-25 16:16
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getJoomSuffixList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaJoomSuffix::find();
        if(isset($condition['joomName'])) $query->andFilterWhere(['like', 'joomName', $condition['joomName']]);
        if(isset($condition['joomSuffix'])) $query->andFilterWhere(['like', 'joomSuffix', $condition['joomSuffix']]);
        if(isset($condition['imgCode'])) $query->andFilterWhere(['like', 'imgCode', $condition['imgCode']]);
        if(isset($condition['mainImg'])) $query->andFilterWhere(['like', 'mainImg', $condition['mainImg']]);
        if(isset($condition['skuCode'])) $query->andFilterWhere(['like', 'skuCode', $condition['skuCode']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);
        return $dataProvider;
    }

    /**
     * @param $condition
     * Date: 2019-03-25 16:38
     * Author: henry
     * @return array|OaEbaySuffix
     */
    public static function createJoomSuffix($condition)
    {
        $model = new OaJoomSuffix();
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * @param $condition
     * Date: 2019-03-25 16:41
     * Author: henry
     * @return array|bool|null|static
     */
    public static function updateJoomSuffix($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaJoomSuffix::findOne($id);
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    ##############################   shipping service   ###############################

    /** get joom suffix list
     * @param $condition
     * Date: 2019-03-25 16:56
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getShippingServiceList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaShippingService::find();
        if(isset($condition['joomName'])) $query->andFilterWhere(['like', 'joomName', $condition['joomName']]);
        if(isset($condition['joomSuffix'])) $query->andFilterWhere(['like', 'joomSuffix', $condition['joomSuffix']]);
        if(isset($condition['imgCode'])) $query->andFilterWhere(['like', 'imgCode', $condition['imgCode']]);
        if(isset($condition['mainImg'])) $query->andFilterWhere(['like', 'mainImg', $condition['mainImg']]);
        if(isset($condition['skuCode'])) $query->andFilterWhere(['like', 'skuCode', $condition['skuCode']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);
        return $dataProvider;
    }

    /**
     * @param $condition
     * Date: 2019-03-25 17:02
     * Author: henry
     * @return array|OaEbaySuffix
     */
    public static function createShippingService($condition)
    {
        $model = new OaShippingService();
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * @param $condition
     * Date: 2019-03-25 17:11
     * Author: henry
     * @return array|bool|null|static
     */
    public static function updateShippingService($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaShippingService::findOne($id);
        $model->attributes = $condition;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }
}