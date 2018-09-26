<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;


use yii\helpers\ArrayHelper;

class ApiDataCenter
{
    /**
     * @brief get out of stock sku information
     */
    public static function outOfStockInfo ($condition) {
        $start = ArrayHelper::getValue($condition,'start',0);
        $limit = ArrayHelper::getValue($condition,'limit',20);
        $con = \Yii::$app->py_db;
        $sql = "SELECT * FROM(
                    SELECT 
                          row_number () OVER (ORDER BY goodscode) rowId,*
                     FROM oauth_outOfStockSkuInfo
                ) aa
                WHERE rowId BETWEEN {$start} AND {$limit}";
        try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

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
}