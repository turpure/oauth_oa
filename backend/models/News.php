<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "news".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $detail 详情
 * @property int $star 星级
 * @property int $isTop 是否置顶
 * @property string $createdate 创建时间
 * @property string $updatedate 最后更新时间
 */
class News extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'news';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['star', 'isTop'], 'integer'],
            [['createDate', 'updateDate'], 'safe'],
            [['title', 'detail', 'creator'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'detail' => 'Detail',
            'star' => 'Star',
            'isTop' => 'Is Top',
            'createDate' => 'Create Date',
            'updateDate' => 'Update Date',
        ];
    }

    /*public function behaviors()
    {
        return //array_merge(parent::behaviors(),
            [
                [
                    'class' => TimestampBehavior::className(),
                    'attributes' => [
                        # 创建之前
                        ActiveRecord::EVENT_BEFORE_INSERT => ['createDate'],
                        //ActiveRecord::EVENT_BEFORE_UPDATE => 'updateDate',
                    ],
                    #设置默认值
                    //'value' => date('Y-m-d H:i:s')
                    'value' => new Expression('NOW()')
                ],
            ];
       // );
    }*/

}
