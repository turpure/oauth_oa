<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_supplier".
 *
 * @property int $id
 * @property string $supplierName
 * @property string $contactPerson1
 * @property string $phone1
 * @property string $contactPerson2
 * @property string $phone2
 * @property string $address
 * @property string $link1
 * @property string $link2
 * @property string $link3
 * @property string $link4
 * @property string $link5
 * @property string $link6
 * @property string $paymentDays
 * @property string $payChannel
 * @property string $purchaser
 * @property string $createTime
 * @property string $updateTime
 * @property int $supplierId 关联普源供的应商ID
 */
class OaSupplier extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_supplier';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createTime', 'updateTime'], 'safe'],
            [['supplierId'], 'integer'],
            [['supplierName', 'address', 'link1', 'link2', 'link3', 'link4', 'link5', 'link6'], 'string', 'max' => 255],
            [['contactPerson1', 'phone1', 'contactPerson2', 'phone2', 'paymentDays', 'payChannel', 'purchaser'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplierName' => 'Supplier Name',
            'contactPerson1' => 'Contact Person1',
            'phone1' => 'Phone1',
            'contactPerson2' => 'Contact Person2',
            'phone2' => 'Phone2',
            'address' => 'Address',
            'link1' => 'Link1',
            'link2' => 'Link2',
            'link3' => 'Link3',
            'link4' => 'Link4',
            'link5' => 'Link5',
            'link6' => 'Link6',
            'paymentDays' => 'Payment Days',
            'payChannel' => 'Pay Channel',
            'purchaser' => 'Purchaser',
            'createTime' => 'create Time',
            'updateTime' => 'update Time',
            'supplierId' => 'Supplier ID',
        ];
    }
}
