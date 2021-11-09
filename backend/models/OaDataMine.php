<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proCenter.oa_dataMine".
 *
 * @property int $id
 * @property string $proId
 * @property string $platForm
 * @property string $progress
 * @property string $creator
 * @property string $createTime
 * @property string $updateTime
 * @property string $detailStatus
 * @property string $cat
 * @property string $subCat
 * @property string $goodsCode
 * @property string $devStatus
 * @property string $mainImage
 * @property string $pyGoodsCode
 * @property int $infoId
 * @property string $spAttribute
 * @property int $isLiquid
 * @property int $isPowder
 * @property int $isMagnetism
 * @property int $isCharged
 * @property string $storeId
 * @property string $linkUrl1
 * @property string $linkUrl2
 * @property string $linkUrl3
 */
class OaDataMine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_dataMine';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => false,
            'updatedAtAttribute' => 'updateTime',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goodsCode'], 'unique'],
            [['createTime', 'updateTime','infoId'], 'safe'],
            [['isLiquid', 'isPowder', 'isMagnetism', 'isCharged'], 'integer'],
            [['storeId','proId', 'platForm', 'progress', 'creator', 'detailStatus', 'cat', 'subCat', 'goodsCode',
                'devStatus', 'mainImage', 'pyGoodsCode', 'spAttribute', 'linkUrl1', 'linkUrl2', 'linkUrl3'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'proId' => 'Pro ID',
            'platForm' => 'Plat Form',
            'progress' => 'Progress',
            'creator' => 'Creator',
            'createTime' => 'Create Time',
            'updateTime' => 'Update Time',
            'detailStatus' => 'Detail Status',
            'cat' => 'Cat',
            'subCat' => 'Sub Cat',
            'goodsCode' => 'Goods Code',
            'devStatus' => 'Dev Status',
            'mainImage' => 'Main Image',
            'pyGoodsCode' => 'Py Goods Code',
            'infoId' => 'Info ID',
            'spAttribute' => 'Sp Attribute',
            'isLiquid' => 'Is Liquid',
            'isPowder' => 'Is Powder',
            'isMagnetism' => 'Is Magnetism',
            'isCharged' => 'Is Charged',
            'Store Id' => 'Store Id',
        ];
    }
}
