<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.joom_category".
 *
 * @property int $id
 * @property string $cateName
 * @property string $cateId
 */
class JoomCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.joom_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cateName', 'cateId'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cateName' => 'Cate Name',
            'cateId' => 'Cate ID',
        ];
    }
}
