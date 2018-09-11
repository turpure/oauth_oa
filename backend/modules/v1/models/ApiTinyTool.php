<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;
use yii\helpers\ArrayHelper;

class ApiTinyTool
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
     * @brief get brand list
     * @param $condition
     * @return array
     */
    public static function getBrand($condition)
    {
        $con = \Yii::$app->py_db;
        $brand = ArrayHelper::getValue($condition,'brand','');
        $country = ArrayHelper::getValue($condition,'country','');
        $category = ArrayHelper::getValue($condition,'category','');
        $start = ArrayHelper::getValue($condition,'start',0);
        $limit = ArrayHelper::getValue($condition,'limit',20);
        $sql = "SELECT
            *
            FROM
            (
                SELECT
                    row_number () OVER (ORDER BY imgname) rowId,
                    brand,
                    country,
                    url,
                    category,
                    imgname
                FROM
                    Y_Brand
                WHERE
                brand LIKE '%$brand%' 
                and (country like '%$country%')
                and (category like '%$category%')
            ) bra
            where rowId BETWEEN $start and $limit";
        try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }
}