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
use backend\models\WishProducts;
use backend\models\JoomProducts;
use backend\modules\v1\utils\Helper;
use yii\data\ArrayDataProvider;

class ApiProductsEngine
{

    /**
     * @return array|ArrayDataProvider|\yii\db\ActiveRecord[]
     * @throws \yii\db\Exception
     */
    public static function recommend()
    {
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
//        $userList = ApiUser::getUserList($username);

        // 请求参数
        $plat = \Yii::$app->request->get('plat');
        $type = \Yii::$app->request->get('type', '');
        $page = \Yii::$app->request->get('page', 1);
        $pageSize = \Yii::$app->request->get('pageSize', 20);
        $marketplace = \Yii::$app->request->get('marketplace');//站点
        $recommendStatus = \Yii::$app->request->get('recommendStatus', '');//站点

        //平台数据
        if ($plat === 'ebay') {
            return static::getEbayRecommend($type, $marketplace, $page, $pageSize, [$recommendStatus]);
        }

        if ($plat === 'wish') {
            return WishProducts::find()->all();
        }

        if ($plat === 'joom') {
            return JoomProducts::find()->all();
        }
    }

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

            $itemId = $doc['itemId'];

            $recommendId = $doc['productType'] == 'new' ? 'new.' . $id : 'hot.' . $id;

            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $accept = ArrayHelper::getValue($doc, 'accept', []);
            if (!empty($accept)) {
                throw new \Exception('产品已被认领');
            }
            $accept[] = $username;
            $col->update(['_id' => $id], ['accept' => array_unique($accept)]);

            //推送新数据到固定端口
            Helper::pushData();

            // 转至逆向开发
            $product_info = [
                'recommendId' => $recommendId, 'img' => $doc['mainImage'], 'cate' => '女人世界',
                'origin1' => 'https://www.ebay.com/itm/' . $doc['itemId'],
                'stockUp' => '否', 'subCate' => '女包', 'salePrice' => $doc['price'], 'flag' => 'backward',
                'type' => 'create', 'introducer' => 'proEngine'
            ];

            // 更改推荐状态
            $table = $doc['productType'] === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
            static::setRecommendToPersons($table, $itemId, 'new');
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
            $itemId = $doc['itemId'];

            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $refuse = ArrayHelper::getValue($doc, 'refuse', []);
            $refuse[$username] = $reason;
            $col->update(['_id' => $id], ['refuse' => array_unique($refuse)]);

            //推送新数据到固定端口
            Helper::pushData();

            // 更改推荐状态
            $table = $doc['productType'] === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
            static::setRecommendToPersons($table, $itemId, 'hot', $reason);


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


