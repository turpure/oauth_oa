<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for collection "operation.ebay_refund".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $amountValue
 * @property string $orderId
 * @property string $currency
 * @property string $suffix
 * @property string $transactionId
 * @property string $transactionDate
 */
class EbayRefund extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['operation', 'ebay_refund'];
    }


    public function attributes()
    {
        return [
            '_id',
            'amountValue',
            'orderId',
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
            [['amountValue', 'orderId', 'currency', 'suffix', 'transactionId', 'transactionDate'], 'safe'],
        ];
    }





}
