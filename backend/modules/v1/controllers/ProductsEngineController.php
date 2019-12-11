<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayAllotRule;
use backend\models\EbayCategory;
use backend\models\EbayCateRule;
use backend\models\EbayDeveloperCategory;
use backend\models\EbayHotRule;
use backend\models\EbayNewRule;
use backend\modules\v1\models\ApiUser;
use console\models\ProductEngine;
use yii\data\ArrayDataProvider;
use backend\modules\v1\models\ApiProductsEngine;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Yii;

class ProductsEngineController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * 产品引擎  每日推荐
     * Date: 2019-10-30 17:36
     * Author: henry
     * @return array|\yii\db\ActiveRecord[]|\yii\data\ActiveDataProvider[]
     */
    public function actionRecommend()
    {
        try {
            return ApiProductsEngine::recommend();

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /** 产品中心  智能推荐
     * Date: 2019-10-30 17:36
     * Author: henry
     * @return array|\yii\db\ActiveRecord[]|\yii\data\ActiveDataProvider[]
     * @throws \yii\db\Exception
     */
    public function actionMindRecommend()
    {
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        //$username = '刘爽';
        $userList = ApiUser::getUserList($username);
        //获取当前登录用户权限下的用户是否有指定eBay产品类目

        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $page = \Yii::$app->request->get('page', 1);
            $pageSize = \Yii::$app->request->get('pageSize', 20);
            $marketplace = \Yii::$app->request->get('marketplace');//站点
            $ret = [];
            if ($plat === 'ebay') {
                $list = (new \yii\mongodb\Query())->from('ebay_recommended_product')
                    ->andFilterWhere(['marketplace' => $marketplace])
                    ->andFilterWhere(['productType' => $type])
                    ->andFilterWhere(['dispatchDate' => ['$regex' => date('Y-m-d')]])
                    ->all();
                foreach ($list as $row) {
                    if (isset($row['accept']) && $row['accept'] ||    //过滤掉已经认领的产品
                        isset($row['refuse'][$username])       //过滤掉当前用户已经过滤的产品
                    ) {
                        continue;
                    } else {
                        $receiver = [];
                        foreach ($row['receiver'] as $v) {
                            if (in_array($v, $userList)) {  //过滤被推荐人(不在自己权限下的被推荐人筛选掉)
                                $receiver[] = $v;
                            }
                        }
                        //过滤当前用户的权限下的用户
                        $row['receiver'] = $receiver;
                        if ($receiver) {
                            $ret[] = $row;
                        }
                    }
                }
                $data = new ArrayDataProvider([
                    'allModels' => $ret,
                    'sort' => [
                        'attributes' => ['price', 'visit', 'sold', 'listedTime'],
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

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    /**
     * 认领
     * @return array
     */
    public function actionAccept()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            return ApiProductsEngine::accept($plat, $type, $id);

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 拒绝
     * @return array|mixed
     */
    public function actionRefuse()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            $reason = isset($condition['reason']) && $condition['reason'] ? $condition['reason'] : '拒绝';
            $res = ApiProductsEngine::refuse($plat, $type, $id, $reason);
            return $res;

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 立即执行规则
     * @return array
     */
    public function actionRunRule()
    {
        try {
            $condition = Yii::$app->request->post('condition');
            $ruleType = Yii::$app->request->get('type', '');
            $ruleId = $condition['ruleId'];
            return ApiProductsEngine::run($ruleType, $ruleId);


        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 规则列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionRule()
    {
        $type = Yii::$app->request->get('type', 'new');
        try {
            if ($type === 'new') {
                return EbayNewRule::find()->all();
            }
            if ($type === 'hot') {
                return EbayHotRule::find()->all();
            }

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 增加规则
     * @return array
     */
    public function actionSaveRule()
    {
        try {

            $type = Yii::$app->request->get('type', 'new');
            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            if ($type === 'new') {
                $rule = EbayNewRule::findOne($id);
                if (empty($rule)) {
                    $rule = new EbayNewRule();
                    $condition['creator'] = $userName;
                }
                $rule->setAttributes($condition);
                if (!$rule->save(false)) {
                    throw new \Exception('fail to save new rule');
                }
                return [];
            }

            if ($type === 'hot') {
                $rule = EbayHotRule::findOne($id);
                if (empty($rule)) {
                    $rule = new EbayHotRule();
                    $condition['creator'] = $userName;
                }
                $rule->setAttributes($condition);
                if (!$rule->save()) {
                    throw new \Exception('fail to save hot rule');
                }
                return [];
            }

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteRule()
    {
        $type = Yii::$app->request->get('type', 'new');
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            if ($type === 'new') {
                EbayNewRule::findOne($id)->delete();
            }
            if ($type === 'hot') {
                EbayHotRule::findOne($id)->delete();
            }
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    //==========================================================================

    /**
     * eBay类目列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionEbayCat()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            return EbayCategory::find()
                ->andFilterWhere(['parentId' => $condition['parentId']])
                ->andFilterWhere(['like', 'category', $condition['category']])
                ->andFilterWhere(['like', 'marketplace', $condition['marketplace']])
                ->orderBy('parentId,category')
                ->all();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /** 发开员eBay类目列表
     * Date: 2019-10-31 15:11
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDevCat()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            $query = (new Query())
                ->select(["ed.*",
                    "p.category as firstCategory",
                    "ea.category",
                    "ea.marketplace",
                ])
                ->from('proEngine.ebay_developer_category ed')
                ->leftJoin('proEngine.ebay_category ea', 'ea.id=categoryId')
                ->leftJoin('proEngine.ebay_category p', 'p.id=ea.parentId')
                ->andFilterWhere(['like', 'developer', $condition['developer']])
                ->andFilterWhere(['like', 'ea.category', $condition['category']])
                ->andFilterWhere(['like', 'ea.marketplace', $condition['marketplace']])
                ->all();
            return new ArrayDataProvider([
                'allModels' => $query,
                'pagination' => [
                    'page' => isset($condition['page']) && $condition['page'] ? $condition['page'] - 1 : 0,
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 增加发开员eBay类目规则
     * @return array
     */
    public function actionSaveDevCat()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $categoryId = ArrayHelper::getValue($condition, 'categoryId', '');
            $developer = ArrayHelper::getValue($condition, 'developer', '');
            /*if(!$categoryId){
                throw new \Exception('Attribute categoryId can not be empty!');
            }*/
            $model = EbayDeveloperCategory::findOne($id);
            if (empty($model)) {
                $model = new EbayDeveloperCategory();
            }
            $model->setAttributes([
                'id' => $id,
                'developer' => $developer,
                'categoryId' => $categoryId,
            ]);
            if (!$model->save()) {
                throw new \Exception('fail to add new ebay developer category rule');
            }
            return [];
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /** 删除发开员eBay类目规则
     * Date: 2019-10-28 15:07
     * Author: henry
     * @return array|false|int
     * @throws \Throwable
     */
    public function actionDeleteDevCat()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            return EbayDeveloperCategory::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //======================================================================================
    //类目规则
    public function actionPlat()
    {
        return [
            'ebay',
            'wish',
            //'joom',
        ];
    }

    public function actionMarketplace()
    {
        $plat = Yii::$app->request->get('plat', null);
        try {
            return EbayCategory::find()->andFilterWhere(['plat' => $plat])->distinct('marketplace');
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    public function actionPyCate()
    {
        $cateList = Yii::$app->runAction('/v1/oa-goodsinfo/attribute-info-cat')['data'];
        try {
            $cate = EbayCateRule::find()->distinct('pyCate');
            $excludeCate = EbayCateRule::find()->distinct('excludePyCate');
            return array_values(array_unique(array_merge($cateList, $cate, $excludeCate)));
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 类目列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionCategory()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            return EbayCategory::find()
                ->andFilterWhere(['plat' => $condition['plat']])
                ->andFilterWhere(['marketplace' => $condition['marketplace']])
                ->andFilterWhere(['like', 'cate', $condition['cate']])
                ->orderBy('cate')
                ->all();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    public function actionSaveCategory()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayCategory::findOne($id);
            if (empty($rule)) {
                $rule = new EbayCategory();
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //获取类目规则详情
    public function actionCateRuleInfo($id)
    {
        return ApiProductsEngine::getCateInfo($id);
    }

    /** 类目规则列表
     * Date: 2019-11-05 14:26
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionCateRule()
    {
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('pageSize', 20);
        try {
            $data = EbayCateRule::find()->all();
            return $data = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'page' => $page - 1,
                    'pageSize' => $pageSize,
                ],
            ]);
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    /**
     * 增加或编辑规则
     * @return array
     */
    public function actionSaveCateRule()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayCateRule::findOne($id);
            if (empty($rule)) {
                $rule = new EbayCateRule();
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteCateRule()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            EbayCateRule::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


//======================================================================================
    //分配规则
    public function actionAllotRule()
    {
        try {
            $data = EbayAllotRule::find()->asArray()->all();
            /*$res = [];
            foreach ($data as $v) {
                $item = $v;
                if ($v['ruleType'] == 'new') {
                    $ebayNewRule = EbayNewRule::findOne(['_id' => $v['ruleId']]);
                    $item['ruleName'] = $ebayNewRule ? $ebayNewRule['ruleName'] : '';
                } elseif ($v['ruleType'] == 'hot') {
                    $ebayHotRule = EbayHotRule::findOne(['_id' => $v['ruleId']]);
                    $item['ruleName'] = $ebayHotRule ? $ebayHotRule['ruleName'] : '';
                } else {
                    $item['ruleName'] = '';
                }
                $res[] = $item;
            }*/

            return $data;
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //获取分配规则详情
    public function actionAllotRuleInfo($id)
    {
        return ApiProductsEngine::getAllotInfo($id);
    }

    /**
     * 增加或编辑规则
     * @return array
     */
    public function actionSaveAllotRule()
    {
        try {

            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayAllotRule::findOne($id);
            if (empty($rule)) {
                $rule = new EbayAllotRule();
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteAllotRule()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            EbayAllotRule::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

//===========================================================================================
    //统计报表

    /**
     * 统计报表首页，每日统计
     * Date: 2019-11-20 9:39
     * Author: henry
     * @return array
     */
    public function actionDailyReport()
    {
        return ProductEngine::getDailyReportData();
    }

    /**
     * 认领产品报表
     * Date: 2019-11-19 8:54
     * Author: henry
     * @return array
     */
    public function actionProductReport()
    {
        $db = Yii::$app->mongodb;
        $condition = Yii::$app->request->post('condition');
        $developer = isset($condition['developer']) && $condition['developer'] ? $condition['developer'] : [];
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . " 23:59:59") : '';
        //计算开发分配产品总数


        //计算开发认领产品总数
        $allQuery = (new Query())
            ->from('proCenter.oa_goodsinfo gs')
            ->select('g.developer, count(goodsCode) as totalNum')
            ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
            ->andWhere(['g.introducer' => 'proEngine'])
            ->andFilterWhere(['g.developer' => $developer])
            ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
            ->groupBy('g.developer');

        //计算爆款数
        $hotQuery = (new Query())
            ->from('proCenter.oa_goodsinfo')
            ->select('g.developer, count(goodsCode) as hotNum')
            ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
            ->andWhere(['g.introducer' => 'proEngine'])
            ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
            ->andFilterWhere(['g.developer' => $developer])
            ->andFilterWhere(['goodsStatus' => '爆款'])
            ->groupBy('g.developer');

        //计算旺款数量
        $popQuery = (new Query())
            ->from('proCenter.oa_goodsinfo')
            ->select('g.developer, count(goodsCode) as popNum')
            ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
            ->andWhere(['g.introducer' => 'proEngine'])
            ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
            ->andFilterWhere(['g.developer' => $developer])
            ->andFilterWhere(['goodsStatus' => '旺款'])
            ->groupBy('g.developer');

        $data = (new Query())
            ->select(["a.developer", "a.totalNum as claimNum",
                "IFNULL(h.hotNum,0) AS hotNum",
                "IFNULL(p.popNum,0) AS popNum",
                "ROUND(CASE WHEN totalNum=0 THEN 0 ELSE IFNULL(hotNum,0)/totalNum END,4) AS hotRate",
                "ROUND(CASE WHEN totalNum=0 THEN 0 ELSE IFNULL(popNum,0)/totalNum END,4) AS popRate"])
            ->from(['a' => $allQuery])
            ->leftJoin(['h' => $hotQuery], ['a.developer' => 'h.developer'])
            ->leftJoin(['p' => $popQuery], ['a.developer' => 'p.developer'])
            ->orderBy('totalNum DESC')
            ->all();


        //获取开发列表
        $devData = EbayAllotRule::find()->andFilterWhere(['username' => $developer])->all();

        $devList = ArrayHelper::getColumn($devData, 'username');
        $devHave = ArrayHelper::getColumn($data, 'developer');
        $devLeft = array_diff($devList, $devHave);
        $dataHave = $dataAdd = [];
        foreach ($data as $k => $v) {
            $dataHave[$k] = $v;
            $dataHave[$k]['dispatchNum'] = $db->getCollection('ebay_recommended_product')
                ->count(['receiver' => $v['developer'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dataHave[$k]['claimNum'] = $db->getCollection('ebay_recommended_product')
                ->count(['accept' => $v['developer'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dataHave[$k]['filterNum'] = $db->getCollection('ebay_recommended_product')
                ->count([
                    '$or' => [
                        [
                            "refuse.".$v['developer'] => null,
                            'accept' => ['$nin' => [null, $v['developer']]],
                        ],
                        [
                            "refuse.".$v['developer'] => ['$ne' => null]
                        ]
                    ],
                    "receiver" => $v['developer'] ,
                    'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]
                ]);
            $dataHave[$k]['unhandledNum'] = $db->getCollection('ebay_recommended_product')
                ->count([
                    "refuse.".$v['developer'] => null,
                    'accept' => null,
                    "receiver" => $v['developer'] ,
                    'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]
                ]);

            $dataHave[$k]['claimRate'] = $dataHave[$k]['dispatchNum'] ? round($dataHave[$k]['claimNum'] * 1.0 / $dataHave[$k]['dispatchNum'], 4) : '0';
            $dataHave[$k]['filterRate'] = $dataHave[$k]['dispatchNum'] ? round($dataHave[$k]['filterNum'] * 1.0 / $dataHave[$k]['dispatchNum'], 4) : '0';
        }
        foreach ($devLeft as $k => $v) {
            $dataAdd[$k]['developer'] = $v;
            $dataAdd[$k]['hotNum'] = '0';
            $dataAdd[$k]['popNum'] = '0';
            $dataAdd[$k]['hotRate'] = '0.0000';
            $dataAdd[$k]['popRate'] = '0.0000';
            $dataAdd[$k]['dispatchNum'] = $db->getCollection('ebay_recommended_product')
                ->count(['receiver' => $v, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dataAdd[$k]['claimNum'] = $db->getCollection('ebay_recommended_product')
                ->count(['accept' => $v, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dataAdd[$k]['filterNum'] = $db->getCollection('ebay_recommended_product')
                ->count([
                    '$or' => [
                        [
                            "refuse.".$v => null,
                            'accept' => ['$nin' => [null, $v]],
                        ],
                        [
                            "refuse.".$v => ['$ne' => null]
                        ]
                    ],
                    "receiver" => $v ,
                    'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]
                ]);
            $dataAdd[$k]['unhandledNum'] = $db->getCollection('ebay_recommended_product')
                ->count([
                    "refuse.".$v => null,
                    'accept' => null,
                    "receiver" => $v ,
                    'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]
                ]);

            $dataAdd[$k]['claimRate'] = $dataAdd[$k]['dispatchNum'] ? round($dataAdd[$k]['claimNum'] * 1.0 / $dataAdd[$k]['dispatchNum'], 4) : '0';
            $dataAdd[$k]['filterRate'] = $dataAdd[$k]['dispatchNum'] ? round($dataAdd[$k]['filterNum'] * 1.0 / $dataAdd[$k]['dispatchNum'], 4) : '0';
        }
        return array_merge($dataHave, $dataAdd);
    }

    /**
     * 推送规则统计
     * Date: 2019-11-19 15:52
     * Author: henry
     * @return array
     */
    public function actionRuleReport()
    {
        $db = Yii::$app->mongodb;
        $condition = Yii::$app->request->post('condition');
        $ruleType = isset($condition['ruleType']) && $condition['ruleType'] ? $condition['ruleType'] : '';
        $ruleName = isset($condition['ruleName']) && $condition['ruleName'] ? $condition['ruleName'] : '';
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . ' 23:59:59') : '';
        $newData = $hotData = [];
        //获取新品推送规则列表并统计产品数
        if (!$ruleType || $ruleType == 'new') {
            $newRuleList = EbayNewRule::find()->andFilterWhere(['ruleName' => ['$regex' => $ruleName]])->all();
            foreach ($newRuleList as $v) {
                $item['ruleType'] = $type = 'new';
                $item['ruleName'] = $v['ruleName'];

                $totalNum = $db->getCollection('ebay_new_product')
                    ->count(['rules' => $v['_id'], 'recommendDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $dispatchNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $claimNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], 'accept' => ['$size' => 1], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $filterNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], "refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $unhandledNewNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type,'rules' => $v['_id'],  "refuse" => null, 'accept' => null, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);


                $item['totalNum'] = $totalNum;
                $item['dispatchNum'] = $dispatchNum;
                $item['claimNum'] = $claimNum;
                $item['filterNum'] = $filterNum;
                $item['unhandledNewNum'] = $unhandledNewNum;

                //获取智能推荐新品 爆旺款全部产品
                $dataList = $hotQuery = (new Query())
                    ->from('proCenter.oa_goodsinfo')
                    ->select('g.recommendId,goodsStatus')
                    ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
                    ->andWhere(['g.introducer' => 'proEngine'])
                    ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
                    ->andFilterWhere(['like', 'g.recommendId', $type])
                    ->andFilterWhere(['goodsStatus' => ['爆款', '旺款']])
                    ->all();
                $hotNum = $popNum = 0;
                foreach ($dataList as $value) {
                    $recommend = $db->getCollection('ebay_recommended_product')->count(['_id' => substr($value['recommendId'], 4)]);
                    if ($value['goodsStatus'] == '爆款' && $recommend) {
                        $hotNum += 1;
                    } elseif ($value['goodsStatus'] == '旺款' && $recommend) {
                        $popNum += 1;
                    }
                }
                $item['hotNum'] = $hotNum;
                $item['popNum'] = $popNum;

                $item['claimRate'] = $dispatchNum ? round($claimNum*1.0/$dispatchNum,4) : 0;
                $item['filterRate'] = $dispatchNum ? round($filterNum*1.0/$dispatchNum,4) : 0;
                $item['hotRate'] = $claimNum ? round($hotNum*1.0/$claimNum,4) : 0;
                $item['popRate'] = $claimNum ? round($popNum*1.0/$claimNum,4) : 0;

                $newData[] = $item;
            }
        }

        //获取热销产品推送规则列表并统计产品数
        if (!$ruleType || $ruleType == 'hot') {
            $hotRuleList = EbayHotRule::find()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ->andFilterWhere(['ruleName' => ['$regex' => $ruleName]])->all();
            foreach ($hotRuleList as $v) {
                $item['ruleType'] = $type = 'hot';
                $item['ruleName'] = $v['ruleName'];
                $totalNum = $db->getCollection('ebay_hot_product')
                    ->count(['rules' => $v['_id'], 'recommendDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $dispatchNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $claimNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], 'accept' => ['$size' => 1], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $filterNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type, 'rules' => $v['_id'], "refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
                $unhandledNewNum = $db->getCollection('ebay_recommended_product')
                    ->count(['productType' => $type,'rules' => $v['_id'],  "refuse" => null, 'accept' => null, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);


                $item['totalNum'] = $totalNum;
                $item['dispatchNum'] = $dispatchNum;
                $item['claimNum'] = $claimNum;
                $item['filterNum'] = $filterNum;
                $item['unhandledNewNum'] = $unhandledNewNum;

                //获取智能推荐新品 爆旺款全部产品
                $dataList = $hotQuery = (new Query())
                    ->from('proCenter.oa_goodsinfo')
                    ->select('g.recommendId,goodsStatus')
                    ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
                    ->andWhere(['g.introducer' => 'proEngine'])
                    ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
                    ->andFilterWhere(['like', 'g.recommendId', $type])
                    ->andFilterWhere(['goodsStatus' => ['爆款', '旺款']])
                    ->all();
                $hotNum = $popNum = 0;
                foreach ($dataList as $value) {
                    $recommend = $db->getCollection('ebay_recommended_product')->count(['_id' => substr($value['recommendId'], 4)]);
                    if ($value['goodsStatus'] == '爆款' && $recommend) {
                        $hotNum += 1;
                    } elseif ($value['goodsStatus'] == '旺款' && $recommend) {
                        $popNum += 1;
                    }
                }
                $item['hotNum'] = $hotNum;
                $item['popNum'] = $popNum;

                $item['claimRate'] = $dispatchNum ? round($claimNum*1.0/$dispatchNum,4) : 0;
                $item['filterRate'] = $dispatchNum ? round($filterNum*1.0/$dispatchNum,4) : 0;
                $item['hotRate'] = $claimNum ? round($hotNum*1.0/$claimNum,4) : 0;
                $item['popRate'] = $claimNum ? round($popNum*1.0/$claimNum,4) : 0;

                //var_dump($claimNum);exit;
                $hotData[] = $item;
            }
        }

        return array_merge($newData, $hotData);
    }

    /**
     * 过滤理由统计
     * Date: 2019-11-22 15:52
     * Author: henry
     * @return array
     */
    public function actionRefuseReport()
    {
        $db = Yii::$app->mongodb;
        $condition = Yii::$app->request->post('condition');
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . ' 23:59:59') : '';

        $product = $db->getCollection('ebay_recommended_product')
            ->find(["refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
        $refuseArr = ArrayHelper::getColumn($product,'refuse');
        $arr = [
            '1: 重复' => 0,
            '2: 侵权' => 0,
            '3: 不好运输' => 0,
            '4: 销量不好' => 0,
            '5: 找不到货源' => 0,
            '6: 价格没优势' => 0,
            '7: 评分低' => 0,
            '8: 其他' => 0,
        ];
        $refuseData = [];
        foreach($refuseArr as $val) {
            foreach ($val as $v){
                if(strpos($v,'1:') !== false){
                    $arr['1: 重复'] += 1;
                }elseif (strpos($v,'2:') !== false){
                    $arr['2: 侵权'] += 1;
                }elseif (strpos($v,'3:') !== false){
                    $arr['3: 不好运输'] += 1;
                }elseif (strpos($v,'4:') !== false){
                    $arr['4: 销量不好'] += 1;
                }elseif (strpos($v,'5:') !== false){
                    $arr['5: 找不到货源'] += 1;
                }elseif (strpos($v,'6:') !== false){
                    $arr['6: 价格没优势'] += 1;
                }elseif (strpos($v,'7:') !== false){
                    $arr['7: 评分低'] += 1;
                }else if(strpos($v,'8:') !== false){
                    $arr['8: 其他'] += 1;
                    @$refuseData[$v]++;
                }
            }
        }
        arsort($arr);
        arsort($refuseData);
        $res = $detail = [];
        foreach ($arr as $k => $v){
            $item['refuse'] = $k;
            $item['num'] = $v;
            $res[] = $item;
        }
        foreach ($refuseData as $k => $v){
            $i['name'] = $k;
            $i['num'] = $v;
            $detail[] = $i;
        }
        return [
            'refuse' => $res,
            'detail' => $detail,
        ];
    }


    public static function actionImageSearch()
    {
        try {
            $condition = Yii::$app->request->post('condition');
            $imageUrl = $condition['imageUrl'];
            return ApiProductsEngine::imageSearch($imageUrl);
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }

    /**
     * 根据商品编码获取SKU信息
     * Date: 2019-12-06 9:03
     * Author: henry
     * @return array
     */
    public static function actionSkuInfo(){
        try {
            $condition = Yii::$app->request->post('condition');
            $goodsCode = $condition['goodsCode'];
            $sql = "EXEC P_KC_StockWarning '',0,0,'','0','{$goodsCode}','','',50000,1,'','1','','','','',268,-999999";
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }




}
