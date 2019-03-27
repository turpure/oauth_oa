<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_joomToWish".
 *
 * @property int $id
 * @property string $greaterEqual
 * @property string $less
 * @property string $addedPrice
 * @property string $createDate
 * @property string $updateDate
 */
class OaJoomToWish extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_joomToWish';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['addedPrice'], 'number'],
            [['createDate', 'updateDate'], 'safe'],
            [['greaterEqual', 'less'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'greaterEqual' => 'Greater Equal',
            'less' => 'Less',
            'addedPrice' => 'Added Price',
            'createDate' => 'Create Date',
            'updateDate' => 'Update Date',
        ];
    }
}
