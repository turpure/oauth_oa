<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\modules\v1\utils\Handler;
use Yii;
use yii\data\ArrayDataProvider;
use yii\data\Sort;

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
        }
        catch (\Exception $why) {
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
                'attributes' => ['suffix', 'username', 'goodsName','goodsCode','lastNum','lastAmt','num','amt','numDiff','amtDiff'],
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
        }
        catch (\Exception $why) {
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
        if($condition['store']) {
            $store = str_replace(',', "','",$condition['store']);
            $sql .= " AND cw.suffix IN ('{$store}')";
        }
        if($condition['trendId']) {
            $tradeId = str_replace(',', "','",$condition['trendId']);
            $sql .= " AND cw.trendId IN ('{$tradeId}')";
        };
        if($condition['beginDate'] && $condition['endDate']) $sql .= " AND cw.orderCloseDate BETWEEN '{$condition['beginDate']}' AND '{$condition['endDate']}'";
        $con = Yii::$app->db;
        try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
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
        $nids = implode(',',$nidList);
        $sql = "EXEC oauth_updateOrderWeight '{$nids}'";

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $result = Yii::$app->py_db->createCommand($sql)->execute();
            if ($result === false) {
                $transaction->rollBack();
            }
            if($nidList){
                foreach ($nidList as $value){
                    $res = Yii::$app->py_db->createCommand("EXEC P_Fr_CalcShippingCostByNid {$value}")->execute();
                    if ($res === false) {
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
        foreach ($data as $v){
            if ($v['type'] == 'pie'){
                $pieData[] = [
                    'name' => $v['name'],
                    'value' => $v['value'],
                ];
            }else{
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
        $orderLineNum =  $skuLineNum  = [];
        $orderLineRate = $skuLineRate = [];
        $orderLineAvg = $skuLineAvg = [];
        foreach($data as $value){
            //订单价格饼图数据
            if($value['type'] == 'order'){
                $orderPie[] = ['name' => $value['flag'], 'value' => $value['orderNum']];
            }
            //SKu价格饼图数据
            if($value['type'] == 'sku'){
                $skuPie[] = ['name' => $value['flag'], 'value' => $value['orderNum']];
            }

            //订单价格区间订单数量线图数据
            if($value['type'] == 'orderTrend'){
                $orderLineNum[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'orderNum' => $value['orderNum']];
                $orderLineRate[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'rate' => $value['rate']];
            }

            //线形图时间数据 SKU价格区间SKU数量线图数据
            if($value['type'] == 'skuTrend'){
                $skuLineNum[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'orderNum' => $value['orderNum']];
                $skuLineRate[] = ['flag' => $value['flag'], 'orderDate' => $value['orderDate'], 'rate' => $value['rate']];
            }
            //每天平均订单价格数据
            if($value['type'] == 'orderAvg'){
                $orderLineAvg[] = ['orderDate' => $value['orderDate'], 'amtAvg' => $value['rate']];
            }

            //每天平均SKU单价数据
            if($value['type'] == 'skuAvg'){
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
    public static function getStockTurnoverInfo($condition){
        $par = [
            'username' => isset($condition['member']) ? $condition['member'] : [],
            'department' => isset($condition['depart']) ? $condition['depart'] : []
        ];
        $params = [
            'cate' => implode(',', $condition['cate'] ?? []),
            'subCate' => implode(',', $condition['cate'] ?? []),
            'goodsStatus' => implode(',', $condition['goodsStatus'] ?? []),
            'storeName' => implode(',', $condition['storeName'] ?? []),
            'lastPurchaseDateBegin' => $condition['lastPurchaseDate'][0] ?? '',
            'lastPurchaseDateEnd' => $condition['lastPurchaseDate'][1] ?? '',
            'devDateBegin' => $condition['devDate'][0] ?? '',
            'devDateEnd' => $condition['devDate'][1] ?? '',
            'unsoldDays' => $condition['unsoldDays'] ?? 0,
            'turnoverDays' => $condition['turnoverDays'] ?? 0,
        ];
        if($condition['dataType'] == 'developer'){
            $params['SalerName'] = implode(',', $condition['member'] ?? []);
            $params['suffix'] = '';
            $params['suffix'] = '';
            $sql = "EXEC oauth_goodsStockTurnover 0,'{$params['goodsStatus']}','{$params['cate']}','{$params['subCate']}',
            '{$params['lastPurchaseDateBegin']}','{$params['lastPurchaseDateEnd']}','{$params['devDateBegin']}',
            '{$params['devDateEnd']}','{$params['unsoldDays']}','{$params['turnoverDays']}',
            '{$params['SalerName']}','{$params['storeName']}';";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            return $data;
        }else{
            $params['SalerName'] = '';
            $paramsFilter = Handler::paramsHandler($par);
            $params['suffix'] = $paramsFilter['store'] ? implode(',', $paramsFilter['store']) : '';
            $sql = "EXEC oauth_goodsStockTurnover 0,'{$params['goodsStatus']}','{$params['cate']}','{$params['subCate']}',
            
            '{$params['lastPurchaseDateBegin']}','{$params['lastPurchaseDateEnd']}','{$params['devDateBegin']}',
            '{$params['devDateEnd']}','{$params['unsoldDays']}','{$params['turnoverDays']}',
            '','{$params['storeName']}','{$params['suffix']}';";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            return $data;
        }
    }

}
