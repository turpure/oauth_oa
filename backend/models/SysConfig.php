<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "sys_config".
 *
 * @property string $id
 * @property string $name
 * @property string $value
 * @property string $memo
 * @property string $creator
 * @property string $createdTime
 * @property string $updatedTime
 */
class SysConfig extends \yii\db\ActiveRecord
{
    const LABEL_SET_SKU_NUM = 'labelSetSkuNum';

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sys_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['name', 'value', 'memo'], 'string', 'max' => 300],
            [['creator'], 'string', 'max' => 20],
        ];
    }

}
