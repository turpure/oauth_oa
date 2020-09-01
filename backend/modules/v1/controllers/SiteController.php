<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 8:57
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\utils\Helper;
use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;


class SiteController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTool';


    /**
     * @brief 获取分部的部门
     * @return array
     */
    public function actionBranchDepart()
    {
        try {

            $sql = "select  DISTINCT department from auth_department where parent=0 AND department like '郑州%'" ;
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($query,'department');
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /**
     * @brief 获取总部的运营部门
     * @return array
     */
    public function actionHeadDepart()
    {
        try {
            $sql = "select  DISTINCT department from auth_department where parent=0 AND type LIKE '业务%' and department NOT like '郑州%'" ;
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($query,'department');
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }

    }

    /**
     * @brief 获取所有部门
     * @return array
     */
    public function actionAllDepart()
    {
        $head = $this->actionHeadDepart();
        $branch = $this->actionBranchDepart();
        return array_merge($head, $branch);
    }


    /** 获取所有销售(部门)目标列表
     * Date: 2019-08-28 9:46
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionIndex()
    {
        $username = Yii::$app->user->identity->username;
        $sql = "SELECT u.avatar,st.*,CASE WHEN amt-target>0 AND role='销售' THEN floor((amt-target)/2000)*100 ELSE 0 END AS rxtraBonus 
                FROM site_targetAll st
                LEFT JOIN `user` u ON st.username=u.username
                WHERE display<>1 ORDER BY st.username='{$username}' DESC,rate DESC";

        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }


    /** 完成销售目标数据统计
     * Date: 2019-08-29 11:42
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionSales()
    {

        $username = Yii::$app->user->identity->username;
        $sql = "SELECT st.username,u.avatar,st.bonus,st.vacationDays,
                CASE WHEN role='销售' AND amt-target>0 THEN floor((amt-target)/2000)*100 
                     WHEN role='开发' AND amt-target>0 THEN floor((amt-target)/5000)*100 
                    ELSE 0 END AS rxtraBonus
                FROM site_targetAll st
                LEFT JOIN `user` u ON st.username=u.username
                WHERE display<>1 AND rate>=100
                ORDER BY st.username='{$username}' DESC,rate DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();

        $bonusUsedNum = Yii::$app->db->createCommand("SELECT sum(bonus) AS bonus FROM site_targetAll WHERE role<>'部门' AND display<>1 AND rate>=100")->queryOne();
        $bonusAllNum = Yii::$app->db->createCommand("SELECT sum(bonus) AS bonus FROM site_targetAll WHERE role<>'部门' AND display<>1")->queryOne();

        $vacationDaysUsedNum = Yii::$app->db->createCommand("SELECT sum(vacationDays) AS vacationDays FROM site_targetAll WHERE role<>'部门' AND display<>1 AND rate>=100")->queryOne();
        $vacationDaysAllNum = Yii::$app->db->createCommand("SELECT sum(vacationDays) AS vacationDays FROM site_targetAll WHERE role<>'部门' AND display<>1")->queryOne();
        $dateRate = Yii::$app->db->createCommand("SELECT dateRate FROM site_targetAll limit 1")->queryScalar();

        return [
            'list' => $query,
            'dateRate' => $dateRate,
            'bonusAllNum' => $bonusAllNum['bonus'],
            'bonusUsedNum' => $bonusUsedNum['bonus'],
            'bonusUnUsedNum' => $bonusAllNum['bonus'] - $bonusUsedNum['bonus'],
            'vacationDaysAllNum' => $vacationDaysAllNum['vacationDays'],
            'vacationDaysUsedNum' => $vacationDaysUsedNum['vacationDays'],
            'vacationDaysUnUsedNum' => $vacationDaysAllNum['vacationDays'] - $vacationDaysUsedNum['vacationDays'],
        ];
    }

    /**
     * 获取开发目标
     * @return mixed
     */
    public function actionDevelop()
    {
        $condition = Yii::$app->request->post()['condition'];
        $depart = isset($condition['depart']) ? $condition['depart'] : '';
        if(empty($depart)) {
            $sql = "SELECT * FROM oauth_target 
                WHERE role = '开发' AND isnull(display,0)=0
                ORDER BY primaryRate DESC";
        }
        else {
            $sql = "SELECT * FROM oauth_target 
                WHERE depart = '{$depart}' and role = '开发'
                ORDER BY primaryRate DESC";
        }

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
                $ret[] = ['label'=>'销售排名','name'=>'ranking'];
                $ret[] = ['label'=>'利润增长表','name'=>'profit'];
                $ret[] = ['label'=>'销售额增长表','name'=>'sale'];
                $ret[] = ['label'=>'郑州分部魔表完成度','name'=>'target'];
            }
            else {
                $ret[] = ['label'=>'销售排名','name'=>'ranking'];
                $ret[] = ['label'=>'利润增长表','name'=>'profit'];
                $ret[] = ['label'=>'销售额增长表','name'=>'sale'];
            }
        }else {
            $ret[] = ['label'=>'销售排名','name'=>'ranking'];
            $ret[] = ['label'=>'利润增长表','name'=>'profit'];
            $ret[] = ['label'=>'销售额增长表','name'=>'sale'];
            $ret[] = ['label'=>'郑州分部魔表完成度','name'=>'target'];
        }
        return Helper::arrayUnique($ret);
    }

