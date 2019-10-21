<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayProducts;
use backend\models\WishProducts;
use backend\models\JoomProducts;
use backend\models\RecommendEbayNewProductRule;
use yii\helpers\ArrayHelper;
use Yii;

class ProductsEngineController extends AdminController
{

    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    /**
     * @brief recommend  products
     * @return mixed
     */
    public function actionRecommend()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type','');
            if ($plat === 'ebay') {
                if($type === 'new') {

                    $db = Yii::$app->mongodb;
                    $cur = $db->getCollection('ebay_new_product')->find();
                    foreach ($cur as $row) {
                        $ret[] = $row;
                    }
                    return $ret;
                }
                if ($type === 'hot') {

                }
                else {
                    $station = \Yii::$app->request->get('status','US');
                    return EbayProducts::find()->where(['station' => $station])->all();
                }
            }
            if ($plat === 'wish') {
                return WishProducts::find()->all();
            }

            if ($plat === 'joom') {
                return JoomProducts::find()->all();
            }
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }

    /**
     * 规则列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionRule()
    {
        try {
            return RecommendEbayNewProductRule::find()->all();

        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 增加规则
     * @return array
     */
    public function actionSaveRule()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $attrs = [
                'soldStart' => ArrayHelper::getValue($condition, 'soldStart', ''),
                'soldEnd' => ArrayHelper::getValue($condition, 'soldEnd', ''),
                'visitStart' => ArrayHelper::getValue($condition, 'visitStart', ''),
                'visitEnd' => ArrayHelper::getValue($condition, 'visitEnd', ''),
                'priceEnd' => ArrayHelper::getValue($condition, 'priceEnd', ''),
                'priceStart' => ArrayHelper::getValue($condition, 'priceStart', ''),
                'country' => ArrayHelper::getValue($condition, 'country', ''),
                'popularStatus' => ArrayHelper::getValue($condition, 'popularStatus', ''),
                'sellerOrStore' => ArrayHelper::getValue($condition, 'sellerOrStore', ''),
                'storeLocation' => ArrayHelper::getValue($condition, 'storeLocation', ''),
                'salesThreeDayFlag' => ArrayHelper::getValue($condition, 'salesThreeDayFlag', ''),
                'listedTime' => ArrayHelper::getValue($condition, 'listedTime', ''),
                'itemLocation' => ArrayHelper::getValue($condition, 'itemLocation', ''),
                'creator' => ArrayHelper::getValue($condition, 'creator', ''),
                'createdDate' => date('Y-m-d H:i:s'),
                'updatedDate' => date('Y-m-d H:i:s'),
            ];
            $rule = RecommendEbayNewProductRule::findOne($id);
            if(empty($rule)) {
                $rule = new RecommendEbayNewProductRule();
            }
            $rule->setAttributes($attrs);
            if (!$rule->save()) {
                throw new \Exception('fail to add new rule');
            }
            return [];
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteRule()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id','');
        try {
            RecommendEbayNewProductRule::findOne($id)->delete();
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }



}
