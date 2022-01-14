<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:01
 */

namespace mdm\admin\models;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

class Position extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position'], 'required'],
            [['position'],'string'],
            [['id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position' => '职位',
        ];
    }

    public static function getPositionUser($position = '')
    {
        $data = PositionChild::find()
            ->select('u.id,u.username')
            ->join('INNER JOIN', 'user u' ,'u.id=user_id')
            ->join('INNER JOIN', 'auth_position p', 'p.id=position_id')
            ->andFilterWhere(['p.position' => $position, 'u.status' => User::STATUS_ACTIVE])
//            ->where(['p.position' => $position, 'u.status' => User::STATUS_ACTIVE])
            ->orderBy('u.id')
            ->asArray()->all();
        return ArrayHelper::map($data,'id','username');
    }

}
