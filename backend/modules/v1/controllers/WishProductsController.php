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

use backend\models\WishRule;
use Yii;
use backend\modules\v1\models\ApiWishProducts;
use yii\helpers\ArrayHelper;

class WishProductsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWishProducts';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];


    public function actionRecommend()
    {
        try {
            return ApiWishProducts::recommend();

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    //=======================================================================================
    //WISH 推送规则

    /**
     * 立即执行规则
     * @return array
     */
    public function actionRunRule()
    {
        try {
            $condition = Yii::$app->request->post('condition');
            $ruleType = Yii::$app->request->get('type', '');
            $ruleId = $condition['ruleId'];
            return ApiProductsEngine::run($ruleType, $ruleId);


        } catch (\Exception $why) {
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
            return WishRule::find()->all();
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
            $rule = WishRule::findOne($id);
            if (empty($rule)) {
                $rule = new WishRule();
                $condition['creator'] = $userName;
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
        try {
            WishRule::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //=======================================================================================
    //WISH 分配规则






}