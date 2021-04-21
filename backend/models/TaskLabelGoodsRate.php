<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "task_label_goods_rate".
 *
 * @property string $id
 * @property string $goodsCode
 * @property float $rate
 * @property string $creator
 * @property string $createdTime
 * @property string updatedTime
 */
class TaskLabelGoodsRate extends \yii\db\ActiveRecord
{

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
        return 'task_label_goods_rate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['rate'], 'safe'],
            [['goodsCode'], 'string', 'max' => 50],
            [['creator'], 'string', 'max' => 20],
        ];
    }

}
