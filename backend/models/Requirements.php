<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use \yii\db\ActiveRecord;
/**
 * This is the model class for table "oauth_requirements".
 *
 * @property int $id
 * @property string $name
 * @property string $detail
 * @property string $creator
 * @property int $type
 * @property int $priority
 * @property string $createdDate
 * @property string $beginDate
 * @property string $endDate
 * @property string $finishedDate
 * @property int $status
 * @property string $result
 * @property string $feedBack
 * @property string $processingPerson
 */
class Requirements extends ActiveRecord
{
    const SCHEDULE_TO_BE_AUDITED  = 1;     //待审核
    const SCHEDULE_FAILED         = 2;     //已驳回
    const SCHEDULE_DEALING        = 3;     //处理中
    const SCHEDULE_DEALT          = 4;     //处理完成
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        //return 'oauth_requirements';
        return 'requirement';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * @brief set behaviors
     *
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                [
                    'class' => TimestampBehavior::className(),
                    'attributes' => [
                        # 创建之前
                        ActiveRecord::EVENT_BEFORE_INSERT => ['createdDate'],
                    ],
                    #设置默认值
                    'value' => date('Y-m-d H:i:s')
                ]
            ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'detail', 'creator', 'auditor', 'result', 'feedBack', 'processingPerson','img'], 'string'],
            [['priority','type','status', 'schedule'],'integer'],
            [['createdDate', 'beginDate', 'endDate', 'auditDate'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'detail' => 'Detail',
            'img' => 'Img',
            'creator' => 'Creator',
            'type' => 'Type',
            'priority' => 'Priority',
            'createdDate' => 'Created Date',
            'beginDate' => 'Begin Date',
            'endDate' => 'End Date',
            'auditor' => 'Auditor',
            'auditDate' => 'Audit Date',
            'schedule' => 'Schedule',
            'status' => 'Status',
            'result' => 'Result',
            'feedBack' => 'Feed Back',
            'processingPerson' => 'Processing Person',
        ];
    }
}
