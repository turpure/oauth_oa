<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace console\models;


use backend\models\EbayAllotRule;
use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\utils\Handler;
use \Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ConScheduler
{

    /**
     * @param $startDate
     * @param $endDate
     * @param $dateRate
     * Date: 2019-06-13 18:26
     * Author: henry
     * @return bool
     * @throws Exception
     */
    public static function getZzTargetData($startDate, $endDate, $dateRate)
    {
        //获取时间段内销售毛利
        $sql = "SELECT u.username
                FROM `user` u
                 left Join auth_department_child dc ON dc.user_id=u.id
                 left Join auth_department d ON d.id=dc.department_id
                 left Join auth_department p ON p.id=d.parent
                left Join auth_assignment a ON a.user_id=u.id
                WHERE u.`status`=10 AND a.item_name='产品销售' 
                AND (p.department LIKE '郑州分部%' OR d.department LIKE '郑州分部%')";
        $userList = Yii::$app->db->createCommand($sql)->queryAll();
        $userList = ArrayHelper::getColumn($userList, 'username');
        $params = [
            'platform' => [],
            'username' => $userList,
            'store' => []
        ];
        $exchangeRate = ApiSettings::getExchangeRate();
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'dateType' => 1,
            'beginDate' => $startDate,
            'endDate' => $endDate,
            'queryType' => $paramsFilter['queryType'],
            'store' => implode(',', $paramsFilter['store']),
            'warehouse' => '',
            'exchangeRate' => $exchangeRate['salerRate']
        ];
        $profit = ApiReport::getSalesReport($condition);

        //获取需要统计的郑州销售列表
        $saleList = Yii::$app->db->createCommand("SELECT * FROM site_target")->queryAll();
        /*
        //备份上月数据
        $arr = [];
        foreach ($saleList as $v){
            $item1 = [];
            $saleMoneyUs = $profitZn = 0;
            foreach ($profit as $value){
                if($v['username'] == $value['salesman']){
                    $saleMoneyUs += $value['salemoney'];
                    $profitZn += $value['grossprofit'];
                }
            }
            $item1['username'] = $v['username'];
            $item1['saleMoneyUs'] = $saleMoneyUs;
            $item1['profitZn'] = $profitZn;
            $item1['month'] = (int)date('n',strtotime($startDate));
            $item1['updateTime'] = $endDate;
            $arr[] = $item1;
        }
        //print_r($arr);exit;
        //批量插入备份表
        $res = Yii::$app->db->createCommand()->batchInsert('site_target_backup_data',['username','saleMoneyUs','profitZn','month','updateTime'],$arr)->execute();
        print_r($res);exit;
*/
        foreach ($saleList as $v) {
            $item = $v;
            $amt = 0;
            foreach ($profit as $value) {
                if ($v['username'] == $value['salesman']) {
                    $amt += $value['grossprofit'];
                }
            }
            $item['amt'] = $amt;
            $item['rate'] = round($item['amt'] / $item['target'], 4);
            $item['dateRate'] = $dateRate;
            $item['updateTime'] = $endDate;
            $res = Yii::$app->db->createCommand()->update('site_target', $item, ['id' => $item['id']])->execute();
            if ($res === false) {
                throw new Exception('update data failed!');
            }
        }
        return true;
    }

    /** 获取开发每日推荐产品
     * @param $developers
     * @param $type
     * @param $plat
     * Date: 2019-11-08 15:13
     * Author: henry
     */
    public static function getDevelopRecommendProduct($developers, $type, $plat)
    {   //默认ebay平台
        $today = date('Y-m-d');
        $db = Yii::$app->mongodb;
        //获取分配规则最大产品数
        $maxProNum = (new \yii\mongodb\Query())->from('ebay_allot_rule')->max('productNum');
        if (!$maxProNum) $maxProNum = 5; //没有数据设置默认值
        for ($i = 1; $i <= $maxProNum; $i++) {  //循环5遍  ，每遍选一个产品
            //遍历开发员
            foreach ($developers as $value) {
                //判断当前开发所选产品数
                //从数据库结果集中查找当前开发所选产品数量
                $num = $db->getCollection('ebay_recommended_product')
                    ->count([
                        'productType' => $type,                               //新品推荐
                        'dispatchDate' => ['$regex' => $today],             //当天推荐
                        //'recommendDate' =>  ['$regex' => '2019-11-07'],
                        'receiver' => [$value]                               //当前开发的推荐
                    ]);

                //获取当前开发的分配产品数
                $devProNum = (new \yii\mongodb\Query())
                    ->select(['productNum'])
                    ->from('ebay_allot_rule')
                    ->andFilterWhere(['username' => $value])
                    ->scalar();
                $devProNum = 5;
                //print_r($devProNum);exit;
                if ($num >= $devProNum) { //已经选够五款产品,不再选产品
                    continue;
                }
                //开始选产品
                //开发员有类目限制，获取开发员的开发类目 产品，没有限制 获取全部产品
                $cateProList = self::getProductsByDeveloper($value, $type, $plat);
                //print_r(count($cateProList));exit;
                foreach ($cateProList as $v) {
                    //数据库结果集中查找 当前产品
                    $resItem = $db->getCollection('ebay_recommended_product')
                        ->findOne(['itemId' => $v['itemId']]);
                    if (!$resItem) { //所选产品没有其他人选过(不在结果集) 添加到结果集
                        $item = $v;
                        $item['productType'] = $type;   //类型
                        $item['dispatchDate'] = date('Y-m-d H:i:s');   //时间
                        $item['receiver'][] = $value;
                        unset($item['_id']);
                        $db->getCollection('ebay_recommended_product')->insert($item);
                        break;
                    } elseif (
                        strpos($resItem['dispatchDate'], $today) !== false && //选择时间是当天
                        $resItem['productType'] == $type &&                  //且是新品
                        count($resItem['receiver']) == 1 &&                   //只有一个人选
                        !in_array($value, $resItem['receiver'])               //不是当前开发选择
                    ) {  //只有其他一个人选过且不是当前开发所选时  更新结果集
                        $dev = $resItem['receiver'];
                        $dev[] = $value;// 先添加开发，再去重
                        $db->getCollection('ebay_recommended_product')->update(['_id' => $resItem['_id']], ['receiver' => $dev]);
                        break;
                    } else {              //否则继续判断下一个产品
                        continue;
                    }
                }
            }
        }

    }

    /** 获取开发员 符合产品类目的产品，没有产品类目则获取全部产品
     * @param $developer
     * @param string $type
     * @param string $plat
     * Date: 2019-11-08 15:11
     * Author: henry
     * @return array
     */
    public static function getProductsByDeveloper($developer, $type = 'new', $plat = 'ebay')
    {
        $db = Yii::$app->mongodb;
        //获取开发分配规则
        $allotList = $db->getCollection('ebay_allot_rule')
            ->find([
                'ruleType' => $type,
                'username' => $developer,
            ]);
        $allCateList = [];
        if ($allotList) {
            foreach ($allotList as $value) {
                $cateList = $db->getCollection('ebay_cate_rule')
                    ->find(['pyCate' => $value['category'], 'plat' => $plat]);
                if ($cateList) {
                    foreach ($cateList as $val) {
                        $item['marketplace'] = $val['marketplace'];
                        $item['cate'] = $val['cate'];
                        $item['subCate'] = $val['subCate'];
                        $allCateList[] = [];
                    }
                }
            }
        }
        $allCateList = array_unique($allCateList);
        $marketplaceArr = array_unique(ArrayHelper::getColumn($allCateList, 'marketplace'));
        //获取站点产品
        if($type === 'new') {
            $proList = (new \yii\mongodb\Query())->from('ebay_new_product')
                ->andFilterWhere(['marketplace' => $marketplaceArr])
                ->orderBy('sold DESC')->all();
        }
        else {
            $proList = (new \yii\mongodb\Query())->from('ebay_hot_product')
                ->andFilterWhere(['marketplace' => $marketplaceArr])
                ->orderBy('sold DESC')->all();

        }
        //匹配符合当前开发类目的产品
        $cateProList = [];
        if($allCateList){
            foreach ($proList as $v) {
                foreach ($allCateList as $val) {
                    if ($val['subCate'] && strpos($v['cidName'], $val['subCate']) !== false || //有子分类且 包含子分类
                        strpos($v['cidName'], $val['cate']) !== false  //没有子分类且 包含分类
                    ) {
                        $cateProList[] = $v;
                        break;
                    }
                }
            }
        }else{
            $cateProList = $proList;
        }
        return $cateProList;
    }

    /**
     * 获取推荐人列表
     * @return mixed
     */
    private static function getRecommendToPersons()
    {
        $mongodb = Yii::$app->mongodb;
        $table = 'ebay_recommended_product';
        $col = $mongodb->getCollection($table);
        $today = date('Y-m-d');
        $products = $col->find(['dispatchDate' => ['$regex' => $today]]);
        return $products;
    }

    /**
     * 为每日推荐列表设置推荐人
     * @param $developer
     * @param $productType
     * @param $itemId
     */
    private static function setRecommendToPersons($developer, $productType, $itemId)
    {
        $mongodb = Yii::$app->mongodb;
        $table = $productType === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $col = $mongodb->getCollection($table);
        $persons = ['name' =>$developer, 'status' => '', 'reason' => ''];
        $product = $col->findOne(['itemId' => $itemId]);
        $oldPersons = $product['recommendToPersons'];
        $currentPersons = static::insertOrUpdateRecommendToPersons($persons,$oldPersons);
        $col->update(['itemId' => $itemId], ['recommendToPersons' => $currentPersons]);

    }

    /**
     * 更新或新增推荐人
     * @param $persons
     * @param $oldPersons
     * @return array
     */
    private static function insertOrUpdateRecommendToPersons($persons,$oldPersons)
    {
        if(empty($oldPersons)) {
            $oldPersons[] = $persons;
        }
        else{
            $appendFlag = 1;
            foreach ($oldPersons as &$op) {
                if($op['name'] === $persons['name']) {
                    $op = $persons;
                    $appendFlag = 0;
                    break;
                }
            }
            if($appendFlag) {
                $oldPersons[] = $persons;
            }
        }
        return $oldPersons;

    }

    /**
     * 获取并更新每日推荐的推荐人
     */
    public static function getAndSetRecommendToPersons()
    {
       $products = static::getRecommendToPersons();
       foreach ($products as $recommendProduct) {
           $productType = $recommendProduct['productType'];
           $developers = $recommendProduct['receiver'];
           $itemId = $recommendProduct['itemId'];
           foreach ($developers as $dep) {
               static::setRecommendToPersons($dep, $productType, $itemId);
           }
       }
    }


    //=====================================================================================================
    //新分配规则

    public static function getProducts($type, $plat)
    {
        $mongodb = Yii::$app->mongodb;
        //获取开发的普源类目，发货地点，以及推荐产品数目
        $devRuleList = $devNunRuleList = [];
        $devList = $mongodb->getCollection('ebay_allot_rule')->find();
        foreach ($devList as $dev){
            if($dev['category']){
                $devRuleList[] = [
                    'username' => $dev['username'],
                    'productNum' => $dev['productNum'],
                    'pyCate' => $dev['category'],
                    'deliveryLocation' => $dev['deliveryLocation'],
                ];
            }else{
                $devNunRuleList[] = [
                    'username' => $dev['username'],
                    'productNum' => $dev['productNum'],
                    'pyCate' => [],
                    'deliveryLocation' => $dev['deliveryLocation'],
                ];
            }
        }
        //var_dump($devNunRuleList);exit;
        //先分配有普源分类开发
        self::allotProducts($devRuleList, $type, $plat);
        //后分配没有普源分类开发
        self::allotProducts($devNunRuleList, $type, $plat);
    }


    public static function allotProducts($developers, $type, $plat)
    {
        $db = Yii::$app->mongodb;
        $table = $type == 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        //获取产品数
        $maxProNum = (new \yii\mongodb\Query())->from($table)
            ->andFilterWhere(['recommendDate' => ['$regex' => date('Y-m-d')]])  //筛选当天获取得新数据
            ->count();
        $cycle = ceil($maxProNum/count($developers));
        //var_dump($cycle);exit;
        for ($i = 1; $i <= $cycle; $i++) {  //每遍选一个产品
            shuffle($developers);//开发员随机排序
            foreach ($developers as $dev) {
                //获取类目产品，没有类目获取全部产品
                $proList = [];

                if($dev['pyCate']){
                    foreach ($dev['pyCate'] as $cate){
                        $list = (new \yii\mongodb\Query())->from($table)
                            ->andFilterWhere(['tag' => $cate])
                            ->andFilterWhere(['isReceiver' => null])
                            ->andFilterWhere(['recommendDate' => ['$regex' => date('Y-m-d')]])  //筛选当天获取得新数据
                            ->orderBy('sold DESC')->all();
                        $proList = array_merge($proList,$list);
                    }
                }else{
                    $proList = (new \yii\mongodb\Query())->from($table)
                        ->andFilterWhere(['isReceiver' => null])
                        ->andFilterWhere(['recommendDate' => ['$regex' => date('Y-m-d')]])  //筛选当天获取得新数据
                        ->orderBy('sold DESC')->all();
                }
                //print_r(count($proList));exit;
                //开始分产品
                foreach ($proList as $v) {
                    //判断产品发货地点是否匹配
                    if($dev['deliveryLocation']){
                        $flag = 0;
                        foreach ($dev['deliveryLocation'] as $deliveryLocation){
                            if(isset(self::$locationCountryName[$deliveryLocation]) &&
                                strpos($v['itemLocation'], self::$locationCountryName[$deliveryLocation]) !== false
                            ){
                                break;
                            }else{
                                $flag++;
                            }
                        }
                        // 产品发货地址 都不在开发员限定的发货地址列表时，跳出当前循环，判断下一个产品
                        if($flag == count($dev['deliveryLocation'])){
                            continue;
                        }
                    }
                    //数据库结果集中查找 当前产品,判断是否选过或超过选择人数
                    $resItem = $db->getCollection('ebay_all_recommended_product')
                        ->findOne(['itemId' => $v['itemId']]);
                    if (!$resItem) { //所选产品没有其他人选过(不在结果集) 添加到结果集
                        $item = $v;
                        $item['productType'] = $type;   //类型
                        $item['dispatchDate'] = date('Y-m-d H:i:s');   //时间
                        $item['receiver'][] = $dev['username'];
                        unset($item['_id']);
                        $db->getCollection('ebay_all_recommended_product')->insert($item);
                        break;
                    } elseif (
                        strpos($resItem['dispatchDate'], date('Y-m-d')) !== false && //选择时间是当天
                        $resItem['productType'] == $type &&                  //且是新品
                        count($resItem['receiver']) == 1 &&                   //只有一个人选
                        !in_array($dev['username'], $resItem['receiver'])               //不是当前开发选择
                    ) {  //只有其他一个人选过且不是当前开发所选时  更新结果集
                        $receiver = $resItem['receiver'];
                        $receiver[] = $dev['username'];// 先添加开发，再去重
                        //更新推荐列表推荐人信息
                        $db->getCollection('ebay_all_recommended_product')->update(['_id' => $resItem['_id']], ['receiver' => $receiver]);
                        //更新产品列表是否推荐字段信息
                        $db->getCollection($table)->update(['itemId' => $resItem['itemId']], ['isReceiver' => 1]);
                        break;
                    } else {              //否则继续判断下一个产品
                        //更新产品列表是否推荐字段信息
                        $db->getCollection($table)->update(['itemId' => $resItem['itemId']], ['isReceiver' => 1]);
                    }
                }
            }
        }
    }




    public static $locationCountryName = [
        '中国' => 'CN',
        '香港' => 'HK',
        '美国' => 'US',
        '英国' => 'GB',
        '法国' => 'FR',
        '德国' => 'DE',
        '荷兰' => 'NL',
        '爱尔兰' => 'IE',
        '加拿大' => 'CA',
        '意大利' => 'IT',
        '澳大利亚' => 'AU',
    ];




}