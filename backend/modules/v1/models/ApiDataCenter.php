<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\models\User;
use backend\modules\v1\utils\Handler;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiDataCenter
{

    /**
     * @brief get express information
     * @return array
     */
    public static function express()
    {
        $con = \Yii::$app->py_db;
        $sql = "SELECT * FROM 
				(
				SELECT 
				m.NID, 
					DefaultExpress = ISNULL(
						(
							SELECT
								TOP 1 Name
							FROM
								T_Express
							WHERE
								NID = m.DefaultExpressNID
						),
						''
					),             -- 物流公司
					name,           --物流方式  --used,
					URL          --链接
					
				FROM
					B_LogisticWay m
				LEFT JOIN B_SmtOnlineSet bs ON bs.logicsWayNID = m.nid
				WHERE	
				used=0
				AND URL<>'') t
				ORDER BY t.DefaultExpress";
        try {
            return $con->createCommand($sql)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * 获取销售变化表（两个时间段对比）
     * @param $condition
     * Date: 2018-12-29 15:46
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getSalesChangeData($condition)
    {
        $params = [
            ':lastBeginDate' => $condition['lastBeginDate'],
            ':lastEndDate' => $condition['lastEndDate'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':salesman' => $condition['salesman'],
            ':goodsName' => $condition['goodsName'],
            ':goodsCode' => $condition['goodsCode'],
        ];
        $sql = "CALL data_salesChange(:lastBeginDate,:lastEndDate,:beginDate,:endDate,:suffix,:salesman,:goodsCode,:goodsName);";
        $list = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
        $data = new ArrayDataProvider([
            'allModels' => $list,
            'sort' => [
                'defaultOrder' => ['numDiff' => SORT_DESC],
                'attributes' => ['suffix', 'username', 'goodsName', 'goodsCode', 'lastNum', 'lastAmt', 'num', 'amt', 'numDiff', 'amtDiff'],
            ],
            'pagination' => [
                'pageSize' => $condition['pageSize'],
            ],
        ]);
        return $data;
    }


    /**
     * @param $condition
     * Date: 2019-02-19 14:55
     * Author: henry
     * @return array
     */
    public static function getPriceChangeData($condition)
    {
        $sql = 'exec oauth_priceChange :suffix,:beginDate,:endDate,:showType,:dateFlag';
        $con = Yii::$app->py_db;
        $params = [
            ':suffix' => $condition['store'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':showType' => $condition['showType'],
            ':dateFlag' => $condition['dateFlag'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


    /**
     * @param $condition
     * Date: 2019-02-21 14:18
     * Author: henry
     * @return array
     */
    public static function getWeightDiffData($condition)
    {
        $sql = "SELECT CASE WHEN IFNULL(pd.department,'')<>'' THEN pd.department ELSE d.department END AS department,
                CASE WHEN IFNULL(pd.department,'')<>'' THEN d.department ELSE '' END AS secDepartment,
                u.username,s.platform,cw.* 
                FROM cache_weightDiff cw
                LEFT JOIN auth_store s ON s.store=cw.suffix
                LEFT JOIN auth_store_child sc ON s.id=sc.store_id
                LEFT JOIN `user` u ON u.id=sc.user_id
                LEFT JOIN auth_department_child dc ON u.id=dc.user_id
                LEFT JOIN auth_department d ON d.id=dc.department_id
                LEFT JOIN auth_department pd ON pd.id=d.parent
                WHERE flag=0
                ";
        if ($condition['store']) {
            $store = str_replace(',', "','", $condition['store']);
            $sql .= " AND cw.suffix IN ('{$store}')";
        }
        if ($condition['trendId']) {
            $tradeId = str_replace(',', "','", $condition['trendId']);
            $sql .= " AND cw.trendId IN ('{$tradeId}')";
        };
        if ($condition['beginDate'] && $condition['endDate']) $sql .= " AND cw.orderCloseDate BETWEEN '{$condition['beginDate']}' AND '{$condition['endDate']}'";
        return Yii::$app->db->createCommand($sql)->queryAll();

    }


    /**
     * @param $condition
     * Date: 2019-02-22 10:37
     * Author: henry
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public static function updateWeight($condition)
    {
        $nidList = $condition['nid'];
        $nids = implode(',', $nidList);
        $sql = "EXEC oauth_updateOrderWeight '{$nids}'";

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $result = Yii::$app->py_db->createCommand($sql)->execute();
            if ($result === false) {
                $transaction->rollBack();
            }
            if ($nidList) {
                foreach ($nidList as $value) {
                    $res1 = Yii::$app->py_db->createCommand("EXEC P_Fr_CalcShippingCostByNid {$value}")->execute();
                    $res2 = Yii::$app->py_db->createCommand("EXEC P_Fr_CalcShippingCostByNidSend {$value}")->execute();
                    if ($res1 === false || $res2 === false) {
                        $transaction->rollBack();
                    }
                    $re = Yii::$app->db->createCommand("UPDATE cache_weightDiff SET flag=1 WHERE trendId={$value}")->execute();
                    if ($re === false) {
                        $transaction->rollBack();
                    }
                }
            }


            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }


    }

    /**
     * @param $condition
     * Date: 2019-03-01 10:54
     * Author: henry
     * @return array
     */
    public static function getDelayDeliveryData($condition, $flag = 0)
    {
        $sql = "EXEC oauth_delayDelivery :beginDate,:endDate,:suffix,:flag";
        $params = [
            ':suffix' => $condition['store'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':flag' => $flag,
        ];
        return Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();

    }

    public static function getDelayShipData($condition)
    {
        $sql = "EXEC oauth_delayShip :beginDate,:endDate,:suffix,:dateFlag";
        $params = [
            ':suffix' => $condition['store'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':dateFlag' => $condition['dateFlag'],
        ];
        $data = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
        $pieData = $barData = [];
        foreach ($data as $v) {
            if ($v['type'] == 'pie') {
                $pieData[] = [
                    'name' => $v['name'],
                    'value' => $v['value'],
                ];
            } else {
                $barData[] = [
                    'dt' => $v['dt'],
                    'name' => $v['name'],
                    'value' => $v['value'],
                ];
            }
        }
        return [
            'pieData' => $pieData,
            'barData' => $barData,
        ];
    }

    /**
     * @param $data
     * Date: 2019-02-22 15:06
     * Author: henry
     * @return array
     */
    public static function outputData($data)
    {
        //获取饼状图数据
        $pieName = array_unique(array_column($data, 'flag'));
        sort($pieName);
        //获取走势图时间数据
        $orderPie = $skuPie = [];
        $orderLineNum = $skuLineNum = [];
        $orderLineRate = $skuLineRate = [];
        $orderLineAvg = $skuLineAvg = [];
        foreach ($data as $value) {
            //订单价格饼图数据
            if ($value['type'] == 'order') {
                $orderPie[] = ['name' => $value['flag'], 'value' => $value['orderNum']];
            }
            //SKu价格饼图数据
            if ($value['type'] == 'sku') {
                $skuPie[] = ['name' => $value['flag'], 'value' => $value['orderNum']];
            }

            //订单价格区间订单数量线图数据
            if ($value['type'] == 'orderTrend') {
                $orderLineNum[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'orderNum' => $value['orderNum']];
                $orderLineRate[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'rate' => $value['rate']];
            }

            //线形图时间数据 SKU价格区间SKU数量线图数据
            if ($value['type'] == 'skuTrend') {
                $skuLineNum[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'orderNum' => $value['orderNum']];
                $skuLineRate[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'rate' => $value['rate']];
            }
            //每天平均订单价格数据
            if ($value['type'] == 'orderAvg') {
                $orderLineAvg[] = ['orderDate' => $value['orderDate'], 'amtAvg' => $value['rate']];
            }

            //每天平均SKU单价数据
            if ($value['type'] == 'skuAvg') {
                $skuLineAvg[] = ['orderDate' => $value['orderDate'], 'amtAvg' => $value['rate']];
            }
        }

        $result = [
            'orderPie' => [
                'legend' => $pieName,
                'data' => $orderPie,
            ],
            'skuPie' => [
                'legend' => $pieName,
                'data' => $skuPie,
            ],
            'orderLineNum' => $orderLineNum,
            'orderLineRate' => $orderLineRate,
            'skuLineNum' => $skuLineNum,
            'skuLineRate' => $skuLineRate,
            'orderLineAvg' => $orderLineAvg,
            'skuLineAvg' => $skuLineAvg,
        ];
        return $result;
    }

    /**
     * 获取库存周转数据
     * @param $condition
     * Date: 2021-03-03 16:43
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getStockTurnoverInfo($condition)
    {
        $params = [
            'cate' => implode(',', $condition['cate'] ?? []),
            'subCate' => implode(',', $condition['cate'] ?? []),
            'goodsStatus' => implode(',', $condition['goodsStatus'] ?? []),
            'storeName' => implode(',', $condition['storeName'] ?? []),
            'goodsCode' => $condition['goodsCode'] ?? '',
            'lastPurchaseDateBegin' => $condition['lastPurchaseDate'][0] ?? '',
            'lastPurchaseDateEnd' => $condition['lastPurchaseDate'][1] ?? '',
            'devDateBegin' => $condition['devDate'][0] ?? '',
            'devDateEnd' => $condition['devDate'][1] ?? '',
            'unsoldDays' => $condition['unsoldDays'] ?? 0,
            'turnoverDays' => $condition['turnoverDays'] ?? 0,
        ];
        if ($condition['dataType'] == 'developer') {
            $member = $condition['member'] ?: '';
            $depart = $condition['depart'];
            if($depart){
                $departSql = "SELECT DISTINCT u.username FROM `user` u
                    left Join auth_department_child dc ON dc.user_id=u.id
                    left Join auth_department d ON d.id=dc.department_id
                    left Join auth_department p ON p.id=d.parent
                    left Join auth_assignment a ON a.user_id=u.id
                    WHERE u.`status`=10 AND item_name = '产品开发' 
                    AND (p.department = '{$depart}' OR d.department = '{$depart}' ) ";
                $departUser = Yii::$app->db->createCommand($departSql)->queryAll();
                $userList = ArrayHelper::getColumn($departUser,'username');
                if ($member) $userList[] = $member;
                $member = implode(',', $userList);
            }

            $params['SalerName'] = $member;
            $params['suffix'] = '';
            $sql = "EXEC oauth_goodsStockTurnover 0,'{$params['goodsStatus']}','{$params['cate']}','{$params['subCate']}',
            '{$params['lastPurchaseDateBegin']}','{$params['lastPurchaseDateEnd']}','{$params['devDateBegin']}',
            '{$params['devDateEnd']}','{$params['unsoldDays']}','{$params['turnoverDays']}',
            '{$params['SalerName']}','{$params['storeName']}','{$params['goodsCode']}';";
        } else {
            $par = [
                'username' => isset($condition['member']) ? $condition['member'] : [],
                'department' => isset($condition['depart']) && $condition['depart'] ? [$condition['depart']] : []
            ];
            $params['SalerName'] = '';
            $suffixFilter = Handler::paramsParse($par);

            $params['suffix'] = implode(',', $suffixFilter);
            $sql = "EXEC oauth_goodsStockTurnover 1,'{$params['goodsStatus']}','{$params['cate']}','{$params['subCate']}',  
            '{$params['lastPurchaseDateBegin']}','{$params['lastPurchaseDateEnd']}','{$params['devDateBegin']}',
            '{$params['devDateEnd']}','{$params['unsoldDays']}','{$params['turnoverDays']}',
            '','{$params['storeName']}','{$params['goodsCode']}','{$params['suffix']}';";
        }
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 开发库存周转 明细
     * @param $condition
     * Date: 2021-03-06 10:08
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function getDeveloperStockTurnoverInfo($condition)
    {
        //获取所有销售员--账号 信息
        //$suffixFilter = Handler::paramsParse();
        $params = [
            'goodsCode' => $condition['goodsCode'] ?? '',
            'storeName' => $condition['storeName'] ?? '',
            'lastPurchaseDate' => $condition['lastPurchaseDate'] ?? '',
            //'suffix' => implode(',', $suffixFilter),
        ];
        //$sql = "EXEC oauth_salesData30DaysBeforeLastPurchaseDate '{$params['goodsCode']}','{$params['storeName']}',
        //       '{$params['lastPurchaseDate']}','{$params['suffix']}'";
        //$data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $sql = "CALL oauth_salesData30DaysBeforeLastPurchaseDate('{$params['goodsCode']}','{$params['storeName']}',
        '{$params['lastPurchaseDate']}')";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return $data;

    }


    /**
     * 获取价格保护信息
     * @param $condition
     * Date: 2021-03-08 16:43
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getPriceProtectionInfo($condition)
    {
        $saler = $condition['saler'] ?: [];
        $saler = is_array($saler) ? implode("','", $saler) : $saler;
        $plat = $condition['plat'] ?? '';
        $foulSaler = $condition['foulSaler'] ?? '';
        $foulSaler = is_array($foulSaler) ? implode("','", $foulSaler) : $foulSaler;
        //var_dump($saler);
        //var_dump($foulSaler);exit;
        $goodsStatus = implode("','", $condition['goodsStatus'] ?: ['爆款', '旺款']);
        if ($condition['dataType'] == 'priceProtection') {
            $sql = "SELECT DISTINCT goodsCode, mainImage, storeName, plat, saler, goodsName, goodsStatus, cate, subCate,
                    salerName, createDate, `number`, soldNum, personSoldNum, turnoverDays, rate 
                    FROM `cache_priceProtectionData` WHERE 1=1 ";
            if ($plat) $sql .= " AND plat IN ('{$plat}')";
            if ($goodsStatus) $sql .= " AND goodsStatus IN ('{$goodsStatus}')";
            if ($saler) $sql .= " AND saler IN ('{$saler}')";
        } elseif ($condition['dataType'] == 'priceProtectionError') {
            $sql = "SELECT * FROM `cache_priceProtectionData` WHERE IFNULL(foulSaler,'')<>'' ";
            if ($goodsStatus) $sql .= " AND goodsStatus IN ('{$goodsStatus}')";
            if ($plat) $sql .= " AND plat IN ('{$plat}')";
            if ($saler) $sql .= " AND saler IN ('{$saler}')";
            if ($foulSaler) $sql .= " AND foulSaler IN ('{$foulSaler}')";
        } else {
            $sql = "SELECT c.* FROM `cache_priceProtectionData` c
                    LEFT JOIN task_priceProtectionHandleLog l on c.goodsCode = l.goodsCode 
                                AND c.storeName=l.storeName AND c.foulSaler=l.foulSaler 
                    WHERE 1=1 AND (IFNULL(l.updateTime,'') = '' OR DATEDIFF(NOW(), l.updateTime) > 10)";
            if ($saler) $sql .= " AND saler IN ('{$saler}')";
            if ($foulSaler) $sql .= " AND c.foulSaler IN ('{$foulSaler}')";
        }

        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 亚马逊补货
     * @param $condition
     * Date: 2021-06-17 17:15
     * Author: henry
     * @return mixed
     */
    public static function getAmazonReplenishment($condition)
    {
        $username = Yii::$app->user->identity->username;
        $suffix = isset($condition['suffix']) && $condition['suffix'] ? (is_array($condition['suffix']) ? $condition['suffix'] : [$condition['suffix']]) : [];
        $params = [
            'username' => isset($condition['saler']) ? $condition['saler'] : ApiUser::getUserList($username),
            'store' => $suffix
        ];
        $suffixFilter = Handler::paramsParse($params);
        //var_dump(implode(',', $suffixFilter));exit;
        $sql = "EXEC guest.amazon_virtual_warehouse_replenishment :sku,:developer,:stockDaysDiff,
                        :totalStockDaysDiff,:suffix,:storeName,:shippingType";
        $params = [
            ':suffix' => implode(',', $suffixFilter),
            ':sku' => $condition['sku'],
            ':developer' => $condition['developer'],
            ':storeName' => $condition['storeName'] ?? '',
            ':stockDaysDiff' => (int)$condition['stockDaysDiff'],
            ':totalStockDaysDiff' => (int)$condition['totalStockDaysDiff'],
            ':shippingType' => $condition['shippingType'],
        ];
//        var_dump(Yii::$app->py_db->createCommand($sql)->bindValues($params)->getRawSql());exit;
        return Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();

    }


/////////////////////////////////////////供应商//////////////////////////////////////////////////

    /**
     * 获取开发员以及对应的部门和开发利率信息
     * Date: 2021-03-20 10:34
     * Author: henry
     * @return array
     */
    public static function getDeveloper()
    {
        $developer = (new Query())
            ->select(['u.username', 'depart' => new Expression('case when d.parent=0 then d.id else d.parent end')])
            ->from('auth_department_child as dc')
            ->leftJoin('auth_department as d', 'd.id=dc.department_id')
            ->leftJoin('user as u', 'u.id=dc.user_id')
            ->leftJoin('auth_assignment as a', 'u.id=a.user_id')
            ->where(['a.item_name' => '产品开发', 'u.status' => 10])->all();
        return $developer;
    }


    /**
     * getSupplierProfit
     * @param $condition
     * Date: 2021-03-22 15:42
     * Author: henry
     * @return mixed
     */
    public static function getSupplierProfit($condition)
    {
        $purchaseDateBegin = $condition['purchaseDate'][0] ?: '';
        $purchaseDateEnd = $condition['purchaseDate'][1] ?: '';
        $deliverDateBegin = $condition['deliverDate'][0] ?: '';
        $deliverDateEnd = $condition['deliverDate'][1] ?: '';
        $cate = implode(',', $condition['cate'] ?: []);
        $subCate = implode(',', ($condition['subCate'] ?: []));
        $purchaser = $condition['purchaser'] ?: '';
        $supplierName = $condition['supplierName'] ?: '';
        $supplierLevel = $condition['supplierLevel'] ?: '';
        $developer = ApiDataCenter::getDeveloper();
        $developerStr = [];
        foreach ($developer as $v) {
            $developerStr[] = $v['username'] . '/' . $v['depart'];
        }
        $developerStr = implode(',', $developerStr);
        $flag = $condition['flag'];
        $sql = "EXEC oauth_data_center_supplier_goods_profit '{$purchaseDateBegin}','{$purchaseDateEnd}',
                '{$deliverDateBegin}','{$deliverDateEnd}','{$purchaser}','{$supplierName}','{$supplierLevel}',
                '{$cate}','{$subCate}','{$developerStr}','{$flag}'";
        //var_dump($sql);exit;
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * getSupplierProfit
     * @param $condition
     * Date: 2021-03-22 15:42
     * Author: henry
     * @return mixed
     */
    public static function getSupplierProfitDetail($condition)
    {
        $supplierID = $condition['supplierID'] ?: '';
        $salerID = $condition['salerID'] ?: '';
        $purchaseDateBegin = $condition['purchaseDate'][0] ?: '';
        $purchaseDateEnd = $condition['purchaseDate'][1] ?: '';
        $deliverDateBegin = $condition['deliverDate'][0] ?: '';
        $deliverDateEnd = $condition['deliverDate'][1] ?: '';
        $developer = ApiDataCenter::getDeveloper();
        $developerStr = [];
        foreach ($developer as $v) {
            $developerStr[] = $v['username'] . '/' . $v['depart'];
        }
        $developerStr = implode(',', $developerStr);
        $sql = "EXEC oauth_data_center_supplier_goods_profit_detail '{$supplierID}','{$salerID}',
                '{$purchaseDateBegin}','{$purchaseDateEnd}','{$deliverDateBegin}','{$deliverDateEnd}','{$developerStr}' ";
        //var_dump($sql);exit;
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    public static function getSupplierProfitSummary($condition)
    {
        $deliverDateBegin = $condition['deliverDate'][0] ?: '';
        $deliverDateEnd = $condition['deliverDate'][1] ?: '';
        $profit = $condition['profit'] ?: 0;
        $supplierName = $condition['supplierName'] ?: '';
        $developer = ApiDataCenter::getDeveloper();
        $developerStr = [];
        foreach ($developer as $v) {
            $developerStr[] = $v['username'] . '/' . $v['depart'];
        }
        $developerStr = implode(',', $developerStr);
        $sql = "EXEC oauth_data_center_supplier_goods_profit_summary '{$deliverDateBegin}','{$deliverDateEnd}','{$supplierName}','{$profit}','{$developerStr}' ";
        //var_dump($sql);exit;
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * 获取供应商等级
     * @param $condition
     * Date: 2021-03-30 10:52
     * Author: henry
     */
    public static function getSupplierLevel($condition)
    {
        $sql = "SELECT s.nid AS supplierID,supplierName,linkMan,mobile,address,categoryName,
                    bd.DictionaryName AS supplierLevel,s.memo,LastPurchaseMoney
		FROM B_Supplier(nolock) s 
		LEFT JOIN B_SupplierCats(nolock) sc ON Sc.nid=s.categoryID
		LEFT JOIN B_Dictionary (nolock) bd ON s.categoryLevel = bd.NID AND bd.categoryID=32 
		WHERE LastPurchaseMoney > 0";
        if ($condition['supplierName']) $sql .= " AND supplierName LIKE '%{$condition['supplierName']}%' ";
        if ($condition['linkMan']) $sql .= " AND linkMan LIKE '%{$condition['linkMan']}%' ";
        if ($condition['categoryName']) $sql .= " AND categoryName LIKE '%{$condition['categoryName']}%' ";
        if ($condition['supplierLevel']) $sql .= " AND bd.DictionaryName LIKE '%{$condition['supplierLevel']}%' ";
        if ($condition['memo']) $sql .= " AND s.memo LIKE '%{$condition['memo']}%' ";
        return Yii::$app->py_db->createCommand($sql)->queryAll();

    }

    public static function  getEbayPayoutData($cond){
        $suffix = isset($cond['suffix']) ? $cond['suffix'] : [];
        $flag = isset($cond['flag']) ? $cond['flag'] : '';
        $used = isset($cond['used']) ? $cond['used'] : '';
        $isUsed = isset($cond['isUsed']) ? $cond['isUsed'] : '';
        $platUsed = isset($cond['platUsed']) ? $cond['platUsed'] : '';
        if ($suffix && !is_array($suffix)) $suffix = [$suffix];
        $suffix = implode("','", $suffix);
        $sql = "EXEC oauth_ebay_suffix_payout '{$cond['dateRange'][0]}','{$cond['dateRange'][1]}',
                '{$suffix}','{$flag}','{$used}','{$isUsed}','{$platUsed}' ";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }


}
