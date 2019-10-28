<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proEngine.recommend_ebayNewProductRule".
 *
 * @property int $id
 * @property int $parentId
 * @property int $category
 */
class EbayDeveloperCategory extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.ebay_developer_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['categoryId','developer'], 'required', 'message' => '{attribute}不能为空'],
            [['categoryId'], 'integer'],
            [['developer'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoryId' => 'Category Id',
            'developer' => 'Developer',
        ];
    }

    /**
     * Date: 2019-10-28 14:22
     * Author: henry
     * @return \yii\db\ActiveQuery
     */
    public function getCategory(){

        // 第一个参数为要关联的子表模型类名，
        // 第二个参数指定 通过子表的user_id，关联主表的usesr_id字段
        // 这里写清楚点大概意思就是User.user_id => Order.user_id
        return $this->hasOne(EbayCategory::className(), ['id' => 'categoryId']);
    }

}
