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
use backend\models\OaJoomToWish;
use backend\models\OaShippingService;
use backend\models\OaSysRules;
use backend\models\OaWishSuffix;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;

class ApiBasicInfo
{
    ##############################   ebay suffix   ###############################
    /** get ebay suffix list
     * @param $condition
     * Date: 2019-03-25 14:16
     * Author: henry
     * @return ArrayDataProvider
     */
    public static function getEbaySuffixList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = (new Query())->select('es.id id,ebayName,ebaySuffix,nameCode,mainImg,ibayTemplate,storeCountry,h.paypal high,l.paypal low')
            ->from('proCenter.oa_ebaySuffix es')
            ->leftJoin('proCenter.oa_paypal h','es.high=h.id')
            ->leftJoin('proCenter.oa_paypal l','es.low=l.id');
        if(isset($condition['ebayName'])) $query->andFilterWhere(['like', 'ebayName', $condition['ebayName']]);
        if(isset($condition['ebaySuffix'])) $query->andFilterWhere(['like', 'ebaySuffix', $condition['ebaySuffix']]);
        if(isset($condition['nameCode'])) $query->andFilterWhere(['like', 'nameCode', $condition['nameCode']]);
        if(isset($condition['mainImg'])) $query->andFilterWhere(['like', 'mainImg', $condition['mainImg']]);
        if(isset($condition['ibayTemplate'])) $query->andFilterWhere(['like', 'ibayTemplate', $condition['ibayTemplate']]);
        if(isset($condition['storeCountry'])) $query->andFilterWhere(['like', 'storeCountry', $condition['storeCountry']]);
        if(isset($condition['high'])) $query->andFilterWhere(['like', 'h.paypal', $condition['high']]);
        if(isset($condition['low'])) $query->andFilterWhere(['like', 'l.paypal', $condition['low']]);
        $dataProvider = new ArrayDataProvider([
            'allModels' => $query->orderBy('ebaySuffix')->all(),
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
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
     * @return array|OaWishSuffix
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
     * @return array|OaJoomSuffix
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

    /** get shipping service list
     * @param $condition
     * Date: 2019-03-25 16:56
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getShippingServiceList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaShippingService::find();
        if(isset($condition['servicesName'])) $query->andFilterWhere(['like', 'servicesName', $condition['servicesName']]);
        if(isset($condition['type'])) $query->andFilterWhere(['like', 'type', $condition['type']]);
        if(isset($condition['site'])) $query->andFilterWhere(['like', 'site', $condition['site']]);
        if(isset($condition['ibayShipping'])) $query->andFilterWhere(['like', 'ibayShipping', $condition['ibayShipping']]);
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
     * @return array|OaShippingService
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

    ##############################   sys rules  ###############################

    /** get sys rules list
     * @param $condition
     * Date: 2019-03-25 16:56
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getSysRulesList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaSysRules::find();
        if(isset($condition['ruleName'])) $query->andFilterWhere(['like', 'ruleName', $condition['ruleName']]);
        if(isset($condition['ruleKey'])) $query->andFilterWhere(['like', 'ruleKey', $condition['ruleKey']]);
        if(isset($condition['ruleValue'])) $query->andFilterWhere(['like', 'ruleValue', $condition['ruleValue']]);
        if(isset($condition['ruleType'])) $query->andFilterWhere(['like', 'ruleType', $condition['ruleType']]);
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
     * @return array|OaSysRules
     */
    public static function createSysRules($condition)
    {
        $model = new OaSysRules();
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
    public static function updateSysRules($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaSysRules::findOne($id);
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

    ##############################   joom to wish  ###############################

    /** get joom to wish list
     * @param $condition
     * Date: 2019-03-27 09:08
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getJoomWishList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaJoomToWish::find();
        if(isset($condition['greaterEqual'])) $query->andFilterWhere(['like', 'greaterEqual', $condition['greaterEqual']]);
        if(isset($condition['less'])) $query->andFilterWhere(['like', 'less', $condition['less']]);
        if(isset($condition['addedPrice'])) $query->andFilterWhere(['addedPrice' => $condition['addedPrice']]);
        if(isset($condition['createDate'])) $query->andFilterWhere(['createDate' => $condition['createDate']]);
        if(isset($condition['updateDate'])) $query->andFilterWhere(['updateDate' => $condition['updateDate']]);
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
     * Date: 2019-03-27 09:13
     * Author: henry
     * @return array|OaJoomToWish
     */
    public static function createJoomWish($condition)
    {
        $model = new OaJoomToWish();
        $model->attributes = $condition;
        $model->createDate = date('Y-m-d H:i:s');
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
     * Date: 2019-03-27 09:22
     * Author: henry
     * @return array|bool|null|static
     */
    public static function updateJoomWish($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaJoomToWish::findOne($id);
        $model->attributes = $condition;
        $model->updateDate = date('Y-m-d H:i:s');
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