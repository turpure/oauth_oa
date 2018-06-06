<?php

namespace backend\models;

use common\models\User;
use Yii;

/**
 * This is the model class for table "auth_position_menu".
 *
 * @property int $id
 * @property int $position_id
 * @property int $menu_id
 */
class AuthPositionMenu extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position_menu';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_id', 'menu_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => 'Position ID',
            'menu_id' => 'Menu ID',
        ];
    }

    /**
     * @return array|string|mixed
     * @throws \yii\web\ForbiddenHttpException
     */
    public static function getAuthMenuList(){
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        if($role === null || $role === AuthAssignment::ACCOUNT_EMPTY){
            throw new \yii\web\ForbiddenHttpException("There is a problem with the account. Please contact the administrator!");
        }
        else if ($role === AuthAssignment::ACCOUNT_ADMIN){
            $data = static::find()
                ->select('m.id,m.name,m.parent,m.route,m.order')
                ->from('menu m')
                ->orderBy('m.order')
                ->asArray()->all();
        }
        else {
            $data = static::find()
                ->select('m.id,m.name,m.parent,m.route,m.order')
                ->from('auth_position_menu pm')
                ->leftJoin('auth_position_child pc','pc.position_id=pm.position_id')
                ->leftJoin('auth_position p','pm.position_id=p.id')
                ->leftJoin('menu m','pm.menu_id=m.id')
                ->where(['pc.user_id' => $userId])
                ->orderBy('m.order')
                ->asArray()->all();
        }
        return self::childTree($data);
    }


    /** 子树格式
     * @param $data
     * @param int $pid
     * @return array
     */
    private static function childTree($data, $pid = null)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['parent'] == $pid) {
                $v['children'] = self::childTree($data, $v['id']);
                $tree[] = $v;
                //unset($data[$k]);
            }
        }
        return $tree;
    }



}
