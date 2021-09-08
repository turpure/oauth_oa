<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "oauth_stock_change_wyt_package_info".
 *
 * @property int $id
 * @property string $wyt_in_no
 * @property string $package_no
 * @property int $seller_case_no
 * @property string $length
 * @property string $width
 * @property string $height
 * @property string $weight
 * @property string $sku
 * @property int $quantity
 * @property string $update_time
 */
class OauthStockChangeWytPackageInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_stock_change_wyt_package_info';
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
            [['quantity', 'seller_case_no'], 'integer'],
            [['wyt_in_no', 'package_no', 'sku'], 'string'],
            [['length', 'width', 'height', 'weight'], 'number'],
            [['update_time'], 'safe'],
        ];
    }


}
