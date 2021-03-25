<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "oauth_clearPlan".
 *
 * @property int $id
 * @property string $goodsCode
 * @property string $planNumber
 * @property string $comment
 * @property int $isRemoved
 * @property string $createdTime
 * @property string $sellers
 */
class OauthClearPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_clearPlan';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goodsCode', 'planNumber', 'comment'], 'string'],
            [['createdTime','isRemoved','sellers'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goodsCode' => 'Goods Code',
            'sellers' => 'Sellers',
            'isRemoved' => 'is removed',
            'planNumber' => 'Plan Number',
            'comment' => 'Comment',
            'createdTime' => 'Created Time',
        ];
    }
}
