<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_paypal".
 *
 * @property int $id
 * @property string $paypal
 * @property string $createDate
 * @property string $updateDate
 * @property int $status
 */
class OaPaypal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_paypal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createDate', 'updateDate'], 'safe'],
            [['paypal'], 'string', 'max' => 255],
            [['status'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'paypal' => 'Paypal',
            'createDate' => 'Create Date',
            'updateDate' => 'Update Date',
            'status' => 'Status',
        ];
    }
}
