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

use backend\models\OaGoodsinfoExtendsStatus;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

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
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $roles = implode('',ApiUser::getUserRole($user));
        //$query = OaGoodsinfo::find()
        $query = (new Query())
            ->select("gi.id, goodsId, supplierID, storeID, gi.bgoodsId, stockDays, number, mid,filterType,
            description, supplierName,declaredValue,devDatetime, updateTime, picCompleteTime,goodsName, 
            aliasCnName, aliasEnName,packName, purchaser, gi.developer,season, goodsCode, completeStatus, 
            goodsStatus,dictionaryName, storeName, picUrl, requiredKeywords, randomKeywords, wishTags, 
            mapPersons,possessMan1, possessMan2,achieveStatus, attributeName,picStatus,isVar, gi.stockUp, 
            isLiquid, isPowder, isMagnetism, isCharged, wishPublish,headKeywords, tailKeywords,g.cate,g.subCate,
            CASE WHEN  INSTR(mapPersons,'" . $user .
                "')>0 AND (SELECT COUNT(1) FROM proCenter.oa_goodsinfoExtendsStatus ge WHERE ge.infoId=gi.id AND status='已推广' AND saler='" . $user .
                "')>0  THEN '已推广' WHEN  INSTR(mapPersons,'" . $user .
                "')>0 AND (SELECT COUNT(1) FROM proCenter.oa_goodsinfoExtendsStatus ges WHERE ges.infoId=gi.id AND saler='" . $user .
                "')=0 THEN '未推广' WHEN extendStatus is not null then extendStatus ELSE '未推广' END  AS extendStatus")
            ->from('proCenter.oa_goodsinfo gi')
            ->leftJoin('proCenter.oa_goods g','g.nid=goodsid');
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if(isset($condition['storeName'])) $query->andFilterWhere(['like', 'storeName', $condition['storeName']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['like', 'gi.stockUp', $condition['stockUp']]);
        if(isset($condition['wishPublish'])) $query->andFilterWhere(['like', 'wishPublish', $condition['wishPublish']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if(isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if(isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if(isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        if(isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if(isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);

        $query = ApiGoodsinfo::completedStatusFilter($query, $condition);
        $query = ApiGoodsinfo::forbidPlatFilter($query, $condition);

        if(isset($condition['isVar'])) $query->andFilterWhere(['like', 'isVar', $condition['isVar']]);
        if(isset($condition['goodsStatus'])) $query->andFilterWhere(['like', 'goodsStatus', $condition['goodsStatus']]);
        if(isset($condition['devDatetime']) && $condition['devDatetime']) $query->andFilterWhere(['between', 'devDatetime', $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if(isset($condition['updateDate']) && $condition['updateDate']) $query->andFilterWhere(['between', 'updateDate', $condition['updateDate'][0], $condition['updateDate'][1]]);
        //判断推广状态
        if(isset($condition['extendStatus']) && $condition['extendStatus'] == '已推广'){
            $query->andWhere([
                'OR',
                [
                    'AND',
                    ['extendStatus' => '已推广'],
                    ['not like', 'mapPersons', $user]
                ],
                [
                    'AND',
                    ['like', 'mapPersons', $user],
                    ['in', 'gi.id' ,OaGoodsinfoExtendsStatus::find()
                        ->select('infoId')
                        ->where(['saler' => $user, 'status' => '已推广'])],
                ]
            ]);
        }
        if(isset($condition['extendStatus']) && $condition['extendStatus'] == '未推广'){
            $query->andWhere([
                'OR',
                [
                    'AND',
                    ["IFNULL(extendStatus,'未推广')" => '未推广'],
                    ['not like', 'mapPersons', $user]
                ],
                [
                    'AND',
                    ['like', 'mapPersons', $user],
                    ['not exists', OaGoodsinfoExtendsStatus::find()
                             ->where( 'infoId=gi.id')
                            ->andWhere(['saler' => $user])
                    ]
                ],
            ]);
        }
        //判断是否为采集数据
        if(isset($condition['mid']) && $condition['mid'] == '是') $query->andFilterWhere(['>', 'mid', 0]);
        if(isset($condition['mid']) && $condition['mid'] == '否') $query->andFilterWhere(["IFNULL(mid,0)" => 0]);
        //备货天数
        if(isset($condition['stockDays'])) $query->andFilterWhere(['stockDays' => $condition['stockDays']]);
        //库存
        if(isset($condition['number'])) $query->andFilterWhere(['number' => $condition['number']]);

        //产品中心模块，去掉未完成的数据
        if ($param == 'product') {
            //print_r($param);exit;
            $query->andWhere(['<>', "IFNULL(completeStatus,'')", '']);
        }
        if($param == 'sales'){
            if (strpos($roles, '销售') !== false) {
                $map[0] = 'or';
                foreach ($userList as $k => $username) {
                    $map[$k + 1] = ['like', 'mapPersons', $username];
                }
                $query->andWhere($map);
            } elseif (strpos($roles, '开发') !== false) {
                $query->andWhere(['in', 'g.developer', $userList]);
                $query->andWhere(['<>', "IFNULL(mapPersons,'')", '']);
            }else{
                $map[0] = 'or';
                foreach ($userList as $k => $username) {
                    $map[$k + 1] = ['like', "IFNULL(mapPersons,'')", $username];
                }
                $query->andWhere($map);
            }
        }
        //Wish待刊登模块，只返回wish平台未完善数据
        if ($param == 'wish') {
            $query->andFilterWhere(['wishPublish' => 'Y']);
            $query->andFilterWhere(['not like', "IFNULL(dictionaryName,'')", 'wish']);
            $query->andFilterWhere(['not like', "IFNULL(completeStatus,'')", 'Wish']);
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

    /** 获取 备货产品/不备货产品
     * Date: 2019-05-15 11:08
     * Author: henry
     * @return array
     */

    public static function getStockData($param = 'stock'){
        try{
            $sql = "SELECT developer,number,orderNum,hotStyleNum,exuStyleNum,
                    CASE WHEN number=0 THEN 0 ELSE round(orderNum*1.0/number,2) END AS orderRate,
                    CASE WHEN number=0 THEN 0 ELSE round(hotStyleNum*1.0/number,2) END AS hotRate,
                    CASE WHEN number=0 THEN 0 ELSE round(exuStyleNum*1.0/number,2) END AS exuRate,
                    stockNumThisMonth,stockNumLastMonth,createDate
                    FROM proCenter.oa_stockGoodsNumReal 
                    WHERE isStock='{$param}' ORDER BY number DESC ";
            return Yii::$app->db->createCommand($sql)->queryAll();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }


    /** 类目表现
     * @param $condition
     * Date: 2019-03-08 9:00
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getCatPerformData()
    {
        $sql = 'P_oa_CategoryPerformance';
        $today = 'category-' . date('y-m-d');
        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        return $result;
    }

    /**
     * @param $condition
     * Date: 2019-07-31 16:18
     * Author: henry
     * @return ArrayDataProvider
     */
    public static function getCatDetailData($condition)
    {
        $data['type'] = $condition['dateFlag'];
        $data['cat'] = $condition['cat'];
        $data['order_start'] = $condition['orderDate'][0];
        $data['order_end'] = $condition['orderDate'][1];
        $data['create_start'] = (!empty($condition['devDate'])) ? $condition['devDate'] : '';
        $data['create_end'] = (!empty($condition['devDate'])) ? $condition['devDate'] : '';
        $sql = "EXEC P_oa_CategoryPerformance_demo " . $data['type'] . " ,'" . $data['order_start'] . "','" . $data['order_end'] . "','" . $data['create_start'] . "','" . $data['create_end'] . "','".$data['cat']."';";
        //P_oa_CategoryPerformance_demo 0 ,'2018-01-01','2018-01-23','',''
        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        //选择了主目录，重组结果数组
        if($data['cat']){
            foreach ($result as $v){
                $v['CategoryParentName'] = $v['CategoryName'];
                unset($v['CategoryName']);
                $list[] = $v;
            }
        }else{
            $list = $result;
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $list,
            'pagination' => [
                'pageSize' => false,
            ],
            'sort' => [
                'attributes' => ['catCodeNum', 'non_catCodeNum', 'numRate', 'l_qty', 'non_l_qty', 'qtyRate', 'l_AMT', 'non_l_AMT', 'amtRate'],
            ],
        ]);
        return $dataProvider;

    }


    /** 产品表现
     * @param $condition
     * Date: 2019-07-31 16:27
     * Author: henry
     * @return ArrayDataProvider
     */

    public static function getProductPerformData($condition)
    {
        $sql = "EXEC P_oa_ProductPerformance '{$condition['dateFlag']}','{$condition['orderBeginDate']}','{$condition['orderEndDate']}','{$condition['devBeginDate']}','{$condition['devEndDate']}','{$condition['salerName']}'";
        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        //var_dump($result);exit;
        $dataProvider = new ArrayDataProvider([
            'allModels' => $result,
            'pagination' => [
                'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
            ],
        ]);
        return $dataProvider;
    }

    /** 开发表现
     * @param $condition
     * Date: 2019-08-02 16:27
     * Author: henry
     * @return array
     */
    public static function getDevPerformData($condition){
        $sql = "EXEC P_oa_DeveloperPerformance " . $condition['dateFlag'] . " ,'" . $condition['orderBeginDate'] . "','" . $condition['orderEndDate'] . "','" . $condition['devBeginDate'] . "','" . $condition['devEndDate'] ."'";
        $orderData = Yii::$app->py_db->createCommand($sql)->queryAll();
        //获取开发员开发产品款数(不受订单影响)
        $numSql = "SELECT CASE WHEN ISNULL(SalerName,'')='' THEN '其他' ELSE salerName END AS salerName,count(GoodsCode) AS num FROM B_Goods b 
                    --LEFT JOIN [user] u ON u.username=b.SalerName WHERE u.username<>\'\'
                     ";
        if($condition['devBeginDate'] && $condition['devEndDate']){
            $numSql .= " AND CreateDate BETWEEN '" . $condition['devBeginDate'] . "' AND '" . $condition['devEndDate'] . "'";
        }
        $numSql .= ' GROUP BY SalerName';
        $devData = Yii::$app->py_db->createCommand($numSql)->queryAll();
        //print_r($devData);exit;
        return [
            'orderData' => $orderData,
            'devData' => $devData,
        ];
    }

    /**全球市场分析
     * @param $condition
     * Date: 2019-08-05 10:53
     * Author: henry
     * @return mixed
     */
    public static function getGlobalMarketData($condition){
        $sql = "P_oa_GlobalMarketAnalysis 0,'" . $condition['orderBeginDate'] . "','" . $condition['orderEndDate'] . "','".$condition['goodsCode'] . "','".$condition['plat'] . "','".$condition['suffix']. "'";
        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        return $result;
    }

    /** 销售产品表现
     * @param $condition
     * Date: 2019-08-07 13:37
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getSalesPerformData($condition)
    {
        $sql = "P_oa_sales_Performance '" . $condition['devBeginDate'] . "','" . $condition['devEndDate'] . "'";

        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        //普源数据插入临时表
        Yii::$app->db->createCommand()->truncateTable('cache_salesPerformTmp')->execute();
        Yii::$app->db->createCommand()->batchInsert('cache_salesPerformTmp',
            ['goodsCode','qty','devDate','goodsStatus','updateTime'],
            $result)->execute();
        $sql = "CALL data_getSalesPerform('{$condition['isStock']}','{$condition['devBeginDate']}','{$condition['devBeginDate']}','{$condition['saler']}');";
        $list = Yii::$app->db->createCommand($sql)->queryAll();
        return $list;
    }

    /** 备货产品库存
     * @param $condition
     * Date: 2019-08-06 15:50
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getStockPerformData($condition)
    {

        //获取普源商品信息
        $sql = "SELECT 
                b.goodsName,b.goodsCode,b.goodsStatus,b.salerName AS developer,CONVERT(VARCHAR(10),b.devDate,121) AS devDatetime,
                SUM(kc.Number) AS number, SUM(Money) AS money,SUM(SellCount1) AS sellCount1,
                SUM(SellCount2) AS sellCount2,SUM(sellCount3) AS sellCount3,GETDATE() AS updateTime
                FROM KC_CurrentStock kc 
                LEFT JOIN B_Goods b ON b.NID=kc.GoodsID 
                WHERE ISNULL(b.salerName,'')<>'' ";
        if($condition['salerName']) $sql .= " AND b.salerName LIKE '%{$condition['salerName']}%'";
        if($condition['goodsCode']) $sql .= " AND b.goodsCode LIKE '%{$condition['goodsCode']}%'";
        $sql .= " GROUP BY b.goodsName,b.goodsCode,b.goodsStatus,b.salerName,CONVERT(VARCHAR(10),b.devDate,121)";
        $result = Yii::$app->py_db->createCommand($sql)->queryAll();
        //普源数据插入临时表
        Yii::$app->db->createCommand()->truncateTable('cache_stockPerformTmp')->execute();
        Yii::$app->db->createCommand()->batchInsert('cache_stockPerformTmp',
            ['goodsName','goodsCode','goodsStatus','developer','devDatetime',
                'number', 'money','sellCount1','sellCount2','sellCount3','updateTime'],
            $result)->execute();
        //获取产品中心商品信息
        $sqlOa = "SELECT g.goodsCode,g.goodsName,g.developer,IFNULL(s.goodsStatus,'在售') AS goodsStatus,
                SUBSTRING(g.devDateTime,1,10) AS devDateTime,s.number,s.money,s.sellCount1,s.sellCount2,s.sellCount3
                FROM cache_stockPerformTmp s 
                LEFT JOIN  proCenter.oa_goodsinfo g ON s.goodsCode=g.goodsCode
                WHERE stockUp='是' ";
        if($condition['salerName']) $sqlOa .= " AND developer LIKE '%{$condition['salerName']}%'";
        if($condition['goodsCode']) $sqlOa .= " AND goodsCode LIKE '%{$condition['goodsCode']}%'";
        if($condition['devBeginDate'] && $condition['devEndDate']) $sqlOa .= " AND g.devDateTime BETWEEN '{$condition['devBeginDate']}' AND '{$condition['devEndDate']}'";
        $sqlOa .= " ORDER BY number DESC";
        $list = Yii::$app->db->createCommand($sqlOa)->queryAll();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $list,
            'pagination' => [
                'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
            ],
        ]);
        return $dataProvider;
    }



}