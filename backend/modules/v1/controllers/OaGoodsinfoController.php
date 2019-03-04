<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 9:23
 * Author: henry
 */
/**
 * @name OaGoodsinfoController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 9:23
 */


namespace backend\modules\v1\controllers;

use app\models\OaDataMine;
use backend\models\OaGoods;
use backend\models\OaSysRules;
use backend\models\OaTaskAttributeLog;
use backend\models\OaTemplates;
use backend\models\OaTemplatesVar;
use backend\models\OaWishgoods;
use backend\models\OaWishgoodssku;
use backend\modules\v1\models\ApiGoodsinfo;
use PHPUnit\Framework\Exception;
use Yii;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsinfoSearch;
use backend\models\Goodssku;
use backend\models\GoodsskuSearch;
use backend\models\GoodsCats;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

use yii\data\ActiveDataProvider;



class OaGoodsinfoController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public $pid;
    /**
     * @inheritdoc
     */


    /**
     * 属性信息
     * Date: 2019-03-01 15:39
     * Author: henry
     */
    public function actionInfoList()
    {
        $condition = Yii::$app->request->post()['condition'];
        //没有搜索条件，则添加默认显示图片状态条件
        if(!isset($condition['achieveStatus'])){
            $condition['achieveStatus'] = '待处理';
        }
        //print_r($condition);exit;
        $dataProvider = ApiGoodsinfo::getOaGoodsInfoList($condition);
        return $dataProvider;
    }

    /**
     * Displays a single OaGoodsinfo model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id = null)
    {

        $conid = Yii::$app->db->createCommand("SELECT goodsid from  oa_Goodsinfo WHERE pid=$id")
            ->queryOne();
        $goodsItem = OaGoods::find()->select('oa_goods.*')->where(['nid'=>$conid])->all();

        return $this->renderAjax('view', [
            'model' => $this->findModel($id),
            'goodsitems' =>$goodsItem[0],
        ]);
    }

    /**
     * Creates a new OaGoodsinfo model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OaGoodsinfo();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->pid]);
        } else {
            $data = $this->actionSelectParam();

            return $this->render('create', [
                'model' => $model,
                'result' => $data['res'],
                'lock' => $data['platFrom'],

            ]);
        }
    }

    /** $id =oa_goodsinfo.pid
     * oa_goods.nid = oa_goodsinfo.goodsid
     * @paramm inter $id
     *
     */

    public function actionUpdate($id){
        //任务属性初始化
        $goodsCode = '';
        $content = '';

        $updata = $_POST;
        $info = OaGoodsinfo::findOne($id);
        $conid = Yii::$app->db->createCommand("SELECT goodsid from  oa_Goodsinfo WHERE pid=$id")
            ->queryOne();

        $goodsItem = OaGoods::find()->select('oa_goods.*')->where(['nid'=>$conid])->all();

        if (!$info) {
            throw new NotFoundHttpException("The product was not found.");
        }
        //获取修改前的记录
        $oldGoodsCode = $info->GoodsCode;
        $oldDescription = $info->description;

        $post = Yii::$app->request->post();
        unset($post['OaGoodsinfo']['stockUp']);
        unset($post['OaGoodsinfo']['DictionaryName']);

        //解析对应销售
        $person = $this->getPerson();
        $info->mapPersons = $info->mapPersons?explode(',',$info->mapPersons):[];

        if($info->load($post)){
            $SupplerName = $updata['OaGoodsinfo']['SupplierName'];
            // 如果该查询没有结果则返回 false
            $Suppler = Yii::$app->db->createCommand("SELECT * from  B_Supplier WHERE SupplierName='$SupplerName'")
                ->queryOne();
            if(empty($Suppler)){
                $Recorder = yii::$app->user->identity->username;
                $InputDate = strftime('%F %T');

                Yii::$app->db->createCommand("insert into  B_Supplier (SupplierName,Recorder,InputDate,Used) 
                  VALUES ('$SupplerName','$Recorder','$InputDate',0)")->execute();
            }

            $SupplerID = Yii::$app->db->createCommand("SELECT NID from  B_Supplier WHERE SupplierName='$SupplerName'")
                ->queryOne();

            $info->SupplierID = $SupplerID['NID'];


            if ($updata['OaGoodsinfo']['DictionaryName']){
                $info->DictionaryName = implode(',',$updata['OaGoodsinfo']['DictionaryName']);
            }else{
                $info->DictionaryName = $updata['OaGoodsinfo']['DictionaryName'];
            }

            //解析对应销售
            $person = $updata['OaGoodsinfo']['mapPersons']?implode(',',$updata['OaGoodsinfo']['mapPersons']):'';
            $info->mapPersons = $person;


            $info->updateTime = strftime('%F %T');
            $developer = $updata['OaGoodsinfo']['developer'];
            $info->developer = $developer;
            if(!empty($updata['OaGoodsinfo']['possessMan1'])) {
                $arc = $updata['OaGoodsinfo']['possessMan1'];
            }
            else {
                $arc_model = OaSysRules::find()->where(['ruleKey' => $developer])->andWhere(['ruleType' => 'dev-arc-map'])->one();
                $arc = $arc_model->ruleValue;
            }

            if(!empty($updata['OaGoodsinfo']['Purchaser'])){
                $pur = $updata['OaGoodsinfo']['Purchaser'];
            }
            else{
                $pur_model = OaSysRules::find()->where(['ruleKey' => $developer])->andWhere(['ruleType' => 'dev-pur-map'])->one();
                $pur = $pur_model->ruleValue;
            }
            $info->Purchaser = $pur;
            $info->possessMan1 = $arc;
            $info->AttributeName = $updata['OaGoodsinfo']['AttributeName'];
            if(!empty($updata['OaGoodsinfo']['AttributeName'])){
                if($updata['OaGoodsinfo']['AttributeName']=='液体商品'){
                    $info->IsLiquid = 1;
                    $info->IsPowder = 0;
                    $info->isMagnetism = 0;
                    $info->IsCharged = 0;
                }
                if($updata['OaGoodsinfo']['AttributeName']=='带电商品'){
                    $info->IsLiquid = 0;
                    $info->IsPowder = 0;
                    $info->isMagnetism = 0;
                    $info->IsCharged = 1;
                }
                if($updata['OaGoodsinfo']['AttributeName']=='带磁商品'){
                    $info->IsLiquid = 0;
                    $info->IsPowder = 0;
                    $info->isMagnetism = 1;
                    $info->IsCharged = 0;
                }if($updata['OaGoodsinfo']['AttributeName']=='粉末商品'){
                    $info->IsLiquid = 0;
                    $info->IsPowder = 1;
                    $info->isMagnetism = 0;
                    $info->IsCharged = 0;
                }
            }
            $info->save(false);
            //判断商品编码修改前后是否一致？
            if($oldGoodsCode != $info->GoodsCode){
                $goodsCode = $info->GoodsCode;
            }
            //判断商品描述修改前后是否一致？
            if($oldDescription != $info->description){
                $content = $info->description;
            }
            //保存商品编码或描述的修改记录
            //var_dump(stripos($info->completeStatus,'eBay已完善'));exit;
            if(($content || $goodsCode) && (stripos($info->completeStatus,'Wish已完善') !== false || stripos($info->completeStatus,'eBay已完善' !== false))){
                $logModel = OaTaskAttributeLog::findOne(['pid' => $id]);
                if(empty($logModel)){
                    $logModel = new OaTaskAttributeLog();
                    $logModel->pid = $id;
                    $logModel->oldGoodsCode = $goodsCode ? $oldGoodsCode : '';
                    $logModel->oldDescription = $content ? $oldDescription : '';
                    $logModel->createtime = date('Y-m-d H:i:s');
                }
                $logModel->GoodsCode = $goodsCode;
                $logModel->description = $content;
                $logModel->save();
            }
            $sub_cate = $updata['OaGoods']['subCate'];
            try {
                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            }
            catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }
            $current_model = $goodsItem[0];
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->vendor1 = $updata['OaGoods']['vendor1'];
            $current_model->vendor2 = $updata['OaGoods']['vendor2'];
            $current_model->vendor3 = $updata['OaGoods']['vendor3'];
            $current_model->origin1 = $updata['OaGoods']['origin1'];
            $current_model->origin2 = $updata['OaGoods']['origin2'];
            $current_model->origin3= $updata['OaGoods']['origin3'];
            $current_model->developer= $updata['OaGoodsinfo']['developer'];
            $current_model->update(false);
            $this->redirect(['oa-goodsinfo/update','id'=>$id]);

        }else{
            $data = $this->actionSelectParam();
            $dataProvider = new ActiveDataProvider([
                'query' => Goodssku::find()->where(['pid'=>$id]),
                'pagination' => [
                    'pageSize' => 150,
                ],
            ]);
            $info->DictionaryName = explode(',', $info->DictionaryName);
            //设置默认仓库
            if(!$info->StoreName)  $info->StoreName = '义乌仓';
            return $this->render('updetail',[
                'info'=>$info,
                'pid' =>$id,
                'dataProvider' => $dataProvider,
                'result' => $data['res'],
                'lock' => $data['platFrom'],
                'packname' => $data['packname'],
                'goodsItem' => $goodsItem[0],
                'person' => $person,
            ]);
        }
    }


    /**
     * Deletes an existing OaGoodsinfo model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * this Delete have relation with OaGoodsinfo,OaGoods,OaGoodsSKU
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = OaGoodsinfo::findOne(['pid' => $id]);
        try{
            if($model && empty($model->completeStatus)){
                $this->findModel($id)->delete();  //OaGoodsinfo信息删除
                // OaGoods::deleteAll(['nid' => $id]);//OaGoods信息删除
                OaGoods::deleteAll(['nid' => $model['goodsid']]);//OaGoods信息删除
                GoodsSKU::deleteAll(['pid' => $id]);//Goodssku信息删除

                OaTemplates::deleteAll(['infoid' => $id]);//eBay平台信息删除
                OaTemplatesVar::deleteAll(['pid' => $id]);//eBay平台SKU信息删除
                OaWishgoods::deleteAll(['infoid' => $id]);//Wish平台信息删除
                OaWishgoodssku::deleteAll(['pid' => $id]);//Wish平台SKU信息删除
            }
            if(!empty($model->mid)){
                $mine = OaDataMine::findOne(['id'=>$model->mid]);
                $mine->devStatus = '未开发';
                $mine->save();
            }
        }
        catch (Exception $e){
        }
        return $this->redirect(['index']);
    }

    /**
     * Finds the OaGoodsinfo model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OaGoodsinfo the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OaGoodsinfo::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Update child sku
     * add child sku
     */

    public function actionCreatesku($id =null)
    {

        $model = new Goodssku();

        if ($model->load(Yii::$app->request->post())&&$model->save()){
            $pid = $_POST['Goodssku']['pid'];
            $this->redirect(['oa-goodsinfo/update','id'=>$pid]);
        } else {
            return $this->renderAjax('createsku', [
                'model' => $model,
                'pid' => $id,
            ]);
        }
    }

    //该方法是异步校验字段，输入框失去焦点之后自动会自动请求改地址
    public function actionValidate(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new Goodssku();
        $model->load(Yii::$app->request->post());
        return ActiveForm::validate($model);

    }


    /**
     * @return array $data 包含仓库 禁售平台信息
     */
    public function actionSelectParam(){
        $connection = Yii::$app->db;
        $sql ="SELECT StoreName from B_store";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        array_push($result,['StoreName'=>'义乌仓']);
        foreach ($result as $key=>$value){
            $StoreName[$key] = $value['StoreName'];
        }
        array_multisort($StoreName,SORT_ASC,$result);
        $res = array_column($result, 'StoreName', 'StoreName');

        $comm = $connection->createCommand('select DictionaryName from B_Dictionary where CategoryID=8 AND used=0');
        $plat = $comm->queryAll();
        array_push($plat,['DictionaryName'=>'']);
        foreach ($plat as $key=>$value){
            $DictionaryName[$key] = $value['DictionaryName'];
        }
        array_multisort($DictionaryName,SORT_ASC,$plat);
        $platFrom = array_column($plat, 'DictionaryName', 'DictionaryName');

        $commpack = $connection->createCommand('SELECT * from B_PackInfo');
        $pack = $commpack->queryAll();
        array_push($pack,['PackName'=>'']);
        foreach ($pack as $key=>$value){
            $PackName[$key] = $value['PackName'];
        }
        array_multisort($PackName,SORT_ASC,$pack);
        $packname =  array_column($pack, 'PackName', 'PackName');

        $data =[];
        $data['res'] =$res;
        $data['platFrom'] =$platFrom;
        $data['packname'] =$packname;
        return $data;
    }

    /**
     * input to py
     * @param $id
     * return mixed
     *
     */

    public function actionInput($id)
    {
        $input_goods = "P_OaGoodsToBGoods '{$id}'";
        $udpate_status = "update oa_goodsinfo set picstatus= '待处理' ,achieveStatus='已导入' where pid = '{$id}'";
        $connection = Yii::$app->db;
        $trans = $connection->beginTransaction();
        try {
            $connection->createCommand($input_goods)->execute();
            $connection->createCommand($udpate_status)->execute();
            $trans->commit();
            echo "导入成功";
        }
        catch (\Exception  $e) {
            $trans->rollBack();
            echo $e;
            echo "导入失败";
        }

    }



    /**
     * input to py
     * @para $ids
     * return mixed
     *
     */

    public function actionInputLots()
    {
        $connection = Yii::$app->db;
        $ids = $_POST['ids'];
        $trans = $connection->beginTransaction();//状态更新和数据插入做成事务
        try {
            foreach($ids as $goods_id){
                $update_status = "update oa_goodsinfo set picstatus='待处理',achieveStatus='已导入' where pid ='{$goods_id}'";
                $input_goods = "P_OaGoodsToBGoods '{$goods_id}'";
                try {
                    $connection->createCommand($input_goods)->execute();
                    $connection->createCommand($update_status)->execute();
                    $trans->commit();
                }
                catch (\Exception  $e) {
                    $trans->rollBack();
                    throw new \Exception("导入时发生错误");
                }

            }
            echo "批量导入完成！";
        }
        catch (\Exception $e) {
            echo "批量导入失败";
        }
    }

    /**
     * mark as completed
     * @param $id
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        try
        {
            $model->achieveStatus = '已完善';
            if(empty($model->picStatus)){
                $model->picStatus = '待处理';
                $model->update(false);
            }
            echo "标记成功";
        }
        catch (\Exception $e)
        {
            echo "标记失败";
        }
    }

    /**
     * mark as completed in bulk
     */
    public function actionCompleteLots()
    {
        $ids = $_GET['ids'];
        try
        {
            foreach ($ids as $id)
            {
                $model = $this->findModel($id);
                $model->achieveStatus = '已完善';
                if(empty($model->picStatus)){
                    $model->picStatus = '待处理';
                    $model->update(false);
                }
                $model->update(false);
            }
            echo "标记失败";
        }
        catch (\Exception $e)
        {
            echo "标记完成";
        }



    }

    /**
     * generate code
     */
    public function actionGenerateCode()
    {
        $ids = $_GET['ids'];
        foreach ($ids as $info_id){
            $info_model = OaGoodsinfo::find()->where(['pid'=>$info_id])->one();
            $goods_id = $info_model->goodsid;

            $current_code = $info_model->GoodsCode;
            $goods_model = OaGoods::find()->where(['nid'=>$goods_id])->one();
            $cate = $goods_model->cate;
            $b_previous_code = Yii::$app->db->createCommand(
                "select  isnull(goodscode,'UNKNOWN') as maxCode from b_goods where nid in 
            (select  max(bgs.nid) from B_Goods as bgs left join B_GoodsCats as bgc
            on bgs.GoodsCategoryID= bgc.nid where bgc.CategoryParentName='$cate' and len(goodscode)=6 )"
            )->queryOne();
            $oa_previous_code = Yii::$app->db->createCommand(
                "select isnull(goodscode,'UN0000') as maxCode from oa_goodsinfo
            where pid in (select max(pid) from oa_goodsinfo as info LEFT join 
            oa_goods as og on info.goodsid=og.nid where goodscode != 'REPEAT' and cate = '$cate')")->queryOne();
            //按规则生成编码
            $b_max_code = $b_previous_code['maxCode'];
            $oa_max_code = $oa_previous_code['maxCode'];
            if(intval(substr($b_max_code,2,4))>=intval(substr($oa_max_code,2,4))) {
                $max_code = $b_max_code;
            }
            else {
                $max_code = $oa_max_code;
            }
            if(strpos($current_code, 'REPEAT-') !== false){
                $max_code = substr($current_code,7,6);
            }
            $head = substr($max_code,0,2);
            $tail = intval(substr($max_code,2,4)) + 1;
            $zero_bit = substr('0000',0,4-strlen($tail));
            $code = $head.$zero_bit.$tail;
            //检查SKU是否已经存在
            $check_oa_goods = Yii::$app->db->createCommand(
                "select * from oa_goodsinfo where goodscode= '$code'"
            )->queryOne();
            $check_b_goods = Yii::$app->db->createCommand(
                "select * from b_goods where goodscode= '$code'"
            )->queryOne();
            if(!(empty($check_oa_goods) && empty($check_b_goods))) {
                $code = "REPEAT-".$code;
            }
            $info_model->GoodsCode = $code;
            $status = $info_model->achieveStatus;
            if($status !== '已导入')
            {
                $info_model->update(false);
            }


        }
        return $this->redirect(['index']);
    }


    //2级分类
    public function actionCategory($typeid, $pid)
    {
        $request = Yii::$app->request;
        $model = new GoodsCats();
        if ($request->isGet) {
            $cid = (int)Yii::$app->request->get('pid');
            $typeid = (int)Yii::$app->request->get('typeid');
            $model->getCatList($cid);
            if ($typeid == 1) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $model->getCatList($cid);
            }
            return $this->renderAjax('updetail', [
                'model' => $model,
            ]);
        }
    }

    private function getPerson()
    {
        $db = Yii::$app->db;
        $sql = "select ur.username as person from [user] as ur 
                LEFT JOIN auth_assignment as ag on 
                ur.id = ag.user_id where ag.item_name like '%销售%'";
        $query = $db->createCommand($sql)->queryAll();
        $ret = ArrayHelper::getColumn($query,'person');
        return \array_combine($ret,$ret);
    }







}