<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_Goods1688".
 *
 * @property int $NID
 * @property int $GoodsID
 * @property string $offerid
 * @property string $specId
 * @property string $subject
 * @property string $style
 * @property int $multiStyle
 * @property string $supplierLoginId
 * @property string $companyName
 */
class BGoods1688 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_Goods1688';
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
            [['NID', 'multiStyle', 'GoodsID'], 'integer'],
            [['offerid', 'specId', 'subject', 'style', 'supplierLoginId', 'companyName'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'GoodsID' => 'Goods Id',
            'multiStyle' => 'Multi Style',
            'style' => 'Style',
            'offerid' => 'Offerid',
            'specId' => 'Spec Id',
            'subject' => 'Subject',
            'supplierLoginId' => 'Supplier Login Id',
            'companyName' => 'Company Name',
        ];
    }
}
