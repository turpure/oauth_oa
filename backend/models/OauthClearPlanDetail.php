<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "oauth_clearPlanDetail".
 *
 * @property int $id
 * @property int $planId
 * @property string $seller
 * @property string $suffix
 */
class OauthClearPlanDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_clearPlanDetail';
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
            [['planId'], 'integer'],
            [['seller','suffix'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'planId' => 'Plan ID',
            'seller' => 'Seller',
            'suffix' => 'Suffix',
        ];
    }
}
