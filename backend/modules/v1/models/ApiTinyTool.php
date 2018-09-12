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

    /**
     * @brief get goods picture
     * @param $condition
     * @return array
     */
    public static function getGoodsPicture($condition)
    {
        $con = \Yii::$app->py_db;
        $salerName = ArrayHelper::getValue($condition,'salerName','');
        $possessMan1 = ArrayHelper::getValue($condition,'possessMan1','');
        $possessMan2 = ArrayHelper::getValue($condition,'possessMan2','');
        $beginDate = ArrayHelper::getValue($condition,'beginDate','')?:'1990-01-01';
        $endDate = ArrayHelper::getValue($condition,'endDate','')?:date('Y-m-d');
        $goodsName = ArrayHelper::getValue($condition,'goodsName','');
        $supplierName = ArrayHelper::getValue($condition,'supplierName','');
        $goodsSkuStatus = ArrayHelper::getValue($condition,'goodsSkuStatus','');
        $categoryParentName = ArrayHelper::getValue($condition,'categoryParentName','');
        $categoryName = ArrayHelper::getValue($condition,'categoryName','');
        $start = ArrayHelper::getValue($condition, 'start',0);
        $limit = ArrayHelper::getValue($condition,'limit',0);

        $sql = "SELECT
	    *
        FROM
        (
            SELECT
                row_number () OVER (ORDER BY bg.nid) AS rowId,
                bg.possessman1,
                bg.GoodsCode,
                bg.GoodsName,
                bg.CreateDate,
                bgs.SKU,
                bgs.GoodsSKUStatus,
                bgs.BmpFileName,
                bg.LinkUrl,
                bg.Brand,
                bgc.CategoryParentName,
                bgc.CategoryName
            FROM
                b_goods AS bg
            LEFT JOIN B_GoodsSKU AS bgs ON bg.NID = bgs.GoodsID
            LEFT JOIN B_GoodsCats AS bgc ON bgc.NID = bg.GoodsCategoryID
            LEFT JOIN B_Supplier bs ON bs.NID = bg.SupplierID
            WHERE
                bgs.SKU IN (
                    SELECT
                        MIN (bgs.SKU)
                    FROM
                        B_GoodsSKU AS bgs
                    GROUP BY
                        bgs.GoodsID
                )
            AND bs.SupplierName LIKE '%$supplierName%'
            AND bg.possessman1 LIKE '%$possessMan1%'
            AND bg.possessman2 LIKE '%$possessMan2%'
            AND bg.SalerName LIKE '%$salerName%'
            AND bg.CreateDate BETWEEN '$beginDate'
            AND '$endDate'
            AND bg.GoodsName LIKE '%$goodsName%'
            AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%'
            AND bgc.CategoryParentName LIKE '%$categoryParentName%'
            AND bgc.CategoryName LIKE '%$categoryName%'
        ) pic
        WHERE
        rowId BETWEEN $start
        AND $limit";
            try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }
}