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
use backend\modules\v1\services\EbayGroupDispatchService;
use Yii;
use backend\models\OaGoods;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use backend\modules\v1\utils\ExportTools;
use backend\modules\v1\models\ApiCondition;
use yii\helpers\ArrayHelper;


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

        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,createDate,updateDate');
        $query->andFilterWhere(['OR',["IFNULL(introducer,'')" => $userList],["IFNULL(developer,'')" => $userList]]);//有推荐人的产品列表
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
        if(isset($post['checkStatus']) && $post['checkStatus']) {
            $query->andWhere(['like', 'checkStatus', $post['checkStatus']]);
        }else{
            $query->andWhere(['checkStatus' => '未认领']);
        }
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
     * @brief 获取所有开发
     * @return array
     */
    public static function getDeveloper()
    {
        $users = ApiCondition::getUsers(true);
        $users =  array_filter($users, function ($ele) {return strpos($ele['position'],'开发') !==false;});
        return array_values(ArrayHelper::getColumn($users,'username'));

    }

    /**
     * 生成产品模板
     */
    public static function generateNewProductTemplate()
    {
        $fileName = 'new-product-template';
        $titles = [
            '*图片', '*主类目', '*子类目', '*供应商链接1', '供应商链接2', '供应商链接3', '平台参考链接1', '平台参考链接2',
            '平台参考链接3', '售价($)', '预估月销量', '预估利润率(%)', '预估重量(g)', '预估成本(￥)', '预估月毛利($)',
        ];

        $data = [
            [
                '*图片' => 'image',
                '*主类目' => 'zhu',
                '*子类目' => 'zi',
                '*供应商链接1' => 'gong1',
                '供应商链接2' => '2',
                '供应商链接3' => '3',
                '平台参考链接1' => '1',
                '平台参考链接2' => '2',
                '平台参考链接3' => '3',
                '售价($)' => '2',
                '预估月销量' => '2',
                '预估利润率(%)' => '2',
                '预估重量(g)' => '2',
                '预估成本(￥)' => '2',
                '预估月毛利($)' => '2',
            ]
        ];

        ExportTools::toExcelOrCsv($fileName, $data,'Csv' ,$titles);
    }


    /**
     * 上传产品
     * @param $devStatus
     * @throws Exception
     * @return mixed
     */
    public static function uploadNewProduct($devStatus)
    {
        $fields = ['img', 'cate', 'subCate', 'vendor1', 'vendor2', 'vendor3',
            'origin1', 'origin2', 'origin3', 'salePrice','hopeSale',  'hopeRate', 'hopeWeight',
            'hopeCost','hopeMonthProfit',
        ];
        try {
            if (Yii::$app->request->isPost ) {
                $tmpName = $_FILES['file']['tmp_name'];
                $csvAsArray = array_map('str_getcsv', file($tmpName));

                // 删除列名
                array_shift($csvAsArray);
                foreach ($csvAsArray as &$row) {
                    foreach ($row as &$ceil) {
                        //检测编码方式
                        $encode = mb_detect_encoding($ceil, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
                        // 转换编码方式
                        $ceil =  iconv($encode, 'UTF-8',$ceil);
                    }
                    //释放
                    unset($ceil);

                    //生产新产品
                    $product = array_combine($fields, $row);

                    //更新状态
                    $product['devStatus'] = $devStatus;

                    static::saveUploadedNewProduct($product);
                }
            }
            return ['上传产品成功'];
        }

        catch(\Exception $why) {
            throw new Exception('上传产品失败');
        }
    }

    /**
     * 保存新产品:包括正向开发和逆向开发
     * 此处先不校验备货和可用数量
     * @param $product
     * @return mixed
     * @throws \Exception
     */
    public static function saveUploadedNewProduct($product)
    {

        $model = new OaGoods();
        $user = Yii::$app->user->identity->username;
        $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
            ->bindValues([':CategoryName' => $product['cate']])->queryOne();
        $model->attributes = $product;
        $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
        $model->checkStatus = '待提交';
        $model->developer =  $user;
        $model->updateDate = $model->createDate = date('Y-m-d H:i:s');
        $model->devNum = date('Ymd', time()) . strval($model->nid);
        $ret = $model->save();
        if (!$ret) {
            throw new \Exception('Create new product failed!');
        }
        return $model;
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
        $query->andFilterWhere(["IFNULL(stockUp,'否')" => $post['stockUp']]);
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
        //$query->andFilterWhere(['checkStatus' => ['已认领','待提交','待审批','已审批','未通过']]);
        if(isset($post['checkStatus']) && $post['checkStatus']) {
            $query->andWhere(['like', 'checkStatus', $post['checkStatus']]);
        }else{
            $query->andWhere(['checkStatus' => ['已认领','待提交','待审批','未通过']]);
        }

        $query->orderBy(["FIELD(`checkStatus`,'已认领','待提交','待审批','未通过')" => true, 'createDate' => SORT_DESC]);
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
        $query->andFilterWhere(["IFNULL(stockUp,'否')" => $post['stockUp']]);
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
        if(isset($post['checkStatus']) && $post['checkStatus']) {
            $query->andWhere(['like', 'checkStatus', $post['checkStatus']]);
        }else{
            $query->andWhere(['checkStatus' => ['已认领','待提交','待审批','未通过']]);
        }

        $query->orderBy(["FIELD(`checkStatus`,'已认领','待提交','待审批','未通过')" => true, 'createDate' => SORT_DESC]);
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
        $role = ApiUser::getUserRole($user->username);

        $query = OaGoods::find();
        $query->select('nid,mineId,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                        devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');

        if($type == 'check'){
            $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
            $query->andFilterWhere(['checkStatus' => '待审批']);
        }elseif($type == 'pass'){
            if(in_array('产品销售', $role) === false){
                $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
            }
            $query->andFilterWhere(['checkStatus' => '已审批']);
        }else{
            $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
            $query->andFilterWhere(['checkStatus' => '未通过']);
        }
        $query->andFilterWhere(["IFNULL(stockUp,'否')" => $post['stockUp']]);

        if (isset($post['mineId']) && $post['mineId'] === '是') $query->andFilterWhere(['>', "IFnull(mineId,0)", 0]);
        if (isset($post['mineId']) && $post['mineId'] === '否') $query->andFilterWhere(["IFNULL(mineId,0)" => 0]);
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
            $sql = "CALL proCenter.oa_joomCheckToGoodsInfo('{$goodsModel->mineId}','{$dictionary}')";
            $db = Yii::$app->db;
            $db->createCommand($sql)->execute();
        }else{
            $code = self::generateCode($goodsModel->cate);
            $_model->mapPersons = $user->mapPersons;
            $_model->goodsId = $goodsModel->nid;
            $_model->goodsCode = $code;
            $_model->picUrl = $goodsModel->img;
            $_model->developer = $goodsModel->developer;
            $_model->devDatetime = strftime('%F %T');
            $_model->updateTime = strftime('%F %T');
            $_model->achieveStatus = '待处理';
            $_model->stockUp = $goodsModel->stockUp;
            if(empty($_model->possessMan1)){
                $arc_model = OaSysRules::find()->where(['ruleKey' => $goodsModel->developer])->andWhere(['ruleType' => 'dev-arc-map'])->one();
                if(!$arc_model){
                    throw new Exception('当前开发没有对应美工！');
                }
                $arc = $arc_model?$arc_model->ruleValue:'';
                $_model->possessMan1 = $arc;
            }
            if(empty($_model->purchaser)){
                $pur_model = OaSysRules::find()->where(['ruleKey' => $goodsModel->developer])->andWhere(['ruleType' => 'dev-pur-map'])->one();
                if(!$pur_model){
                    throw new Exception('当前开发没有对应采购！');
                }
                $pur = $pur_model?$pur_model->ruleValue:'';
                $_model->purchaser = $pur;
            }
            if(!$_model->save()){
                //print_r($_model->getErrors());exit;
                throw new Exception('审核失败！');
            }

        }

    }


    /**
     * 设置ebay分组 只有在部门列表里面的开发才设置分组
     * @param $id
     * @param $developMan
     * @param $mineId
     * @throws Exception
     */
    public static function setEbayGroup($id,$developMan,$mineId) {

        $userDepartment = ApiUser::getUserGroupByUserName($developMan);
        $disableDepartment = ApiBasicInfo::getDisableEbayGroupDepartment();

        if(!empty($mineId)) {
            return;
        }

        foreach ($disableDepartment as $dp) {
            if (strpos($userDepartment, $dp) !== false) {
                return;
            }
        }


        $goodsInfo = OaGoodsinfo::find()->where(['goodsId' =>$id])->one();
        $ebayGroup = EbayGroupDispatchService::getOneWorkGroup();
        $groupName = $ebayGroup['groupName'];
        $groupId = $ebayGroup['id'];
        $goodsInfo->setAttributeS(['ebay_group' => $groupName]);
        if($goodsInfo->save()) {
            EbayGroupDispatchService::addWorkGroupNumber($groupId);
        }
        else {
            throw new Exception("设置ebay分组失败！");
        }
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

        $oa_previous_code = Yii::$app->db->createCommand(
            "select ifnull(goodscode,'UN0000') as maxCode from proCenter.oa_goodsinfo
            where id in (select max(id) from proCenter.oa_goodsinfo as info LEFT join 
            proCenter.oa_goods as og on info.goodsid=og.nid where cate = '$cate')")->queryOne();

        $oa_goodsId_query = Yii::$app->db->createCommand("select max(nid) as maxNid from proCenter.oa_goods")->queryOne();
        $oa_maxNid = $oa_goodsId_query['maxNid'];

        //按规则生成编码
        $b_max_code = $b_previous_code['maxCode'];
        $oa_max_code = str_replace('-test','',$oa_previous_code['maxCode']);

        if(is_numeric($oa_max_code)){
            return strval($oa_maxNid).'-test';
        }
        if(intval(substr($b_max_code,2,4))>=intval(substr($oa_max_code,2,4))) {
            $max_code = $b_max_code;
        } else {
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
            $check_oa_goods = Yii::$app->db->createCommand("select id from proCenter.oa_goodsinfo where goodscode like '$code"."%'")->queryOne();
            $check_b_goods = Yii::$app->py_db->createCommand("select nid from b_goods where goodscode='$code'")->queryOne();
            if((empty($check_oa_goods) && empty($check_b_goods))) {
                break;
            } else{
                $code = $oa_maxNid;
            }
        }
        return $code.'-test';
    }

}
