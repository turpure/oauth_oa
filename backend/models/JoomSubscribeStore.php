<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.joom_subscribeStore".
 *
 * @property int $id
 * @property string $storeName
 * @property string $storeId
 * @property string $creator
 * @property string $createdDate
 */
class JoomSubscribeStore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.joom_subscribeStore';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['createdDate'], 'safe'],
            [['storeName'], 'string', 'max' => 300],
            [['storeId'], 'string', 'max' => 200],
            [['creator'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'storeName' => 'Store Name',
            'storeId' => 'Store ID',
            'creator' => 'Creator',
            'createdDate' => 'Created Date',
        ];
    }
}
