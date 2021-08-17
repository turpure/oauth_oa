<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for collection "operation.ebay_new_fee".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $amountValue
 * @property string $feeType
 * @property string $currency
 * @property string $suffix
 * @property string $transactionId
 * @property string $transactionDate
 */
class EbayStoreFee extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['operation', 'ebay_new_fee'];
    }


    public function attributes()
    {
        return [
            '_id',
            'amountValue',
            'feeType',
            'currency',
            'suffix',
            'transactionId',
            'transactionDate',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['amountValue', 'feeType', 'currency', 'suffix', 'transactionId', 'transactionDate'], 'safe'],
        ];
    }





}
