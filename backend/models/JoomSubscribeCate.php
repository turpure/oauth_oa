<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.joom_subscribeCate".
 *
 * @property int $id
 * @property string $cateName
 * @property string $cateId
 * @property string $creator
 * @property string $createdDate
 */
class JoomSubscribeCate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.joom_subscribeCate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdDate'], 'safe'],
            [['cateName'], 'string', 'max' => 300],
            [['cateId'], 'string', 'max' => 200],
            [['creator'], 'string', 'max' => 20],
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
            'creator' => 'Creator',
            'createdDate' => 'Created Date',
        ];
    }
}
