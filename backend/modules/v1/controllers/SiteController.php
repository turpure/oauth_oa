<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 8:57
 */

namespace backend\modules\v1\controllers;


class SiteController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTool';


    /**
     * 获取非郑州销售目标
     * @return mixed
     */
    public function actionIndex()
    {
        $sql = "SELECT * FROM oauth_target 
                WHERE username NOT IN ('韩珍','六部') AND depart <> '郑州分部' AND role = '销售'
                ORDER BY primaryRate DESC";
        $query = \Yii::$app->py_db->createCommand($sql)->queryAll();
        return $query;
    }

    /**
     * 获取郑州销售目标
     * @return mixed
     */
    public function actionSales()
    {
        $sql = "SELECT * FROM oauth_target 
                WHERE  depart = '郑州分部' AND role = '销售'
                ORDER BY highRate DESC";
        $query = \Yii::$app->py_db->createCommand($sql)->queryAll();
        return $query;
    }
    /**
     * 获取开发目标
     * @return mixed
     */
    public function actionDevelop()
    {
        $sql = "SELECT * FROM oauth_target 
                WHERE role = '开发'
                ORDER BY primaryRate DESC";
        $query = \Yii::$app->py_db->createCommand($sql)->queryAll();
        return $query;
    }
    /**
     * 获取非郑州部门目标
     * @return mixed
     */
    public function actionDepart()
    {
        $sql = "SELECT depart,sum([primary]) AS [primary],sum([high]) AS [high],sum([amt]) AS [amt],
                     sum([amt])/sum([primary]) AS primaryRate,sum([amt])/sum([high]) AS highRate,
                     MAX(dateRate) AS dateRate,max(updatetime) as updatetime
                FROM oauth_target 
                WHERE depart <> '郑州分部' AND role = '销售'
                GROUP BY depart
                ORDER BY primaryRate DESC";
        $query = \Yii::$app->py_db->createCommand($sql)->queryAll();
        return $query;
    }

}