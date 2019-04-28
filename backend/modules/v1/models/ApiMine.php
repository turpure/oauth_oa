<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:18
 */

namespace backend\modules\v1\models;

use yii\data\ActiveDataProvider;
use backend\models\OaDataMine;
use backend\models\OaDataMineDetail;
use backend\models\OaGoods;
use backend\models\ShopElf\BGoodsSKULinkShop;
use backend\modules\v1\utils\ExportTools;
use Exception;
use Yii;


class ApiMine
{

    /**
     * @brief 获取采集数据列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getMineList($condition)
    {

        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $query = OaDataMine::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 获取条目的详细信息
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function getMineInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $mine = OaDataMineDetail::find()->joinWith('oaDataMine')
            ->select('oa_dataMine.*,proName,description,tags,mid')
            ->where(['mid' => $id])->asArray()->one();
        unset($mine['oaDataMine'],$mine['mid']);
        $mineDetail = OaDataMineDetail::find()->select('id,mid,parentId,childId,color,
        proSize,quantity,price,msrPrice,shipping,shippingWeight,shippingTime,varMainImage')
            ->where(['mid' => $id])->asArray()->all();

        $images = OaDataMineDetail::find()->select('extraImage1,extraImage2,extraImage3,extraImage4,
        extraImage5,extraImage6,extraImage7,extraImage8,extraImage9,extraImage10,mainImage')->
        where(['mid' => $id])->asArray()->one();
        return['basicInfo' => $mine, 'images' => $images, 'detailsInfo' => $mineDetail];
    }

    /**
     * @brief 添加采集任务
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function mine($condition)
    {
        $proId = isset($condition['proId']) ? $condition['proId'] : '';
        $proId = explode(',', $proId);
        $maxCode = static::getMaxCode();
        $creator = Yii::$app->user->identity->username;
        $trans = Yii::$app->db->beginTransaction();
        $mine = new OaDataMine();
        try {
            foreach ($proId as $id) {
                $newMine = clone $mine;
                $id = trim($id);
                $goodsCode = static::generateCode($maxCode);
                $createTime = date('Y-m-d H:i:s');
                $newMine->proId = $id;
                $newMine->platForm = 'joom';
                $newMine->creator = $creator;
                $newMine->createTime = $createTime;
                $newMine->updateTime = $createTime;
                $newMine->progress = '待采集';
                $newMine->goodsCode = $goodsCode;
                $newMine->detailStatus = '未完善';
                if (!$newMine->save()) {
                    throw  new Exception("保存失败！", '400003');
                }
                $jobId = $newMine->id;
                $redis = Yii::$app->redis;
                $redis->lpush('job_list', $jobId . ',' . $id);
                $maxCode = $goodsCode;
            }
            $trans->commit();
        }
        catch (Exception $why){
            $trans->rollBack();
            throw  new Exception("保存失败！",'400003');
        }
        return [];
    }

    /**
     * @brief 标记玩善
     * @param $condition
     * @throws Exception
     * @return array
     */
    public static function finish($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            $id = [$id];
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            foreach ($id as $mid) {
                $mine = OaDataMine::findOne(['id' => $mid]);
                if ($mine === null) {
                    throw new Exception('无效的ID', '400002');
                }
                $mine->setAttribute('detailStatus','已完善');
                if(!$mine->save()){
                    throw  new \Exception('保存失败！', '400003');
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];
    }

    /**
     * @brief 设置价格
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function setPrice($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $price = isset($condition['price']) ? (float)$condition['price'] : 0;
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';

        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            throw new Exception('id 应为数组！', '400004');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $mid) {
                $mine = OaDataMineDetail::findAll(['mid' => $mid]);
                foreach ($mine as $row) {
                    $oldPrice = $row->price;
                    $newPrice = round(static::_calculatePrice((float)$oldPrice, (float)$price, $operator),2);
                    $row->setAttribute('price', $newPrice );
                    if(!$row->save()){
                        throw  new \Exception('保存失败！', '400003');
                    }
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];

    }

    /**
     * @brief 设置类目
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function setCat($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $cat = isset($condition['cat']) ? (int)$condition['cat'] : '';
        $subCat = isset($condition['subCat']) ? $condition['subCat'] : '';

        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            throw new Exception('id 应为数组！', '400004');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $mid) {
                $row = OaDataMine::findOne(['id' => $mid]);
                if ($row === null) {
                    throw new Exception('无效的ID', '400002');
                }
                $row->setAttributes(['cat' => $cat, 'subCat' => $subCat]);
                if(!$row->save(false)){
                    throw  new \Exception('保存失败！', '400003');
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];

    }

    /**
     * @brief 删除条目
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function delete($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            OaDataMine::deleteAll(['id' =>$id]);
            OaDataMineDetail::deleteAll(['mid' => $id]);
            $trans->commit();
        }
        catch (\Exception $why) {
            $trans->rollBack();
            throw  new Exception('删除失败！', '400005');

        }
        return [];
    }


    /**
     * @brief 删除多属性条目
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function deleteDetail($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!\is_array($id)) {
            $id = [$id];
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $varId) {
                OaDataMineDetail::deleteAll(['id' => $varId]);
            }
            $trans->commit();
        }
        catch (\Exception $why) {
            $trans->rollBack();
            throw  new \Exception('删除失败！', '400005');

        }
        return [];
    }

    /**
     * @brief 转至开发
     * @param $condition
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function sendToDevelop($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $stockUp = isset($condition['stockUp']) ? $condition['stockUp'] : 0;
        $user = Yii::$app->user->identity->username;
        $mine = OaDataMine::findOne($id);
        if($mine === null) {
           throw new Exception('无效的ID', '400002');
        }
        if($mine->devStatus !=='未开发') {
            throw new Exception('该状态下产品不能转至开发','400006');
        }
        static::_sendToDevelop($mine,$user, $stockUp);
    }

    /**
     * @brief 关联店铺SKu
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function bindShopSku($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $mine = OaDataMine::findOne($id);
        if($mine === null) {
            throw  new Exception('无效的ID', '400002');
        }
        $goodsCode = $mine->goodsCode;
        $variations = OaDataMineDetail::find()->select('id,childId,color,proSize,pySku,mainImage')
            ->where(['mid' => $id])->asArray()->all();
        return ['goodsCode' => $goodsCode, 'pyGoodsCode' => '', 'variations' => $variations];
    }

    /**
     * @brief 保存店铺SKU
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function saveShopSku($condition)
    {
        $variations = $condition['variations'];
        $pyGoodsCode = $condition['pyGoodsCode'];
        $goodsCode = $condition['goodsCode'];

        //bind trans
        $trans = Yii::$app->py_db->beginTransaction();
        try{
            foreach ($variations as $var) {
                $shopSku = BGoodsSKULinkShop::findOne(['SKU' => $var['pySku'], 'ShopSKU' => $var['childId']]);
                if($shopSku === null) {
                    $shopSku = new BGoodsSKULinkShop();
                }
                $shopSku->setAttributes(['SKU' => $var['pySku'], 'ShopSKU' => $var['childId']]);
                if(!$shopSku->save()){
                    throw new Exception('关联失败！', '400008');
                }
                $detail = OaDataMineDetail::findOne($var['id']);
                $detail->setAttributes(['pySku' => $var['pySku']]);
                if(!$detail->save()) {
                    throw new Exception('关联失败！', '400008');
                }
            }
            $mine = OaDataMine::findOne(['goodsCode' => $goodsCode]);
            $mine->setAttributes(['pyGoodsCode' => $pyGoodsCode, 'devStatus' => '已关联']);
            if(!$mine->save()) {
                throw new Exception('关联失败！', '400008');
            }
            $trans->commit();
        }
        catch (Exception $why) {
            $trans->rollBack();
        }
        return [];

    }

    /**
     * @brief 导出joom模板
     * @param $condition
     * @throws Exception
     */
    public static function exportToJoom($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if(!is_array($id)) {
            $id = [$id];
        }
        $ret = [];
        foreach ($id as $mid) {
            $condition = ['id' => $mid];
            $info = static::getMineInfo($condition);
            $basicInfo = $info['basicInfo'];
            $images = $info['images'];
            $variations = $info['detailsInfo'];
            foreach ($variations as $var) {
                $row = [
                    'Parent Unique ID' => $var['parentId'],
                    '*Product Name' =>  $basicInfo['proName'],
                    'Description' =>  $var['parentId'],
                    '*Tags' => $basicInfo['tags'],
                    '*Unique ID' => $var['childId'],
                    'Color' => $var['color'],
                    'Size' => $var['proSize'],
                    '*Quantity' => $var['quantity'],
                    '*Price' => $var['price'],
                    '*MSRP' => $var['msrPrice'],
                    '*Shipping' => $var['shipping'],
                    'Shipping weight' => $var['shippingWeight'],
                    'Shipping Time(enter without " ", just the estimated days )' => $var['shippingTime'],
                    '*Product Main Image URL' => $images['mainImage'],
                    'Variant Main Image URL' => $var['varMainImage'],
                    'Extra Image URL' => $images['extraImage1'],
                    'Extra Image URL 1' => $images['extraImage2'],
                    'Extra Image URL 2' => $images['extraImage3'],
                    'Extra Image URL 3' => $images['extraImage4'],
                    'Extra Image URL 4' => $images['extraImage5'],
                    'Extra Image URL 5' => $images['extraImage6'],
                    'Extra Image URL 6' => $images['extraImage7'],
                    'Extra Image URL 7' => $images['extraImage8'],
                    'Extra Image URL 8' => $images['extraImage9'],
                    'Extra Image URL 9' => $images['extraImage10'],
                    'Extra Image URL 10' => '',
                    'Dangerous Kind' => static::_getDangerousKind($basicInfo)
                ];
                $ret[] = $row;
            }
        }
        ExportTools::toExcelOrCsv('test-joom',$ret,'Csv');
    }

    /**
     * @brief 保存信息
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function save($condition)
    {
        $basicInfo = $condition['basicInfo'];
        $variations = $condition['detailsInfo'];
        $variations['proName'] = $basicInfo['proName'];
        $variations['description'] = $basicInfo['description'];
        $variations['tags'] = $basicInfo['tags'];
        $images = $condition['images'];

        $trans = Yii::$app->db->beginTransaction();
        try {
            $mine = OaDataMine::findOne(['id' => $basicInfo['id']]);
            $mine->setAttributes($basicInfo);
            foreach ($variations as $var) {
                $detail = OaDataMineDetail::findOne(['id' => $var['id']]);
                $detail->setAttributes($var);
                $detail->setAttributes($images);
                if(!$detail->save()) {
                    throw new Exception('保存失败！','400003');
                }
            }
            $trans->commit();
        }
        catch (Exception $why) {
            $trans->rollBack();
            throw new Exception('保存失败！','400003');;
        }
        return [];

    }

    /**
     * @brief 计算价格
     * @param $oldPrice
     * @param $price
     * @param $operator
     * @return float|int
     */
    private static function _calculatePrice($oldPrice, $price, $operator)
    {
        if ($operator === '=') {
            return $price;
        }
        if ($operator === '+') {
            return $oldPrice + $price;
        }
        if ($operator === '-') {
            return $oldPrice - $price;
        }

        if ($operator === '*') {
            return $oldPrice * $price;
        }
        if ($operator === '/') {
            return $oldPrice / $price;
        }
        return $price;

    }

    /**
     * @brief 转至开发事务
     * @param $mine
     * @param $developer
     * @param $stockUp
     * @throws Exception
     * @throws \yii\db\Exception
     */
    private static function _sendToDevelop($mine, $developer, $stockUp)
    {
        $row = [
            'cate,'  => $mine['cat'],
            'devNum,'  => $mine['goodsCode'],
            'devStatus,'  => '正向认领',
            'checkStatus,'  => '待审批',
//            'createDate,'  => date('Y-m-d H:i:s'),
//            'updateDate,'  => date('Y-m-d H:i:s'),
            'img,'  => $mine['mainImage'],
            'subCate,'  => $mine['subCat'],
            'origin1,'  => 'https://www.joom.com/en/products/'. $mine['proId'],
            'developer,'  => $developer,
            'stockUp,'  => $stockUp,
            'mineId'  => $mine['id'],
        ];

        $goods = new OaGoods();
        $trans =  Yii::$app->db->getTransaction();
        try {
            $goods->setAttributes($row);
            $mine->setAttribute(['devStatus' => '开发中']);
            if(!$goods->save() || !$mine->save()) {
                throw new Exception('转至失败！', '400007');
            }
            $trans->commit();
        }
        catch (Exception $why) {
            $trans->rollBack();
            throw new Exception('转至失败！', '400007');
        }
    }

    /**
     * @brief 计算危险类型
     * @param $basicInfo
     * @return string
     */
    private static function _getDangerousKind($basicInfo)
    {
        if($basicInfo['isLiquid'] === 1) {
            return 'liquid';
        }
        if($basicInfo['isPowder'] === 0) {
            return 'powder';
        }
        if($basicInfo['isMagnetism'] === 0) {
            return 'withBattery';
        }
        if($basicInfo['isCharged'] === 0) {
            return 'withBattery';
        }
        return 'notDangerous';
    }

    /**
     * @brief 生成编码
     * @param $previousCode
     * @return string
     */
    private static function generateCode($previousCode)
    {
        $number = (int)substr($previousCode,9) + 1;
        $base = '0000';
        if(\strlen($number) === \strlen($base)){
            return 'A'.date('Ymd').$number;
        }
        $code = substr($base,0,\strlen($base) - \strlen($number)).$number;
        return 'A'.date('Ymd').$code;
    }

    /**
     * @brief 获取当天最大编码
     * @return string
     * @throws Exception
     */
    private static function getMaxCode()
    {
        $db = Yii::$app->pro_db;
        $max_code_sql = 'select goodsCode from oa_dataMine 
                        where datediff(createTime,now())=0 
                        and id =(select max(id) from oa_dataMine 
                        where datediff(createTime,now())=0 )';
        $maxCode = $db->createCommand($max_code_sql)->queryOne()['goodsCode'] ?: 'A'.date('Ydm').'0000';
        return $maxCode;
    }
}