<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-28 14:14
 */

namespace backend\modules\v1\models;

use backend\models\EbayAllotRule;
use backend\models\EbayCategory;
use backend\models\EbayCateRule;
use backend\models\EbayHotRule;
use backend\models\EbayNewRule;
use Yii;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use backend\modules\v1\utils\Helper;

class ApiProductsEngine
{

    /**
     * 认领
     * @param $plat
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function accept($plat, $type, $id)
    {
        $username = Yii::$app->user->identity->username;
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {
            $col = $db->getCollection('ebay_recommended_product');
            $doc = $col->findOne(['_id' => $id]);

            $recommnedId = $doc['productType'] == 'new' ? 'new.' . $id : 'hot.' . $id;

            //print_r($doc);exit;
            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $accept = ArrayHelper::getValue($doc, 'accept', []);
            if (!empty($accept)) {
                throw new \Exception('产品已被认领');
            }
            $accept[] = $username;
            $col->update(['_id' => $id], ['accept' => array_unique($accept)]);

            // 转至逆向开发
            $product_info = [
                'recommendId' => $recommnedId, 'img' => $doc['mainImage'], 'cate' => '女人世界',
                'stockUp' => '否', 'subCate' => '女包', 'salePrice' => $doc['price'], 'flag' => 'backward',
                'type' => 'create', 'introducer' => 'proEngine'
            ];
            Yii::$app->request->setBodyParams(['condition' => $product_info]);
            $ret = Yii::$app->runAction('/v1/oa-goods/dev-create');
            return $ret;
        }
    }

    /**
     * 拒绝
     * @param $plat
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function refuse($plat, $type, $id, $reason)
    {
        $username = Yii::$app->user->identity->username;
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {

            $col = $db->getCollection('ebay_recommended_product');
            $doc = $col->findOne(['_id' => $id]);

            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $refuse = ArrayHelper::getValue($doc, 'refuse', []);
            $refuse[$username] = $reason;
            $col->update(['_id' => $id], ['refuse' => array_unique($refuse)]);

            return $col->findOne(['_id' => $id]);

        }
    }

    /**
     * 立即执行规则
     * @param $ruleType
     * @param $ruleId
     * @return array
     */
    public static function run($ruleType, $ruleId)
    {
        $playLoad = ['ruleType' => $ruleType, 'ruleId' => $ruleId];
        $url = Yii::$app->params['recommendServiceUrl'];
        $ret = Helper::request($url, json_encode($playLoad));
        return $ret;
    }


    /**
     * @param $users
     * @param $site
     * Date: 2019-10-31 13:58
     * Author: henry
     * @return array
     */
    public static function getUserCate($users, $site)
    {
        $catQuery = (new Query())
            ->select("developer")
            ->from('proEngine.ebay_developer_category ed')
            ->leftJoin('proEngine.ebay_category ea', 'ea.id=categoryId')
            ->andFilterWhere(['developer' => $users])
            ->andFilterWhere(['marketplace' => $site])
            ->groupBy('developer')
            ->count();
        $isSetCat = $catQuery == count($users) ? true : false; //判断用户是否设置有独立的产品类目
        //部门开发对应产品类目或  开发自己的产品类目
        $category = (new Query())
            ->select("ea.category")
            ->from('proEngine.ebay_developer_category ed')
            ->leftJoin('proEngine.ebay_category ea', 'ea.id=categoryId')
            ->andFilterWhere(['developer' => $users])
            ->andFilterWhere(['marketplace' => $site])
            ->all();
        $categoryArr = array_unique(ArrayHelper::getColumn($category, 'category'));
        return [$isSetCat, $categoryArr];
    }

    /** 获取 分配规则详情
     * @param $id
     * Date: 2019-11-12 13:04
     * Author: henry
     * @return array|null|\yii\mongodb\ActiveRecord
     */
    public static function getAllotInfo($id)
    {
        $rule = EbayAllotRule::find()->where(['_id' => $id])->asArray()->one();
        $newDetail['ruleType'] = 'new';
        $hotDetail['ruleType'] = 'hot';
        $newDetail['flag'] = $hotDetail['flag'] = false;
        $newDetail['ruleValue'] = $hotDetail['ruleValue'] = [];
        $newRule = EbayNewRule::find()->asArray()->all();
        foreach ($newRule as $k => $val) {
            if (isset($rule['detail']) && $rule['detail']) {
                foreach ($rule['detail'] as $v) {
                    if (isset($v['ruleType']) && $v['ruleType'] == 'new' && $val['_id'] == $v['ruleId']) {
                        $newDetail['flag'] = true;
                        $newDetail['ruleValue'][$k] =
                            [
                                'ruleId' => $val['_id'],
                                'ruleName' => $val['ruleName'],
                                'flag' => true
                            ];
                    }
                }
            } else {
                $newDetail['ruleValue'][$k] =
                    [
                        'ruleId' => $val['_id'],
                        'ruleName' => $val['ruleName'],
                        'flag' => false
                    ];
            }
        }
        $hotRule = EbayHotRule::find()->asArray()->all();
        foreach ($hotRule as $k => $val) {
            if (isset($rule['detail']) && $rule['detail']) {
                foreach ($rule['detail'] as $v) {
                    if (isset($v['ruleType']) && $v['ruleType'] == 'hot' && $val['_id'] == $v['ruleId']) {
                        $hotDetail['flag'] = true;
                        $hotDetail['ruleValue'][$k] =
                            [
                                'ruleId' => $val['_id'],
                                'ruleName' => $val['ruleName'],
                                'flag' => false
                            ];
                    }
                }
            } else {
                $hotDetail['ruleValue'][$k] =
                    [
                        'ruleId' => $val['_id'],
                        'ruleName' => $val['ruleName'],
                        'flag' => false
                    ];
            }
        }
        $rule['detail'] = [$newDetail, $hotDetail];
        return $rule;
    }

    /** 获取类目规则详情
     * @param $id
     * Date: 2019-11-12 13:04
     * Author: henry
     * @return array|null|\yii\mongodb\ActiveRecord
     */
    public static function getCateInfo($id)
    {
        $rule = EbayCateRule::find()->where(['_id' => $id])->asArray()->one();
        $allCateArr = EbayCategory::find()->asArray()->all();
        //获取所有平台信息
        $platArr = ArrayHelper::getColumn($allCateArr,'plat');
        $marketplaceArr = ArrayHelper::getColumn($allCateArr,'marketplace');


        $detail['ruleType'] = 'new';
        $detail['flag'] = false;



        $newRule = EbayNewRule::find()->asArray()->all();
        foreach ($newRule as $k => $val) {
            if (isset($rule['detail']) && $rule['detail']) {
                foreach ($rule['detail'] as $v) {
                    if (isset($v['ruleType']) && $v['ruleType'] == 'new' && $val['_id'] == $v['ruleId']) {
                        $newDetail['flag'] = true;
                        $newDetail['ruleValue'][$k] =
                            [
                                'ruleId' => $val['_id'],
                                'ruleName' => $val['ruleName'],
                                'flag' => true
                            ];
                    }
                }
            } else {
                $newDetail['ruleValue'][$k] =
                    [
                        'ruleId' => $val['_id'],
                        'ruleName' => $val['ruleName'],
                        'flag' => false
                    ];
            }
        }

        $rule['detail'] = [];
        return $rule;
    }

}