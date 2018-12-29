<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use Yii;
use yii\data\ArrayDataProvider;

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
     * 获取销售变化表（连个时间段对比）
     * @param $condition
     * Date: 2018-12-29 15:46
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getSalesChangeData($condition){
        //获取本周一时间
        $day = date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
        $sql = "SELECT username,sc.* FROM cache_sales_change sc
                LEFT JOIN auth_store s ON s.store=sc.suffix
                LEFT JOIN auth_store_child scc ON scc.store_id=s.id
                LEFT JOIN `user` u ON u.id=scc.user_id
                WHERE createDate >= '{$day}' ORDER BY numDiff DESC";
        $list = Yii::$app->db->createCommand($sql)->queryAll();
        $data = new ArrayDataProvider([
            'allModels' => $list,
            'pagination' => [
                'pageSize' => $condition['pageSize'],
            ],
        ]);
        return $data;
    }


}