    private static function getEbayRecommend($type, $marketplace, $page, $pageSize, $recommendStatus = [])
    {
        $ret = [];
        //当天推荐数据
        $today = date('Y-m-d');
        // 新品
        if ($type === 'new') {
            //当前在用规则下数据
            $newRules = EbayNewRule::find()->select(['id'])->all();
            $cur = (new \yii\mongodb\Query())->from('ebay_new_product')
                ->andFilterWhere(['marketplace' => $marketplace])
                ->andWhere(['recommendDate' => ['$regex' => $today]])
                ->all();
            foreach ($cur as $row) {
                foreach ($newRules as $rule) {
                    $productRules = $row['rules'];
                    if ( in_array($rule->_id, $productRules, false)) {
                        //推荐状态筛选
                        $item = static::recommendStatusFilter($recommendStatus, $row);
                        if (!empty($item)) {
                            $ret[] = $item;
                            break;
                        }
                    }
                }
            }
        }
        // 热销
        if ($type === 'hot') {
            //当前在用规则
            $hotRules = EbayHotRule::find()->select(['id'])->all();
            $cur = (new \yii\mongodb\Query())->from('ebay_hot_product')
                ->andFilterWhere(['marketplace' => $marketplace])
                ->andWhere(['recommendDate' => ['$regex' => $today]])
                ->all();
            foreach ($cur as $row) {
                foreach ($hotRules as $rule) {
                    $productRules = $row['rules'];
                    if (in_array($rule->_id, $productRules, false)) {
                        //推荐状态筛选
                        $item = static::recommendStatusFilter($recommendStatus, $row);
                        if (!empty($item)) {
                            $ret[] = $item;
                            break;
                        }
                    }
                }
            }
        }
        // 分页，排序
        $data = new ArrayDataProvider([
            'allModels' => $ret,
            'sort' => [
                'attributes' => [
                    'price', 'visit', 'sold',
                    'salesThreeDay1', 'salesThreeDayGrowth', 'paymentThreeDay1',
                    'salesWeek1', 'paymentWeek1', 'salesWeekGrowth', 'listedTime'
                ],
                'defaultOrder' => [
                    'sold' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);
        return $data;
    }


    private static function recommendStatusFilter($recommendStatus, $row)
    {
        $ret = [];
        //$recommendStatus = ['未推送','未处理', '已过滤', '已认领'];
        //var_dump($row['recommendToPersons']);exit;
        foreach ($recommendStatus as $rs) {
            $recommendPersons = $row['recommendToPersons'];
            if ($rs === '未推送') {
                if (empty($recommendPersons)) {
                    $ret = $row;
                }
            } elseif ($rs === '已过滤') {
                foreach ($recommendPersons as $rp) {
                    if ($rp['status'] === 'refuse') {
                        $ret = $row;
                        break;
                    }
                }

            } elseif ($rs === '已认领') {
                foreach ($recommendPersons as $rp) {
                    if ($rp['status'] === 'accept') {
                        $ret = $row;
                        break;
                    }
                }
            } elseif ($rs === '未处理') {
                $flag = 1;
                if (empty($recommendPersons)) {
                    $flag = 0;
                }
                foreach ($recommendPersons as $rp) {
                    if (!empty($rp['status'])) {
                        $flag = 0;
                    }
                }
                if ($flag) {
                    $ret = $row;
                }
            } else {
                $ret = $row;
            }

        }
        return $ret;
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
        $rule = EbayAllotRule::findOne(['_id' => $id]);
        $newDetail['ruleType'] = 'new';
        $hotDetail['ruleType'] = 'hot';
        $newDetail['flag'] = $hotDetail['flag'] = false;
        $newDetail['ruleValue'] = $hotDetail['ruleValue'] = [];
        $newRule = EbayNewRule::find()->all();
        foreach ($newRule as $k => $value) {
            if (isset($rule['detail']) && $rule['detail']) {
                foreach ($rule['detail'] as $val) {
                    //print_r($val['ruleValue']);exit();
                    if (isset($val['ruleType']) && $val['ruleType'] == 'new' &&
                        isset($val['ruleValue']) && $val['ruleValue']) {
                        foreach ($val['ruleValue'] as $v) {
                            if (isset($v['ruleId']) && (string)$value['_id'] == $v['ruleId']['oid']) {
                                $newDetail['flag'] = true;
                                $newDetail['ruleValue'][$k] =
                                    [
                                        'ruleId' => $value['_id'],
                                        'ruleName' => $value['ruleName'],
                                        'flag' => true
                                    ];
                            }
                        }
                    }
                }
            } else {
                $newDetail['ruleValue'][$k] =
                    [
                        'ruleId' => $value['_id'],
                        'ruleName' => $value['ruleName'],
                        'flag' => false
                    ];
            }
        }
        $hotRule = EbayHotRule::find()->asArray()->all();
        foreach ($hotRule as $k => $value) {
            if (isset($rule['detail']) && $rule['detail']) {
                foreach ($rule['detail'] as $val) {
                    if (isset($val['ruleType']) && $val['ruleType'] == 'hot' &&
                        isset($val['ruleValue']) && $val['ruleValue']) {
                        foreach ($val['ruleValue'] as $v) {
                            if (isset($v['ruleId']) && (string)$value['_id'] == $v['ruleId']['oid']) {
                                $hotDetail['flag'] = true;
                                $hotDetail['ruleValue'][$k] =
                                    [
                                        'ruleId' => $value['_id'],
                                        'ruleName' => $value['ruleName'],
                                        'flag' => true
                                    ];
                            }
                        }
                    }
                }
            } else {
                $hotDetail['ruleValue'][$k] =
                    [
                        'ruleId' => $value['_id'],
                        'ruleName' => $value['ruleName'],
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
     * @return array|null|\yii\mongodb\ActiveRecord   TODO
     */
    public static function getCateInfo($id)
    {
        $rule = EbayCateRule::find()->where(['_id' => $id])->asArray()->one();
        //获取所有平台信息
        //$allCateArr = EbayCategory::find()->asArray()->all();
        $allPlatArr = Yii::$app->runAction('/v1/products-engine/plat')['data'];
        $detail = [];

        $detailArr = isset($rule['detail']) ? $rule['detail'] : [];
        //print_r($detailArr);exit;
        foreach ($allPlatArr as $dk => $value) {//遍历所有平台
            $item['plat'] = $value;
            $item['flag'] = false;
            $item['platValue'] = [];
            $allMarketplaceArr = Yii::$app->runAction('/v1/products-engine/marketplace', ['plat' => $value])['data'];
            foreach ($allMarketplaceArr as $mk => $marketplace) {
                $item['platValue'][$mk]['marketplace'] = $marketplace;
                $item['platValue'][$mk]['flag'] = false;
                $item['platValue'][$mk]['marketplaceValue'] = [];
                //获取平台站点下所有一级类目
                $allCateArr = $allCateArr = EbayCategory::find()
                    ->andFilterWhere(['plat' => $value])
                    ->andFilterWhere(['marketplace' => $marketplace])
                    ->orderBy('cate')->asArray()->all();
                foreach ($allCateArr as $ck => $cate) {//遍历平台下所有一级类目
                    $item['platValue'][$mk]['marketplaceValue'][$ck]['cate'] = $cate['cate'];
                    $item['platValue'][$mk]['marketplaceValue'][$ck]['flag'] = false;
                    foreach ($cate['subCate'] as $sk => $subCate) {  //遍历已有二级类目
                        $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCate'][$sk] = $subCate;
                        $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCateChecked'] = [];
                        if ($detailArr) {
                            foreach ($detailArr as $detailValue) {
                                if (isset($detailValue['plat']) && $detailValue['plat'] == $value) { //判断是否有该平台
                                    $item['flag'] = true;
                                    if (isset($detailValue['platValue']) && $detailValue['platValue']) {
                                        foreach ($detailValue['platValue'] as $platValue) {
                                            if (isset($platValue['marketplace']) && $platValue['marketplace'] == $marketplace) {//判断是否有该站点
                                                $item['platValue'][$mk]['flag'] = true;
                                                if (isset($platValue['marketplaceValue']) && $platValue['marketplaceValue']) {
                                                    foreach ($platValue['marketplaceValue'] as $marketplaceValue) {
                                                        if (isset($marketplaceValue['cate']) && $marketplaceValue['cate'] == $cate['cate']) {//判断是否有该一级类目
                                                            $item['platValue'][$mk]['marketplaceValue'][$ck]['flag'] = true;
                                                            $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCate'] = $cate['subCate'];
                                                            $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCateChecked'] = [];
                                                            if (isset($marketplaceValue['cateValue']) && $marketplaceValue['cateValue'] &&
                                                                isset($marketplaceValue['cateValue']['subCateChecked']) && $marketplaceValue['cateValue']['subCateChecked']
                                                            ) {
                                                                $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCateChecked'] = $marketplaceValue['cateValue']['subCateChecked'];
                                                                /*foreach ($marketplaceValue['cateValue'] as $cateValue){
                                                                    if(isset($cateValue['subCateChecked']) && $cateValue['subCateChecked'] == $subCate) {
                                                                        $item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue']['subCateChecked'][] = $subCate;
                                                                        //$item['platValue'][$mk]['marketplaceValue'][$ck]['cateValue'][$sk]['flag'] = true;//判断是否有该二级类目
                                                                    }
                                                                }*/
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $detail[$dk] = $item;
        }
        if ($rule) $res['_id'] = $rule['_id'];
        //获取普源类目
        $pyCate = Yii::$app->runAction('/v1/products-engine/py-cate')['data'];
        foreach ($pyCate as $k => $v) {
            if ($rule && $rule['pyCate'] == $v) {
                $res['pyCate'][$k] = ['name' => $v, 'flag' => true];
            } else {
                $res['pyCate'][$k] = ['name' => $v, 'flag' => false];
            }
        }
        $res['detail'] = $detail;
        return $res;
    }


    /**
     * @param $table
     * @param $itemId
     * @param $type
     * @param string $reason
     */
    private static function setRecommendToPersons($table, $itemId, $type, $reason = '')
    {
        # [{"name":"陈微微","status":"refuse", "reason":"不行"},{"name":"刘珊珊","status":"accept", "reason":""}]
        $username = Yii::$app->user->identity->username;
        if ($type === 'new') {
            $person = ['name' => $username, 'status' => 'accept', 'reason' => $reason];
        } else {
            $person = ['name' => $username, 'status' => 'refuse', 'reason' => $reason];
        }
        $collection = Yii::$app->mongodb->getCollection($table);
        $product = $collection->findOne(['itemId' => $itemId]);
        $oldPersons = $product['recommendToPersons'];
        $currentPersons = static::insertOrUpdateRecommendToPersons($person, $oldPersons);
        $collection->update(['itemId' => $itemId], ['recommendToPersons' => $currentPersons]);
    }


    /**
     * 更新或新增推荐人
     * @param $persons
     * @param $oldPersons
     * @return array
     */
    private static function insertOrUpdateRecommendToPersons($persons, $oldPersons)
    {
        if (empty($oldPersons)) {
            $oldPersons[] = $persons;
        } else {
            $appendFlag = 1;
            foreach ($oldPersons as &$op) {
                if ($op['name'] === $persons['name']) {
                    $op = $persons;
                    $appendFlag = 0;
                    break;
                }
            }
            if ($appendFlag) {
                $oldPersons[] = $persons;
            }
        }
        return $oldPersons;

    }


    /**
     * 图片搜索
     * @param $imageUrl
     * @return mixed
     */
    public static function imageSearch($imageUrl)
    {
        $playLoad = ['imageUrl' => $imageUrl];
        $url = Yii::$app->params['imageSearchUrl'];
        $ret = Helper::request($url, json_encode($playLoad));
        $ret = $ret[1]['data']['Auctions'];
        $out = [];
        $goods = [];
        try {
            foreach ($ret as $ele) {
                $goodsCode = static::getImageGoodsCode($ele['PicName']);
                if (!in_array($goodsCode, $goods, false)) {
                    $ele['GoodsCode'] = $goodsCode;
                    $goods[] = $goodsCode;
                    $out[] = $ele;
                }
            }
            return ['Auctions' => $out];
        } catch (\Exception $why) {
            return ['Auctions' => []];
        }
    }


    /**
     * 根据SKU获取商品编码
     * @param $sku
     * @return mixed
     */
    private static function getImageGoodsCode($sku)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('images_tasks');
        $tasks = $col->find([
            'sku' => $sku
        ]);
        try {
            $ret = [];
            foreach ($tasks as $row) {
                $ret[] = $row;
            }
            return $ret[0]['goodsCode'];
        } catch (\Exception $why) {
            return $sku;
        }

    }

}