<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_supplierOrderPaymentDetail".
 *
 * @property int $id
 * @property string $billNumber 订单编号
 * @property string $requestTime 请求时间
 * @property string $requestAmt 请求金额
 * @property string $paymentStatus 付款状态 已付款 未付款
 * @property string $paymentTime 付款时间
 * @property string $paymentAmt 付款金额
 * @property string $img 付款凭证（截图）
 * @property string $comment 备注
 * @property string $unpaidAmt 未付金额
 */
class OaSupplierOrderPaymentDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_supplierOrderPaymentDetail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['requestTime', 'paymentTime'], 'safe'],
            [['requestAmt', 'paymentAmt', 'unpaidAmt'], 'number'],
            [['billNumber', 'paymentStatus', 'img', 'comment'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'billNumber' => 'Bill Number',
            'requestTime' => 'Request Time',
            'requestAmt' => 'Request Amt',
            'paymentStatus' => 'Payment Status',
            'paymentTime' => 'Payment Time',
            'paymentAmt' => 'Payment Amt',
            'img' => 'Img',
            'comment' => 'Comment',
            'unpaidAmt' => 'Unpaid Amt',
        ];
    }
}
