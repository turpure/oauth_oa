<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "oa_cleanOffline".
 *
 * @property int $id
 * @property string $sku
 * @property string $skuType 导入，扫描
 * @property string $checkStatus 初始化,已找到,拣错货
 * @property string $creator 创建人
 * @property string $createdTime
 * @property string $updatedTime
 */
class OaCleanOffline extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oa_cleanOffline';
    }


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
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['sku'], 'string', 'max' => 500],
            [['checkStatus'], 'string', 'max' => 10],
            [['creator', 'skuType'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'skuType' => 'Sku Type',
            'checkStatus' => 'Check Status',
            'creator' => 'Creator',
            'createdTime' => 'Created Time',
            'updatedTime' => 'Updated Time',
        ];
    }
}
