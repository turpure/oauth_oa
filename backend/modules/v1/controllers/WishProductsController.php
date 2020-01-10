<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-12-03
 * Time: 15:07
 * Author: henry
 */

/**
 * @name WishProductsController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-12-03 15:07
 */


namespace backend\modules\v1\controllers;

use backend\models\ShopeeRule;
use backend\models\WishRule;
use Yii;
use yii\helpers\ArrayHelper;

class WishProductsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWishProducts';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];


    //=======================================================================================
    //WISH 推送规则


    /**
     * 规则列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionRule()
    {
        $plat = Yii::$app->request->get('plat','wish');
        try {
            if($plat == 'wish'){
                $data = WishRule::find()->all();
            }elseif($plat == 'shopee'){
                $data = ShopeeRule::find()->all();
            }
            return $data;
        } catch (\Exception $why) {
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

            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $plat = ArrayHelper::getValue($condition, 'plat', 'wish');
            if($plat == 'wish'){
                $rule = WishRule::findOne($id);
                if (empty($rule)) {
                    $rule = new WishRule();
                    $condition['creator'] = $userName;
                }
            }elseif($plat == 'shopee'){
                $rule = ShopeeRule::findOne($id);
                if (empty($rule)) {
                    $rule = new WishRule();
                    $condition['creator'] = $userName;
                }
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save wish rule');
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
        $id = ArrayHelper::getValue($condition, 'id', '');
        $plat = ArrayHelper::getValue($condition, 'plat', 'wish');
        try {
            if($plat == 'wish'){
                WishRule::findOne($id)->delete();
            }elseif($plat == 'shopee'){
                ShopeeRule::findOne($id)->delete();
            }
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }








}