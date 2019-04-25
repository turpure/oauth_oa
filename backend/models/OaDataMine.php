<?php

namespace backend\models;

use Yii;

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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createTime', 'updateTime'], 'safe'],
            [['infoId', 'isLiquid', 'isPowder', 'isMagnetism', 'isCharged'], 'integer'],
            [['proId', 'platForm', 'progress', 'creator', 'detailStatus', 'cat', 'subCat', 'goodsCode', 'devStatus', 'mainImage', 'pyGoodsCode', 'spAttribute'], 'string', 'max' => 255],
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
        ];
    }
}
