<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-08-15
 * Time: 16:49
 * Author: henry
 */

/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-08-15 16:49
 */


namespace console\controllers;


use backend\models\OaDataMineDetail;
use backend\modules\v1\models\ApiReport;
use yii\console\Controller;
use yii\db\Exception;
use yii\db\Query;
use Yii;

class TestController extends Controller
{

    public function actionSite()
    {
        try {
            //备份上月开发目标完成度 TODO  备份数据的加入
            $condition = [
                'dateFlag' => 1,
                'beginDate' => '2019-09-01',
                'endDate' => '2019-09-30',
                'seller' => '胡小红,廖露露,常金彩,刘珊珊,王漫漫,陈微微,杨笑天,李永恒,崔明宽,张崇,史新慈,邹雅丽,杨晶媛',
            ];
            $devList = ApiReport::getDevelopReport($condition);
            //print_r($devList);exit;
            foreach ($devList as $value){
                Yii::$app->db->createCommand()->insert(
                    'site_targetAllBackupData',
                   // ['username','role','profitZn','month','updateTime'],
                    [
                        'username' => $value['salernameZero'],
                        'role' => '开发',
                        'profitZn' => $value['netprofittotal'],
                        'month' => 9,
                        'updateTime' => '2019-10-09'
                    ]
                )->execute();
            }

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }


    public function actionTest()
    {
        $query = (new Query())->select('ebayName ebay,h.paypal big,l.paypal small')
            ->from('proCenter.oa_ebaySuffix es')
            ->leftJoin('proCenter.oa_paypal h', 'es.high=h.id')
            ->leftJoin('proCenter.oa_paypal l', 'es.low=l.id')->all();
        //print_r($query);exit;

        try {
            \Yii::$app->py_db->createCommand()->truncateTable('guest.t1')->execute();
            $res = \Yii::$app->py_db->createCommand()->batchInsert('guest.t1', ["ebay", "big", "small"], $query)->execute();
            print_r($res);
            echo "\r\n";
            //exit;

        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }


    }

    public function actionTest2(){   //默认ebay平台
        $devList = [
            '刘珊珊', '陈微微', '王漫漫', '杨笑天', '胡小红', '廖露露', '常金彩', '宋现中',
        ];
        try {
            $ret = [];
            $cateList = (new \yii\mongodb\Query())->from('ebay_new_product')
                //->andFilterWhere(['marketplace' => 'ebay'])
                ->distinct('marketplace');
            //->all();
            //print_r($cur);exit;
            foreach ($cateList as $value) {
                $cur = (new \yii\mongodb\Query())->from('ebay_new_product')
                    ->andFilterWhere(['marketplace' => $value])
                    ->all();
                list($isSetCat, $categoryArr) = ApiProductsEngine::getUserCate($userList, $value);
                foreach ($cur as $row) {
                    if (isset($row['accept']) && $row['accept'] ||    //过滤掉已经认领的产品
                        isset($row['refuse'][$username])       //过滤掉当前用户已经过滤的产品
                    ) {
                        continue;
                    } else {
                        if ($isSetCat == false) {
                            $ret[] = $row;
                        } else {
                            foreach ($categoryArr as $v) {
                                if (strpos($row['cidName'], $v) !== false) {
                                    $ret[] = $row;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            $data = new ArrayDataProvider([
                'allModels' => $ret,
                'sort' => [
                    'attributes' => ['price', 'visit', 'sold', 'listedTime'],
                    'defaultOrder' => [
                        'sold' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'page' => $page - 1,
                    'pageSize' => $pageSize,
                ],
            ]);
            return $data;


        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }


    }
















}