<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "Y_PayPalTransactions".
 *
 * @property int $id
 * @property string $paypal_account
 * @property string $transaction_id
 * @property string $transaction_date
 * @property string $transaction_type
 * @property string $transaction_type_description
 * @property string $transaction_status
 * @property string $transaction_status_description
 * @property string $currecny_code
 * @property number $transaction_amount
 * @property number $transaction_fee
 * @property number $transaction_net_amount
 * @property string $payer_email
 * @property string $payer_full_name
 * @property string $update_time
 */
class YPayPalTransactions extends \yii\db\ActiveRecord
{
    public $DateTime;
    public $Name;
    public $Type;
    public $Status;
    public $Currency;
    public $Gross;
    public $Fee;
    public $Net;
    public $FromEmailAddress;
    public $ToEmailAddress;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Y_PayPalTransactions';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => false,
            'updatedAtAttribute' => 'update_time',
            'value' => date('Y-m-d H:i:s')
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_amount','transaction_fee','transaction_net_amount'], 'number'],
            [['paypal_account', 'transaction_id','transaction_date','transaction_type', 'transaction_type_description',
                'transaction_status','transaction_status_description','currecny_code','payer_email',
                'payer_full_name','update_time'], 'string'],
        ];
    }

}
