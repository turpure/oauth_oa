<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-14
 * Time: 10:53
 * Author: henry
 */

/**
 * @name ApiSupplier.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-14 10:53
 */


namespace backend\modules\v1\models;


use backend\models\OaSupplier;
use backend\models\OaSupplierGoods;
use backend\models\OaSupplierGoodsSku;
use backend\models\User;
use mdm\admin\models\Department;
use mdm\admin\models\DepartmentChild;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class ApiSupplier
{
    /**
     * @param $condition
     * Date: 2019-03-18 16:16
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function getPySupplierList($condition)
    {
        $q = isset($condition['q']) ? $condition['q'] : '';
        Yii::$app->response->format = Response::FORMAT_JSON;//响应数据格式为json
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!$q) {
            return $out;
        }

        $sql = "SELECT TOP 50 bs.supplierName FROM B_supplier bs
                WHERE used=0 AND supplierName LIKE '%{$q}%' 
                AND NOT EXISTS (SELECT supplierName FROM oa_supplier WHERE RTRIM(LTRIM(oa_supplier.supplierName)) = RTRIM(LTRIM(bs.supplierName)))  
                ORDER BY supplierName";
        $res = Yii::$app->py_db->createCommand($sql)->queryAll();
        $out['results'] = array_map([$this, 'format'], $res);
        //print_r($out['results']);exit;
        return $out;
    }

    /**
     * 获取已有的供应商列表
     * @param $condition
     * Date: 2019-03-18 17:31
     * Author: henry
     * @return array
     */
    public function getSupplier($condition)
    {
        $q = isset($condition['q']) ? $condition['q'] : null;
        Yii::$app->response->format = Response::FORMAT_JSON;//响应数据格式为json
        $out = ['results' => ['id' => '', 'text' => '']];
        $res = OaSupplier::find()->andFilterWhere(['like', 'supplierName', $q])->asArray()->all();
        $out['results'] = array_map([$this, 'format'], $res);
        return $out;
    }

    private function format($data)
    {
        $result = [];
        $result['id'] = $data['supplierName'];
        $result['text'] = $data['supplierName'];
        return $result;
    }

    /**
     * @param $condition
     * Date: 2019-03-14 14:28
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaSupplierInfoList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaSupplier::find();

        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if (isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if (isset($condition['contactPerson1'])) $query->andFilterWhere(['like', 'contactPerson1', $condition['contactPerson1']]);
        if (isset($condition['phone1'])) $query->andFilterWhere(['like', 'phone1', $condition['phone1']]);
        if (isset($condition['contactPerson2'])) $query->andFilterWhere(['like', 'contactPerson2', $condition['contactPerson2']]);
        if (isset($condition['phone2'])) $query->andFilterWhere(['like', 'phone2', $condition['phone2']]);
        if (isset($condition['paymentDays'])) $query->andFilterWhere(['paymentDays' => $condition['paymentDays']]);
        if (isset($condition['payChannel'])) $query->andFilterWhere(['like', 'payChannel', $condition['payChannel']]);
        if (isset($condition['address'])) $query->andFilterWhere(['like', 'address', $condition['address']]);
        if (isset($condition['link1'])) $query->andFilterWhere(['like', 'link1', $condition['link1']]);
        if (isset($condition['link2'])) $query->andFilterWhere(['like', 'link2', $condition['link2']]);
        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @param $condition
     * Date: 2019-03-14 14:42
     * Author: henry
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getSupplierById($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return OaSupplier::findOne(['id' => $id]);
    }

    /**
     * @param $id
     * Date: 2019-03-14 14:52
     * Author: henry
     * @return bool
     */
    public static function deleteSupplierById($id)
    {
        $ret = OaSupplier::deleteAll(['id' => $id]);
        if ($ret) {
            return true;
        }
        return false;
    }

    /** 创建供应商信息
     * @param $condition
     * Date: 2019-03-14 17:38
     * Author: henry
     * @return array|bool
     */
    public static function createSupplier($condition)
    {
        //判断当前采购的最大创建数量
        //$user = Yii::$app->user->identity->username;
        $user = $condition['purchaser'];
        $userModel = User::findOne(['username' => $user]);
        $num = OaSupplier::find()->andWhere(['purchaser' => $user])->count();
        //获取部门
        $departChild = DepartmentChild::findOne(['user_id' => $userModel['id']]);
        $depart = Department::findOne(['id' => $departChild['department_id']]);
        if (strpos($depart['type'], '供应链') !== false && $num < $userModel['maxSupplierNum']) {
            $model = new OaSupplier();
            $supplierId = Yii::$app->py_db->createCommand("SELECT nid FROM B_supplier WHERE supplierName LIKE '%" . $condition['supplierName'] . "%'")->queryOne();
            $model->attributes = $condition;
            $model->supplierId = $supplierId['nid'];
            if (!isset($condition['createTime']) || !$condition['createTime']) $model->createTime = date('Y-m-d H:i:s');
            $res = $model->save();
            if ($res) {
                return true;
            } else {
                return [
                    'code' => 400,
                    'message' => 'failed'
                ];
            }
        } else {
            return [
                'code' => 400,
                'message' => 'The user can not add new suppliers'
            ];
        }

    }

    /**
     * @param $condition
     * Date: 2019-03-14 17:52
     * Author: henry
     * @return array|bool
     */
    public static function updateSupplier($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return false;
        }
        $model = OaSupplier::findOne($id);
        $supplierId = Yii::$app->db->createCommand("SELECT nid FROM B_supplier WHERE supplierName LIKE '%" . $condition['supplierName'] . "%'")->queryOne();
        $model->attributes = $condition;
        $model->supplierId = $supplierId['nid'];
        if (!isset($condition['updateTime']) || !$condition['updateTime']) $model->updateTime = date('Y-m-d H:i:s');
        $res = $model->save();
        if ($res) {
            return true;
        } else {
            return [
                'code' => 400,
                //'message' => array_values($model->getErrors())[0][0],
                'message' => 'failed'
            ];
        }
    }

    ##############################   supplier goods   ###############################

    /**
     * @param $condition
     * Date: 2019-03-14 16:06
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaSupplierGoodsList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaSupplierGoods::find();

        if (isset($condition['updatedTime'])) $query->andFilterWhere(['updatedTime' => $condition['updatedTime']]);
        if (isset($condition['createdTime'])) $query->andFilterWhere(['createdTime' => $condition['createdTime']]);

        if (isset($condition['supplier'])) $query->andFilterWhere(['like', 'supplier', $condition['supplier']]);
        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if (isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if (isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if (isset($condition['supplierGoodsCode'])) $query->andFilterWhere(['like', 'supplierGoodsCode', $condition['supplierGoodsCode']]);

        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @param $condition
     * Date: 2019-03-18 15:02
     * Author: henry
     * @return array|ActiveDataProvider
     */
    public static function getSupplierGoodsById($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return new ActiveDataProvider([
            'query' => OaSupplierGoodsSku::find()->where(['supplierGoodsId' => $id]),
            'pagination' => ['pageSize' => 200]
        ]);
    }

    /**
     * @param $id
     * Date: 2019-03-18 15:53
     * Author: henry
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function deleteSupplierGoodsById($id)
    {
        $db = OaSupplierGoods::getDb();
        $trans = $db->beginTransaction();
        try {

            $res = OaSupplierGoods::deleteAll(['id' => $id]);
            if (!$res) {
                throw new \Exception('删除失败！');
            }
            $res = OaSupplierGoodsSku::deleteAll(['supplierGoodsId' => $id]);
            if (!$res) {
                throw new \Exception('删除失败！');
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = false;
        }
        return $msg;
    }

    /** 创建供应商信息
     * @param $condition
     * Date: 2019-03-26 16:02
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function createSupplierGoods($condition)
    {
        $db = Yii::$app->db;
        $tran = $db->beginTransaction;
        try{
            $model = new OaSupplierGoods();
            $model->attributes = $condition;
            if (!isset($condition['createdTime']) || !$condition['createdTime']) $model->createdTime = date('Y-m-d H:i:s');
            if (!$model->save()) {
                throw new \Exception('failed');
            }
            $goodsCode = $model->goodsCode;
            $supplierGoodsId = $model->id;
            $sql = "P_oa_SkuToSupplierGoodsSku '$goodsCode','$supplierGoodsId' ";
            $res = Yii::$app->db->createCommand($sql)->execute();
            if (!$res) {
                throw new \Exception('failed');
            }
            $tran->commit();
            $ret = true;
        }catch (\Exception $e){
            $tran->rollBack();
            $ret = [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
        return $ret;
    }

    /**
     * @param $condition
     * Date: 2019-03-14 17:53
     * Author: henry
     * @return array|bool
     */
    public static function updateSupplierGoods($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return false;
        }
        $model = OaSupplierGoods::findOne($id);
        $model->attributes = $condition;
        if (!isset($condition['updatedTime']) || !$condition['updatedTime']) $model->updatedTime = date('Y-m-d H:i:s');
        $res = $model->save();
        if ($res) {
            return true;
        } else {
            return [
                'code' => 400,
                //'message' => array_values($model->getErrors())[0][0],
                'message' => 'failed'
            ];
        }
    }

    /** 保存SKU信息
     * @param $condition
     * Date: 2019-03-18 17:09
     * Author: henry
     * @return bool
     */
    public static function updateSupplierGoodsSKU($condition)
    {
        $ids = isset($condition['id']) ? $condition['id'] : [];
        if (empty($ids)) {
            return false;
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $row) {
                if ($row['id']) {
                    $sku = OaSupplierGoodsSku::findOne(['id' => $row['id']]);
                    if (!empty($sku)) {
                        $sku->setAttributes($row);
                        if (!$sku->save()) {
                            throw new \Exception('保存失败！');
                        }
                    }
                } else {
                    $sku = new OaSupplierGoodsSku();
                    $sku->setAttributes($row);
                    if (!$sku->save()) {
                        throw new \Exception('保存失败！');
                    }
                }
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $why) {
            $msg = false;
        }
        return $msg;
    }


}