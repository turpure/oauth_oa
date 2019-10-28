<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-28 14:14
 */

namespace backend\modules\v1\models;

use backend\models\EbayHotProduct;
use Yii;
use yii\helpers\ArrayHelper;
use backend\modules\v1\controllers\OaGoodsController;
class ApiProductsEngine
{

    /**
     * 认领
     * @param $plat
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function accept($plat, $type,$id)
    {
        $username = Yii::$app->user->identity->username;
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {

            // ebay 新品认领
            if ($type === 'new') {
                $col = $db->getCollection('ebay_new_product');
            }

            // ebay 爆品认领
            if ($type === 'hot') {
                $col = $db->getCollection('ebay_hot_product');
            }
            $doc = $col->findOne(['_id' => $id]);



            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $accept = ArrayHelper::getValue($doc, 'accept', []);
            if (!empty($accept)) {
                throw new \Exception('产品已被认领');
            }
            $accept[] = $username;
            $col->update(['_id' => $id], ['accept' => array_unique($accept)]);


            // 转至逆向开发
            $product_info = ['img' => $doc['mainImage'], 'cate' => '女人世界','stockUp' => '否',
                'subCate' => '女包', 'salePrice' =>$doc['price'], 'flag' =>'backward'
            ];
            Yii::$app->request->setBodyParams(['condition' => $product_info]);
            Yii::$app->runAction('/v1/oa-goods/dev-create');

            return $col->findOne(['_id' => $id]);
        }

    }

    public static function refuse($plat, $type,$id)
    {
        $username = Yii::$app->user->identity->username;
        $refuseReason = Yii::$app->request->post('reason','拒绝');
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {

            // ebay 新品
            if ($type === 'new') {
                $col = $db->getCollection('ebay_new_product');
            }

            // ebay 爆品
            if ($type === 'hot') {
                $col = $db->getCollection('ebay_hot_product');
            }
            $doc = $col->findOne(['_id' => $id]);
            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $refuse = ArrayHelper::getValue($doc, 'accept', []);
            $refuse[$username] = $refuseReason;
            $col->update(['_id' => $id], ['refuse' => array_unique($refuse)]);

            return $col->findOne(['_id' => $id]);

        }
    }

}