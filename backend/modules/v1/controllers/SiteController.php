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

    /**
     * @brief what I can see
     * @return mixed
     * @throws \Exception
     */
    public function actionPermission() {
        $user = \Yii::$app->user->identity->id;
        return $this->identity($user);
    }


    /**
     * @brief who are you
     * @param $userid
     * @return mixed
     * @throws \Exception
     */
    private function identity($userid) {
        $salesCheck = "SELECT department FROM auth_department AS ad
        LEFT JOIN auth_department_child AS adc ON ad.id = adc.department_id
        LEFT JOIN auth_position_child AS apc ON apc.user_id = adc.user_id
        LEFT JOIN auth_position AS ap ON ap.id = apc.position_id
        WHERE
        position=:position
        AND adc.user_id = $userid";
        $db = \Yii::$app->db;
        $salesRet = $db->createCommand($salesCheck,[':position'=>'销售'])->queryOne();
        if(!empty($salesRet)) {
            if($salesRet['department'] ==='郑州分部') {
                return [['label'=>'郑州销售','name'=>'zhengzhou']];
            }
            return [['label'=>'上海销售','name'=>'shanghai']];
        }
        else {
            $devRet = $db->createCommand($salesCheck,[':position'=>'开发'])->queryOne();
            if(!empty($devRet)) {
                return [['label'=>'所有开发','name'=>'developer']];
            }
            return [
                ['label'=>'上海销售','name'=>'shanghai'],
                ['label'=>'郑州销售','name'=>'zhengzhou'],
                ['label'=>'所有部门','name'=>'depart'],
                ['label'=>'所有开发','name'=>'developer']
            ];
        }

    }

}