//=====================  Profit Changes  ============================

    /**
     * 所有销售毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     */
    public function actionProfit()
    {

        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            $sql = "SELECT * FROM site_profit 
                WHERE role = '销售' AND ifnull(display,0)=0 ";
            if($depart) {
                $sql .= " AND depart = '{$depart}' ";
            }
            $sql .= " ORDER BY Rate DESC";
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }

    }

    /**
     * 郑州销售毛利
     * Date: 2019-01-10 19:14
     * Author: henry
     * @return array
     */

    public function actionZzProfit()
    {

        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT * FROM site_profit 
                WHERE depart LIKE '%郑州%' AND role = '销售' AND IFNULL(display,0)=0
                ORDER BY Rate DESC";
            }
            else {
                $sql = "SELECT * FROM site_profit 
                WHERE depart = '{$depart}' AND role = '销售'
                ORDER BY Rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /**
     * 开发毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     */
    public function actionDevProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT * FROM site_profit 
                WHERE  role = '开发' AND ifnull(display,0)=0
                ORDER BY Rate DESC";
            }
            else {
                $sql = "SELECT * FROM site_profit 
                WHERE depart = '{$depart}' AND role = '开发'
                ORDER BY Rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    /**
     * 部门毛利
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     */
    public function actionDepartProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT depart,SUM(lastProfit) AS lastProfit,SUM(profit) AS profit,
                     CASE WHEN SUM(lastProfit)=0 THEN 0 
                          WHEN SUM(lastProfit)<0 THEN 1
                          ELSE SUM(profit)/SUM(lastProfit) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_profit 
                WHERE  role = '销售'
                GROUP BY depart
                ORDER BY rate DESC";
            }
            else {
                $sql = "SELECT depart,SUM(lastProfit) AS lastProfit,SUM(profit) AS profit,
                     CASE WHEN SUM(lastProfit)=0 THEN 0 
                          WHEN SUM(lastProfit)<0 THEN 1
                          ELSE SUM(profit)/SUM(lastProfit) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_profit 
                WHERE  role = '销售' and depart = '{$depart}'
                GROUP BY depart
                ORDER BY rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /**
     * 今日爆款
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionPros()
    {
        $plat = \Yii::$app->request->get('plat','eBay-义乌仓');
        $sql = "SELECT * FROM site_goods 
                WHERE  platform = '{$plat}'
                ORDER BY profit DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }


    //=====================  Amt Changes  ============================

    /**
     * 所有销售额
     * Date: 2019-04-16 09:38
     * Author: henry
     * @return mixed
     */
    public function actionAmt()
    {

        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            $sql = "SELECT * FROM site_sales_amt
                WHERE role = '销售' AND ifnull(display,0)=0 ";
            if($depart) {
                $sql .= "AND depart = '{$depart}' ";
            }
            $sql .= " ORDER BY Rate DESC";
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /**
     * 郑州销售额
     * Date: 2019-04-16 09:14
     * Author: henry
     * @return array
     */

    public function actionZzAmt()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT * FROM site_sales_amt 
                WHERE depart LIKE '%郑州%' AND role = '销售' AND IFNULL(display,0)=0
                ORDER BY Rate DESC";
            }
            else {
                $sql = "SELECT * FROM site_sales_amt
                WHERE depart = '{$depart}' AND role = '销售'
                ORDER BY Rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /**
     * 开发销售额
     * Date: 2019-04-16 10:38
     * Author: henry
     * @return mixed
     */
    public function actionDevAmt()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT * FROM site_sales_amt 
                WHERE  role = '开发' AND ifnull(display,0)=0  AND (lastAmt<>0 OR amt <> 0)
                ORDER BY Rate DESC";
            }
            else {
                $sql = "SELECT * FROM site_sales_amt
                WHERE depart = '{$depart}' AND role = '开发'
                ORDER BY Rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    /**
     * 部门销售额
     * Date: 2019-04-16 10:38
     * Author: henry
     * @return mixed
     */
    public function actionDepartAmt()
    {
        $sql = "SELECT depart,SUM(lastAmt) AS lastAmt,SUM(amt) AS amt,
                     CASE WHEN SUM(lastAmt)=0 THEN 0 ELSE SUM(amt)/SUM(lastAmt) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_sales_amt
                WHERE  role = '销售'
                GROUP BY depart
                ORDER BY rate DESC";
//        $query = \Yii::$app->db->createCommand($sql)->queryAll();
//        return $query;

        try {
            $condition = Yii::$app->request->post()['condition'];
            $depart = isset($condition['depart']) ? $condition['depart'] : '';
            if(empty($depart)) {
                $sql = "SELECT depart,SUM(lastAmt) AS lastAmt,SUM(amt) AS amt,
                     CASE WHEN SUM(lastAmt)=0 THEN 0 ELSE SUM(amt)/SUM(lastAmt) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_sales_amt
                WHERE  role = '销售'
                GROUP BY depart
                ORDER BY rate DESC";
            }
            else {
                $sql = "SELECT depart,SUM(lastAmt) AS lastAmt,SUM(amt) AS amt,
                     CASE WHEN SUM(lastAmt)=0 THEN 0 ELSE SUM(amt)/SUM(lastAmt) END AS rate,
                     MAX(dateRate) AS dateRate,MAX(updateTime) as updateTime
                FROM site_sales_amt
                WHERE  role = '销售' and depart = '{$depart}'
                GROUP BY depart
                ORDER BY rate DESC";
            }
            $query = \Yii::$app->db->createCommand($sql)->queryAll();
            return $query;
        }
        catch (\Exception $why) {
            return ['msg' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    /**
     * 销售排名
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionSalesRanking()
    {
        $plat = \Yii::$app->request->get('plat','eBay-义乌仓');
        $sql = "SELECT * FROM cache_siteSalesRanking 
                WHERE  platform = '{$plat}'
                ORDER BY thisProfit DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }

//==================================================================

    /**
     * 郑州6-8月销售目标完成度
     * Date: 2019-06-14 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionZzTarget()
    {
        $plat = \Yii::$app->request->get('plat','eBay');
        //获取当月数据
        $sql = "SELECT * FROM site_target
                WHERE  plat = '{$plat}'
                ORDER BY rate DESC";
        $list = \Yii::$app->db->createCommand($sql)->queryAll();

        //获取前几月数据
        $sql = "SELECT username,sum(saleMoneyUs) AS saleMoneyUs,sum(profitZn) AS profitZn
                FROM site_target_backup_data
                GROUP BY username";
        $arr = \Yii::$app->db->createCommand($sql)->queryAll();

        $data = [];
        foreach($list as $v){
            $item = $v;
            $amt = 0;
            foreach ($arr as $val){
                if($v['username'] == $val['username']){
                    $item['amt'] = $v['amt'] + $val['profitZn'];
                    break;
                }
            }
            $item['rate'] = round($item['amt']/$item['target'],4);
            $data[] = $item;
        }

        return $data;
    }


    /**
     * 仓库积分排行
     * Date: 2019-01-10 18:38
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionIntegralRanking()
    {
        $job = \Yii::$app->request->get('job','');
        $type = \Yii::$app->request->get('type','all');
        if($type != 'all' && $job == '') return [
            'code' => '400',
            'message' => 'job can not be empty!',
        ];

        $sql = "SELECT * FROM site_warehouse_integral_ranking 
            WHERE  type='{$type}' ";
        if($job) $sql .= " AND job = '{$job}' ";
        $sql .= " ORDER BY this_total_num DESC";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;

    }


}
