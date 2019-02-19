<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-12-06
 * Time: 14:23
 */

namespace backend\modules\v1\models;


use backend\models\OaGoodsinfo;
use backend\models\OaSysRules;
use backend\models\User;
use Yii;
use backend\models\OaGoods;
use yii\data\ActiveDataProvider;

class ApiGoods
{


    /**
     * 获取产品推荐列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getGoodsList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        //$page = isset($post['page']) ? $post['page'] : 1;

        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,createDate,updateDate');
        $query->andFilterWhere(["IFNULL(introducer,'')" => $userList]);//有推荐人的产品列表
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }


    /**
     * 获取正向开发列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getForwardList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                                devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        $query->andFilterWhere(['devStatus' => '正向认领']);//正向开发
        $query->andFilterWhere(['checkStatus' => ['已认领','待提交','待审批','已审批','未通过']]);
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);


        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /**
     * 获取逆向开发列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getBackwardList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                                devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        $query->andFilterWhere(['devStatus' => '逆向认领']);//正向开发
        $query->andFilterWhere(['checkStatus' => ['已认领','待提交','待审批','已审批','未通过']]);
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate']) $query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate']) $query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);

        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }


    /**
     * 获取待审核列表、已通过列表、未通过列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getCheckList($user, $post, $type = 'check')
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,mineId,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                        devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        if($type == 'check'){
            $query->andFilterWhere(['checkStatus' => '待审批']);
        }elseif($type == 'pass'){
            $query->andFilterWhere(['checkStatus' => '已审批']);
        }else{
            $query->andFilterWhere(['checkStatus' => '未通过']);
        }
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        if ($post['mineId'] == 1){
            $query->andFilterWhere(['>', 'IFNULL(mineId,0)', 0]);
        }elseif($post['mineId'] == 0){
            $query->andFilterWhere(['IFNULL(mineId,0)' => 0]);
        }
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);

        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /**
     * @param $id
     * Date: 2019-02-18 14:23
     * Author: henry
     * @throws \yii\db\Exception
     */
    public static function saveDataToInfo($id,$dictionary)
    {
        $goodsModel = OaGoods::findOne($id);
        $user = User::findOne(['username' => $goodsModel->developer]);
        $_model = new OaGoodsinfo();
        if($goodsModel->mineId){
            $dictionaryName = ArrayHelper::getValue($request,'dictionaryName');
            $dictionaryName[] = 'eBay';
            $dictionaryName = \array_unique($dictionaryName);
            $dictionaryName = \implode(',',$dictionaryName);

            $sql = 'p_oa_joomCheckToGoodsInfo @mid=:mid,@dictionaryName=:dictionaryName';
            $db = Yii::$app->db;
            try {
                $check = $db->createCommand($sql)->bindValues([':mid' => $goodsModel->mineId, ':dictionaryName' => $dictionaryName]);
                $check->execute();
                return '审核成功！';
            }
            catch (\Exception $why) {
                return '审核失败！';
            }
        }else{
            $code = self::generateCode($goodsModel->cate);
            //print_r($code);exit;
            $_model->mapPersons = $user->mapPersons;
            $_model->goodsid = $goodsModel->nid;
            $_model->GoodsCode = $code;
            $_model->picUrl = $goodsModel->img;
            $_model->developer = $goodsModel->developer;
            $_model->devDatetime = strftime('%F %T');
            $_model->updateTime = strftime('%F %T');
            $_model->achieveStatus = '待处理';
            $_model->stockUp = $goodsModel->stockUp ? '是' : '否';
            if(empty($_model->possessMan1)){
                $arc_model = OaSysRules::find()->where(['ruleKey' => $goodsModel->developer])->andWhere(['ruleType' => 'dev-arc-map'])->one();
                $arc = $arc_model?$arc_model->ruleValue:'';
                $_model->possessMan1 = $arc;
            }
            if(empty($_model->Purchaser)){
                $pur_model = OaSysRules::find()->where(['ruleKey' => $goodsModel->developer])->andWhere(['ruleType' => 'dev-pur-map'])->one();
                $pur = $pur_model?$pur_model->ruleValue:'';
                $_model->Purchaser = $pur;
            }
            $_model->save();
            //print_r($res);exit();
        }
        //print_r(123123);exit();

    }

    /**
     * @param $cate
     * Date: 2019-02-18 14:23
     * Author: henry
     * @return string
     * @throws \yii\db\Exception
     */
    private static function generateCode($cate)
    {
        $b_previous_code = Yii::$app->py_db->createCommand(
            "select isnull(goodscode,'UN0000') as maxCode from b_goods where nid in 
            (select max(bgs.nid) from B_Goods as bgs left join B_GoodsCats as bgc
            on bgs.GoodsCategoryID= bgc.nid where bgc.CategoryParentName='$cate' and len(goodscode)=6)"
        )->queryOne();

        $oa_previous_code = Yii::$app->pro_db->createCommand(
            "select ifnull(goodscode,'UN0000') as maxCode from oa_goodsinfo
            where id in (select max(id) from oa_goodsinfo as info LEFT join 
            oa_goods as og on info.goodsid=og.nid where cate = '$cate')")->queryOne();

        $oa_goodsId_query = Yii::$app->pro_db->createCommand("select max(nid) as maxNid from oa_goods")->queryOne();
        $oa_maxNid = $oa_goodsId_query['maxNid'];

        //按规则生成编码
        $b_max_code = $b_previous_code['maxCode'];
        $oa_max_code = str_replace('-test','',$oa_previous_code['maxCode']);

        if(is_numeric($oa_max_code)){
            return strval($oa_maxNid).'-test';
        }
        if(intval(substr($b_max_code,2,4))>=intval(substr($oa_max_code,2,4))) {
            $max_code = $b_max_code;
        }
        else {
            $max_code = $oa_max_code;
        }
        $head = substr($max_code,0,2);
        $tail = intval(substr($max_code,2,4));
        $code = $oa_maxNid;
        while($tail<=9999)
        {
            $tail = $tail + 1;
            $zero_bit = substr('0000',0,4-strlen($tail));
            $code = $head.$zero_bit.$tail;
            //检查SKU是否已经存在
            $check_oa_goods = Yii::$app->py_db->createCommand(
                "select pid from oa_goodsinfo where goodscode like '$code"."%'"
            )->queryOne();
            $check_b_goods = Yii::$app->py_db->createCommand(
                "select nid from b_goods where goodscode='$code'"
            )->queryOne();
            if((empty($check_oa_goods) && empty($check_b_goods))) {
                break;
            }
            else{
                $code = $oa_maxNid;
            }
        }
        return $code.'-test';
    }

}