<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\models\OaGoods;
use backend\models\OaGoods1688;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku1688;
use backend\models\ShopElf\BGoods;
use backend\models\ShopElf\BGoodsSku;
use backend\modules\v1\models\ApiPurchaseTool;
use backend\modules\v1\utils\ProductCenterTools;
use yii\data\SqlDataProvider;
use yii\db\Exception;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class PurchaseToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiPurchaseTool';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    public $host = 'http://192.168.0.150:8087/';

    /** 清仓SKU
     * Date: 2020-04-29 11:19
     * Author: henry
     * @return array | mixed
     */
    public function actionClearSku()
    {
        try {
            return ApiPurchaseTool::clearSku();
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**非清仓SKU
     * @brief 拣货人
     * @return array | mixed
     */
    public function actionUnclearSku()
    {
        try {
            return ApiPurchaseTool::clearSku(1);
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 缺货管理
     * @return array | mixed
     */
    public function actionShortage()
    {
        try {
            return ApiPurchaseTool::shortage();
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 自动审核
     * @return array | mixed
     */
    public function actionChecking()
    {
        try {
            return ApiPurchaseTool::checkPurchaseOrder();
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief 同步差额
     * @return array | mixed
     */
    public function actionAutoSync()
    {
//        set_time_limit(0);
        ini_set("max_execution_time",0);
        try {
            return ApiPurchaseTool::checkPurchaseOrder($check = false);
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }


    ####################添加1688供应商#########################
    public function actionSearchSuppliers()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
            if (!$goodsCode) {
                return [
                    'code' => 400,
                    'message' => 'goodsCode can not be empty!'
                ];
            }
            $sql = "SELECT gs.nid,gs.goodsId,gs.SKU,SKUName,gs.property1,gs.property2,gs.property3,
                            ISNULL(sw.companyName,'无') as companyName,g16.style FROM B_GoodsSKU gs
					 LEFT JOIN B_GoodsSKUWith1688 sw ON gs.NID=sw.GoodsSKUID  AND sw.isDefault=1
					 LEFT JOIN B_Goods1688 g16 ON g16.GoodsID=gs.GoodsID and g16.specId=sw.specId  AND g16.offerid=sw.offerid 
					WHERE gs.sku LIKE '%{$goodsCode}%'  ";
//            $goodsSql = "SELECT DISTINCT companyName FROM B_Goods1688 sw LEFT JOIN B_Goods g ON sw.GoodsID=g.NID WHERE g.GoodsCode LIKE :goodsCode ";
            $goodsSql = "SELECT DISTINCT sw.companyName FROM B_Goods1688 sw
                    LEFT JOIN B_Goods g ON sw.GoodsID=g.NID WHERE ISNULL(companyName,'')<>'' AND g.GoodsCode LIKE '%{$goodsCode}%' ";

            $provider = new SqlDataProvider([
                'sql' => $sql,
                'db' => 'py_db',
                'sort' => [
                    'defaultOrder' => ['SKU' => SORT_ASC],
                    'attributes' => ['SKU'],
                ],
                'pagination' => [
                    'pageSize' => 100,
                ],
            ]);
            //var_dump($provider);exit;
            $userInfo = $provider->getModels();
            $suppliers = Yii::$app->py_db->createCommand($goodsSql)->queryAll();
            foreach ($userInfo as &$v) {
                /*$companySql = "SELECT DISTINCT offerid,companyName FROM B_Goods1688 WHERE goodsId = :goodsId";
                $res = Yii::$app->py_db->createCommand($companySql)->bindValues([':goodsId' => $v['goodsId']])->queryAll();
                foreach ($res as &$value){
                    $skuSql = "SELECT DISTINCT specId,style FROM B_Goods1688 WHERE companyName = '{$value['companyName']}'";
                    $styleInfo = Yii::$app->py_db->createCommand($skuSql)->queryAll();
                    $value['style'] = $styleInfo;
                }
                $v['values'] = $res ? $res : [['offerid' => '','companyName' => '无', 'style' => []]];
                */
                $companySql = "SELECT DISTINCT offerid,companyName FROM B_GoodsSKUWith1688 WHERE nid = :nid";
                $res = Yii::$app->py_db->createCommand($companySql)->bindValues([':nid' => $v['nid']])->queryAll();
                $res = ArrayHelper::getColumn($res, 'companyName');
                $v['values'] = $res ? $res : ['无'];
            }
            return [
                'skuInfo' => $userInfo,
                'companyName' => ArrayHelper::getColumn($suppliers, 'companyName'),
            ];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    public function actionDeleteSkuSuppliers(){
        $condition = Yii::$app->request->post('condition', []);
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        $companyName = isset($condition['companyName']) ? $condition['companyName'] : '';
        if (!$goodsCode) {
            return [
                'code' => 400,
                'message' => 'goodsCode can not be empty!'
            ];
        }
        $goods = BGoods::findOne(['GoodsCode' => $goodsCode]);
        if (!$goods) {
            return [
                'code' => 400,
                'message' => "Product $goodsCode can not be found!"
            ];
        }
        try {
            $sql = "DELETE FROM B_GoodsSKUWith1688 WHERE  GoodsSKUID IN (
		                SELECT gs.nid FROM B_GoodsSKU gs LEFT JOIN B_Goods g ON gs.GoodsID=g.nid 
		                WHERE GoodsCode='{$goodsCode}' 
		            ) ";
            if($companyName) $sql .= " AND companyName='{$companyName}'";
            $res = Yii::$app->py_db->createCommand($sql)->execute();
            if (!$res) {
                throw new Exception('Failed to save supplier info!');
            }
            return true;
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    public function actionSaveSkuSuppliers()
    {
        $condition = Yii::$app->request->post('condition', []);
        $skuInfo = isset($condition['skuInfo']) ? $condition['skuInfo'] : [];
        $transaction = Yii::$app->py_db->beginTransaction();
        try {
            foreach ($skuInfo as $info) {
                $num = Yii::$app->py_db->createCommand("SELECT count(1) FROM B_GoodsSKUWith1688 WHERE  GoodsSKUID=:nid AND companyName=:companyName")
                    ->bindValues([':nid' => $info['nid'], ':companyName' => $info['companyName']])->queryScalar();
                if ($num) {
                    $res1 = Yii::$app->py_db->createCommand()->update('B_GoodsSKUWith1688',
                        ['isDefault' => 0],
                        ['GoodsSKUID' => $info['nid']])
                        ->execute();

                    $res2 = Yii::$app->py_db->createCommand()->update('B_GoodsSKUWith1688',
                        ['isDefault' => 1],
                        ['GoodsSKUID' => $info['nid'], 'companyName' => $info['companyName']])
                        ->execute();
                    if (!$res1 || !$res2) {
                        throw new Exception('Failed to update supplier info!');
                    }
                }/*else{
                    $sql = "select distinct supplierLoginId from B_Goods1688 where offerid='{$info['offerid']}'";
                    $supplier = $res = Yii::$app->py_db->createCommand($sql)->queryAll();
                    $res = Yii::$app->py_db->createCommand()->insert('B_GoodsSKUWith1688',
                        ['GoodsSKUID' => $info['nid'], 'offerid' => $info['offerid'], 'specId' => $info['specId'],
                            'supplierLoginId' => $supplier['supplierLoginId'], 'companyName' => $info['companyName'], 'isDefault' => 1])
                        ->execute();
                    if (!$res) {
                        throw new Exception('Failed to save supplier info!');
                    }
                }*/
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /** 添加新供应商
     * Date: 2020-07-27 11:11
     * Author: henry
     * @return array|bool
     */
    public function actionAddSuppliers()
    {
        $condition = Yii::$app->request->post('condition', []);
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        $url = isset($condition['url']) ? $condition['url'] : [];
        try {
            $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $goodsCode]);
            $goods = OaGoods::findOne(['nid' => $goodsInfo['goodsId']]);
            if ($goods['vendor1'] == $url) {
                $goods->vendor1 = $url;
            } else if (!$goods['vendor2'] || $goods['vendor2'] == $url) {
                $goods->vendor2 = $url;
            } else {
                $goods->vendor3 = $url;
            }
            $res = $goods->save();
            if ($res) {
                return ProductCenterTools::sync1688Goods($goodsInfo['id']);
            } else {
                throw new Exception('Failed to update goods vendor');
            }

        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    public function actionSkuInfo()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
            $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $goodsCode]);
            $id = isset($goodsInfo['id']) ? $goodsInfo['id'] : 0;
            if(!$id){
                return [
                    'code' => 400,
                    'message' => '该产品不存在！'
                ];
            }
            $skuInfo = (new Query())->select("gs.*, ss.offerId, ss.specId,og.style")
                ->from('proCenter.oa_goodssku gs')
                ->leftJoin('proCenter.oa_goodsSku1688 ss', 'ss.goodsSkuId=gs.id')
                ->leftJoin('proCenter.oa_goods1688 og', 'og.specId=ss.specId and og.offerId=ss.offerId and og.infoId='.$id)
                ->where(['gs.infoId' => $id])->orderBy('gs.property1,gs.id')->all();
            //var_dump($skuInfo);exit;
            foreach ($skuInfo as &$v) {
                $goods = OaGoods1688::find()->select('offerId,specId,style')
                    ->where(['infoId' => $id, 'offerId' => $v['offerId']])->distinct()->asArray()->all();
                $v['selectData'] = array_merge([["offerId" => '无', "specId" => '无', 'style' => '无']], $goods);
            }
            return $skuInfo;
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /** 保存1688SKU信息 并同步到普源
     * Date: 2020-07-28 11:39
     * Author: henry
     * @return array|bool
     * @throws Exception
     */
    public function actionSaveSkuInfo()
    {
        $condition = Yii::$app->request->post('condition', []);
        $skuInfo = isset($condition['data']) ? $condition['data'] : [];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $skuIds = [];
            foreach ($skuInfo as $skuRow) {
                $infoId = $skuRow['infoId'];
                $id = $skuRow['id'];
                $item['SKU'] = $skuRow['sku'];
                $item['goodsSkuId'] = BGoodsSku::findOne(['SKU' => $skuRow['sku']])['NID'];
                $skuIds[] = $item;
                //保存SKU关联1688信息
                $specId = isset($skuRow['specId']) ? $skuRow['specId'] : '';
                $offerId = isset($skuRow['offerId']) ? $skuRow['offerId'] : '';
                if ($offerId) {
                    $count = OaGoods1688::find()->andFilterWhere(['infoId' => $infoId, 'offerId' => $offerId])->count();
                    if ($specId || $count == 1) {
                        $goodsSku1688 = OaGoodsSku1688::findOne(['goodsSkuId' => $id]);
                        if (!$goodsSku1688) {
                            $goodsSku1688 = new OaGoodsSku1688();
                            $goodsSku1688->goodsSkuId = $id;
                        }
                        if ($specId != '无') {
                            $goods1688 = OaGoods1688::find()->andFilterWhere(['infoId' => $infoId, 'offerId' => $offerId, 'specId' => $specId])->one();
                            $goodsSku1688->supplierLoginId = $goods1688->supplierLoginId;
                            $goodsSku1688->companyName = $goods1688->companyName;
                        } else {
                            $goodsSku1688->supplierLoginId = '';
                            $goodsSku1688->companyName = '';
                        }
                        $goodsSku1688->offerId = $offerId;
                        $goodsSku1688->specId = $specId;
                        $goodsSku1688->isDefault = 1;
                        $ss = $goodsSku1688->save();
                        if (!$ss) {
                            throw new \Exception("failed save 1688 goods sku info！");
                        }
                    }
                }
            }

            // 同步1688商品信息到普源
            $goodsInfo = OaGoodsinfo::findOne($infoId);
            $params['GoodsCode'] = $goodsInfo['goodsCode'];
            $params['goodsId'] = BGoods::findOne(['GoodsCode' => $goodsInfo['goodsCode']])['NID'];
            ProductCenterTools::_bGoods1688Import($params);
            ProductCenterTools::_bGoodsSkuWith1688Import($skuIds);
            ProductCenterTools::_addSupplier($params);//添加供应商

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }


}
