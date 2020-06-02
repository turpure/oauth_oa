<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-05-29
 * Time: 16:02
 * Author: henry
 */
/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2020-05-29 16:02
 */


namespace backend\modules\v1\controllers;


use backend\models\OaGoodsinfo;
use backend\models\OaJoomSuffix;
use backend\models\OaSmtGoods;
use backend\models\OaSmtGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\modules\v1\models\ApiGoodsinfo;
use Yii;
class TestController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public  function actionTest(){
        try {

            $request = Yii::$app->request;
            $condition = $request->post()['condition'];
            $id = $condition['id'];
            $accounts = $condition['account'];
//            $res = ApiGoodsinfo::uploadToJoomBackstage($infoId, $account);

            if (!is_array($accounts)) {
                $accounts = [$accounts];
            }
            $row = [
                'parent_sku' => '', 'brand' => '', 'description' => '',
                'tags' => '', 'upc' => '', 'color' => '', 'sku' => '', 'name' => '', 'hs_code' => '',
                'size' => '', 'inventory' => '', 'price' => '', 'msrp' => '', 'shipping' => '',
                'shipping_weight' => '', 'shipping_height' => '', 'shipping_length' => '', 'shipping_width' => '',
                'main_image' => '', 'product_main_image' => '', 'variation_main_image' => '', 'extra_images' => '',
                'landing_page_url' => '', 'dangerous_kind' => 'notDangerous', 'declaredValue' => ''
            ];

            if (is_numeric($id)) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
            } else {
                $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
                $id = $goodsInfo['id'];
            }
            $smtSku = OaSmtGoodsSku::find()->where(['infoId' => $id])->asArray()->all();
            $smtInfo = OaSmtGoods::find()->where(['infoId' => $id])->asArray()->one();
//            $keyWords = static::preKeywords($smtInfo);
//            $title = static::getTitleName($keyWords, self::JoomTitleLength);
//            var_dump($smtInfo);exit;

            foreach ($accounts as $account) {
//                $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
//                $imageInfo = static::getJoomImageInfo($smtInfo, $joomAccounts);
//                $row['parent_sku'] = $smtInfo['sku'] . $joomAccounts['skuCode'];
                #获取账号TOKEN
                $sql = "SELECT AliasName AS suffix, AccessToken AS token FROM [dbo].[S_AliSyncInfo] 
                        WHERE AliasName='{$account}' ORDER BY AliasName;";
                $tokens = Yii::$app->py_db->createCommand($sql)->queryOne();
                #获取分类必填信息
                $url = "";
                var_dump($tokens);exit;


                return $res;
            }
        } catch (\Exception $why) {
            return ['code' => 400, 'message'=>$why->getMessage()];
        }
    }

}
