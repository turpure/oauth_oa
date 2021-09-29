<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proCenter.oa_dataMine".
 *
 * @property int $id
 * @property string $storeName
 * @property string $groupName
 * @property string $rate
 * @property string $createBy
 * @property string $createTime
 */
class OaGroupRule extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_group_rule';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => false,
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createTime', '$createBy','storeName','groupName','rate'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [

        ];
    }
}
