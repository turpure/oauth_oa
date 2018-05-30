<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-30 9:14
 */

namespace backend\components;


use Yii;
use yii\rbac\Rule;
use backend\models\Goods;

class GoodsRule extends Rule
{
    public $name = 'goods';
    public function execute($user, $item, $params)
    {
        $id = isset($params['id']) ? $params['id'] : null;
        if (!$id) {
            return false;
        }
        $model = Goods::findOne($id);
        if (!$model) {
            return false;
        }

        $username = Yii::$app->user->identity->username;
//        $role = Yii::$app->user->identity->role;
        if ($username == $model->creator) {
            return true;
        }
            return false;
    }


}