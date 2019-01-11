<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 8:57
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\utils\Helper;

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
                WHERE depart NOT LIKE '%郑州分部%' AND role = '销售' AND isnull(display,0)=0
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
                WHERE  depart LIKE '%郑州分部%' AND role = '销售' AND isnull(display,0)=0
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
                WHERE role = '开发' AND isnull(display,0)=0
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
                     CASE WHEN sum([primary])=0 THEN 0 ELSE sum([amt])/sum([primary]) END AS primaryRate,
                     CASE WHEN sum([high])=0 THEN 0 ELSE sum([amt])/sum([high]) END AS highRate,
                     MAX(dateRate) AS dateRate,max(updatetime) as updatetime
                FROM oauth_target 
                WHERE  role = '销售'
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
        $ret = [];
        $salesRet = $db->createCommand($salesCheck,[':position'=>'销售'])->queryOne();
        if(!empty($salesRet)) {
            if(strpos($salesRet['department'],'郑州分部') !==false) {
                $ret[] = ['label'=>'郑州销售','name'=>'zhengzhou'];
                $ret[] = ['label'=>'所有部门','name'=>'depart'];
            }
            else {
               $ret[] = ['label'=>'上海销售','name'=>'shanghai'];
               $ret[] = ['label'=>'所有部门','name'=>'depart'];
            }
        }
        $devRet = $db->createCommand($salesCheck,[':position'=>'开发'])->queryOne();
        if(!empty($devRet)) {
            $ret[] = ['label'=>'所有开发','name'=>'developer'];
        }
        else {
            $ret[] = ['label'=>'上海销售','name'=>'shanghai'];
            $ret[] = ['label'=>'郑州销售','name'=>'zhengzhou'];
            $ret[] = ['label'=>'所有部门','name'=>'depart'];
            $ret[] = ['label'=>'所有开发','name'=>'developer'];
            }
        return Helper::arrayUnique($ret);
    }

//================================================================================================

    /**
     * 上海销售毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionProfit()
    {
        $sql = "SELECT * FROM site_profit 
                WHERE depart NOT LIKE '%郑州分部%' AND role = '销售' AND IFNULL(display,0)=0
                ORDER BY rate  DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }

    /**
     * 郑州销售毛利
     * Date: 2019-01-10 19:14
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */

    public function actionZzProfit()
    {
        $sql = "SELECT * FROM site_profit 
                WHERE depart LIKE '%郑州分部%' AND role = '销售' AND IFNULL(display,0)=0
                ORDER BY rate  DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }

    /**
     * 开发毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionDevProfit()
    {
        $sql = "SELECT * FROM site_profit 
                WHERE role = '开发' AND IFNULL(display,0)=0
                ORDER BY rate DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }


    /**
     * 部门毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionDepartProfit()
    {
        $sql = "SELECT depart,SUM(lastProfit) AS lastProfit,SUM(profit) AS profit,
                     CASE WHEN SUM(lastProfit)=0 THEN 0 ELSE SUM(profit)/SUM(lastProfit) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_profit 
                WHERE  role = '销售'
                GROUP BY depart
                ORDER BY rate DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }





}