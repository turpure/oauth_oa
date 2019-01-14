<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

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
            return [$why];
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
        print_r($condition);exit;
        $updateSql = "oauth_salesChangeOfTwoDateBlock @lastBeginDate=:lastBeginDate,@lastEndDate=:lastEndDate,@beginDate=:beginDate,@endDate=:endDate ";
        $items = [
            ':lastBeginDate' => $condition['lastBeginDate'],
            ':lastEndDate' => $condition['lastEndDate'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate']
        ];
        $data = Yii::$app->py_db->createCommand($updateSql)->bindValues($items)->queryAll();

        //清空数据表并插入新数据
        Yii::$app->db->createCommand("TRUNCATE TABLE cache_sales_change")->execute();
        //更新cache_sales_change 表数据
        Yii::$app->db->createCommand()->batchInsert(
            'cache_sales_change',
            ['suffix','goodsCode','goodsName','lastNum','lastAmt','num','amt','numDiff','amtDiff','createDate'],
            $data
        )->execute();

        $sql = "SELECT username,sc.* FROM cache_sales_change sc
                LEFT JOIN auth_store s ON s.store=sc.suffix
                LEFT JOIN auth_store_child scc ON scc.store_id=s.id
                LEFT JOIN `user` u ON u.id=scc.user_id 
                WHERE 1=1 ";
        if ($condition['suffix']) $sql .= " AND sc.suffix IN(" . $condition['suffix'] . ') ';
        if ($condition['salesman']) $sql .= " AND u.username IN(" . $condition['salesman'] . ') ';
        if ($condition['goodsName']) $sql .= " AND sc.goodsName LIKE '%" . $condition['goodsName'] . "%'";
        if ($condition['goodsCode']) $sql .= " AND sc.goodsCode LIKE '%" . $condition['goodsCode'] . "%'";
        //$sql .= " ORDER BY numDiff DESC";
        $list = Yii::$app->db->createCommand($sql)->queryAll();
        $data = new ArrayDataProvider([
            'allModels' => $list,
            'sort' => [
                'defaultOrder' => ['numDiff' => SORT_DESC],
                'attributes' => ['suffix', 'salesman', 'goodsName','goodsCode','lastNum','lastAmt','num','amt','numDiff','amtDiff','createDate'],
            ],
            'pagination' => [
                'pageSize' => $condition['pageSize'],
            ],
        ]);
        return $data;
    }


}