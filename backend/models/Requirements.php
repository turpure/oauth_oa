<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "oauth_requirements".
 *
 * @property int $id
 * @property string $name
 * @property string $detail
 * @property string $creator
 * @property string $type
 * @property string $priority
 * @property string $createdDate
 * @property string $beginDate
 * @property string $endDate
 * @property string $finishedDate
 * @property string $status
 * @property string $result
 * @property string $feedBack
 * @property string $processingPerson
 */
class Requirements extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_requirements';
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
            [['name', 'detail', 'creator', 'type', 'priority', 'status', 'result', 'feedBack', 'processingPerson'], 'string'],
            [['createdDate', 'beginDate', 'endDate', 'finishedDate'], 'safe'],
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
            'creator' => 'Creator',
            'type' => 'Type',
            'priority' => 'Priority',
            'createdDate' => 'Created Date',
            'beginDate' => 'Begin Date',
            'endDate' => 'End Date',
            'finishedDate' => 'Finished Date',
            'status' => 'Status',
            'result' => 'Result',
            'feedBack' => 'Feed Back',
            'processingPerson' => 'Processing Person',
        ];
    }
}
