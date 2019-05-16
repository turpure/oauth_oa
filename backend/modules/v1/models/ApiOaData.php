<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-06
 * Time: 10:53
 * Author: henry
 */
/**
 * @name ApiOaData.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-06 10:53
 */


namespace backend\modules\v1\models;

use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

class ApiOaData
{

    /**
     * @param $condition
     * Date: 2019-03-07 16:51
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaData($condition, $param = null)
    {
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $roles = implode('',ApiUser::getUserRole($user));
        //$query = OaGoodsinfo::find()
        $query = (new Query())
            ->select('gi.*,g.cate,g.subCate')
            ->from('proCenter.oa_goodsinfo gi')
            ->leftJoin('proCenter.oa_goods g','g.nid=goodsid');
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if(isset($condition['storeName'])) $query->andFilterWhere(['like', 'storeName', $condition['storeName']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['like', 'stockUp', $condition['stockUp']]);
        if(isset($condition['wishPublish'])) $query->andFilterWhere(['like', 'wishPublish', $condition['wishPublish']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if(isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if(isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if(isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if(isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);
        if(isset($condition['completeStatus'])) $query->andFilterWhere(['like', 'completeStatus', $condition['completeStatus']]);
        if(isset($condition['dictionaryName'])) $query->andFilterWhere(['like', 'dictionaryName', $condition['dictionaryName']]);
        if(isset($condition['isVar'])) $query->andFilterWhere(['like', 'isVar', $condition['isVar']]);
        if(isset($condition['goodsStatus'])) $query->andFilterWhere(['like', 'goodsStatus', $condition['goodsStatus']]);
        if(isset($condition['devDatetime']) && $condition['devDatetime']) $query->andFilterWhere(['between', 'devDatetime', $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if(isset($condition['updateDate']) && $condition['updateDate']) $query->andFilterWhere(['between', 'updateDate', $condition['updateDate'][0], $condition['updateDate'][1]]);
        //判断推广状态
        if(isset($condition['extendStatus']) && $condition['extendStatus'] == '已推广'){
            $query->andFilterWhere(['like', 'extendStatus', $condition['extendStatus']]);
        }//TODO
        //判断是否为采集数据
        if(isset($condition['mid'])){
            if($condition['mid'] == '是'){
                $query->andFilterWhere(['>', 'mid', 0]);
            }else {
                $query->andFilterWhere(["IFNULL(mid,0)" => 0]);
            }
        }
        //备货天数
        if(isset($condition['stockDays'])) $query->andFilterWhere(['stockDays' => $condition['stockDays']]);
        //库存
        if(isset($condition['number'])) $query->andFilterWhere(['number' => $condition['number']]);

        //产品中心模块，去掉未完成的数据
        if ($param == 'product') {
            //print_r($param);exit;
            $query->andWhere(['<>', "IFNULL(completeStatus,'')", '']);
        }
        if($param == 'sales'){
            if (strpos($roles, '销售') !== false) {
                $map[0] = 'or';
                foreach ($userList as $k => $username) {
                    $map[$k + 1] = ['like', 'mapPersons', $username];
                }
                $query->andWhere($map);
            } elseif (strpos($roles, '开发') !== false) {
                $query->andWhere(['in', 'g.developer', $userList]);
                $query->andWhere(['<>', "IFNULL(mapPersons,'')", '']);
            }else{
                $map[0] = 'or';
                foreach ($userList as $k => $username) {
                    $map[$k + 1] = ['like', 'mapPersons', $username];
                }
                $query->andWhere($map);
            }
        }
        //Wish待刊登模块，只返回wish平台未完善数据
        if ($param == 'wish') {
            $query->andFilterWhere(['wishPublish' => 'Y']);
            $query->andFilterWhere(['not like', "IFNULL(dictionaryName,'')", 'wish']);
            $query->andFilterWhere(['not like', "IFNULL(completeStatus,'')", 'Wish']);
        }
        
        $query->orderBy('id DESC');
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /** 获取 备货产品/不备货产品
     * Date: 2019-05-15 11:08
     * Author: henry
     * @return array
     */

    public static function getStockData($param = 'stock'){
        try{
            return Yii::$app->db->createCommand("SELECT * FROM proCenter.oa_stockGoodsNumReal WHERE isStock='{$param}' ORDER BY number DESC ")->queryAll();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * @param $condition
     * Date: 2019-03-08 9:00
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getCatPerformData()
    {
        $sql = 'P_oa_CategoryPerformance';
        //$cache = Yii::$app->local_cache;
        $today = 'category-' . date('y-m-d');
       // $ret = $cache->get($today);
        //if (!empty($ret)) {
          //  $result = $ret;
        //} else {
            $result = Yii::$app->py_db->createCommand($sql)->queryAll();
          //  $cache->set($today, $result, 86400);
        //}
        foreach ($result as $key => $value) {
            if ($value['Distinguished'] == 'catNum') {
                $va['value'] = (int)$value['value'];
                $va['name'] = $value['name'];
                $Data['catNum'][] = $va;
            } else {
                $va['value'] = (int)$value['value'];
                $va['name'] = $value['name'];
                $Data['catAmt'][] = $va;
            }
        }
        $Data['maxValue'] = max(ArrayHelper::getColumn($result,'value'));
        $Data['name'] = array_column($Data['catNum'], 'name');
        return $Data;

    }

    public static function getCatDetailData($condition)
    {
        $data['type'] = $condition['dateFlag'];
        $data['cat'] = $condition['cat'];
        $data['order_start'] = $condition['orderDate'][0];
        $data['order_end'] = $condition['orderDate'][1];
        $data['create_start'] = (!empty($condition['devDate'])) ? $condition['devDate'] : '';
        $data['create_end'] = (!empty($condition['devDate'])) ? $condition['devDate'] : '';
        $sql = "EXEC P_oa_CategoryPerformance_demo " . $data['type'] . " ,'" . $data['order_start'] . "','" . $data['order_end'] . "','" . $data['create_start'] . "','" . $data['create_end'] . "','".$data['cat']."';";
        //P_oa_CategoryPerformance_demo 0 ,'2018-01-01','2018-01-23','',''
        //缓存数据
        //$cache = Yii::$app->local_cache;
        //$ret = $cache->get($sql);
        //if($ret !== false){
         //   $result = $ret;
        //} else {
            $result = Yii::$app->py_db->createCommand($sql)->queryAll();
          //  $cache->set($sql,$result,2592000);
        //}
        //选择了主目录，重组结果数组
        if($data['cat']){
            foreach ($result as $v){
                $v['CategoryParentName'] = $v['CategoryName'];
                unset($v['CategoryName']);
                $list[] = $v;
            }
        }else{
            $list = $result;
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $list,
            'pagination' => [
                'pageSize' => false,
            ],
            'sort' => [
                'attributes' => ['catCodeNum', 'non_catCodeNum', 'numRate', 'l_qty', 'non_l_qty', 'qtyRate', 'l_AMT', 'non_l_AMT', 'amtRate'],
            ],
        ]);
        return $dataProvider;

    }

}