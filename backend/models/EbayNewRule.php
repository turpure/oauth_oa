<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for collection "ebay_new_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $cids
 * @property mixed $index
 * @property mixed $title
 * @property mixed $itemId
 * @property mixed $soldEnd
 * @property mixed $country
 * @property mixed $visitEnd
 * @property mixed $priceEnd
 * @property mixed $soldStart
 * @property mixed $titleType
 * @property mixed $sort
 * @property mixed $pageSize
 * @property mixed $priceStart
 * @property mixed $visitStart
 * @property mixed $marketplace
 * @property mixed $popularStatus
 * @property mixed $sellerOrStore
 * @property mixed $storeLocation
 * @property mixed $salesThreeDayFlag
 * @property mixed $orderColumn
 * @property mixed $listedTime
 * @property mixed $itemLocation
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 * @property mixed $ruleName
 * @property mixed $ruleMark
 * @property mixed $publishedSite
 * @property mixed $site
 *
 */
class EbayNewRule extends \yii\mongodb\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            'updatedAtAttribute' => 'updatedDate',
            'value' => date('Y-m-d H:i:s'),
        ],];
    }

    public function beforeSave($insert=true)
    {
        $site = [
            '美国' => ['country' => 1, 'marketplace' => 'EBAY_US'],
            '英国' => ['country' => 5, 'marketplace' => 'EBAY_GB'],
            '德国' => ['country' => 3, 'marketplace' => 'EBAY_DE'],
            '澳大利亚' => ['country' => 4, 'marketplace' => 'EBAY_AU'],
        ];
        $inputSite = $this->getAttribute('publishedSite');
        $ret= [];
        foreach ($inputSite as $inSite) {
            $ret[] = [$site[$inSite]['marketplace'] => $site[$inSite]['country']];
        }
        $this->setAttributes(['site' => $ret]);

        if(parent::beforeSave($insert)) {

            $defaultAttributes = [
                'cids' =>'', 'index' => 1, 'title' => '','itemId' => '','country' => 1,'marketplace' => '','titleType' => 1,
                'sort' => 'DESC', 'pageSize' => 20, 'salesThreeDayFlag' => '', 'sellerOrStore' => '',
                'orderColumn' => 'last_modi_time','itemLocation' => [], 'isUsed' => 1,
                ];
            $this->setAttributes($defaultAttributes);
        }
        return true;

    }

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['product_engine', 'ebay_new_rule'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'cids',
            'index',
            'title',
            'itemId',
            'soldEnd',
            'country',
            'visitEnd',
            'priceEnd',
            'soldStart',
            'titleType',
            'sort',
            'pageSize',
            'priceStart',
            'visitStart',
            'marketplace',
            'popularStatus',
            'sellerOrStore',
            'storeLocation',
            'salesThreeDayFlag',
            'orderColumn',
            'listedTime',
            'itemLocation',
            'creator',
            'createdDate',
            'updatedDate',
            'ruleName',
            'ruleMark',
            'publishedSite',
            'site',
            'isUsed',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['isUsed','cids', 'index', 'title', 'itemId', 'soldEnd', 'country', 'visitEnd', 'priceEnd', 'soldStart', 'titleType', 'sort', 'pageSize', 'priceStart', 'visitStart', 'marketplace', 'popularStatus', 'sellerOrStore', 'storeLocation', 'salesThreeDayFlag', 'orderColumn', 'listedTime', 'itemLocation', 'creator', 'createdDate', 'updatedDate','ruleName','ruleMark','publishedSite','site'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'cids' => 'Cids',
            'index' => 'Index',
            'title' => 'Title',
            'itemId' => 'Item ID',
            'soldEnd' => 'Sold End',
            'country' => 'Country',
            'visitEnd' => 'Visit End',
            'priceEnd' => 'Price End',
            'soldStart' => 'Sold Start',
            'titleType' => 'Title Type',
            'sort' => 'Sort',
            'pageSize' => 'Page Size',
            'priceStart' => 'Price Start',
            'visitStart' => 'Visit Start',
            'marketplace' => 'Marketplace',
            'popularStatus' => 'Popular Status',
            'sellerOrStore' => 'Seller Or Store',
            'storeLocation' => 'Store Location',
            'salesThreeDayFlag' => 'Sales Three Day Flag',
            'orderColumn' => 'Order Column',
            'listedTime' => 'Listed Time',
            'itemLocation' => 'Item Location',
            'creator' => 'Creator',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
            'ruleName' => 'Rule Name',
            'ruleMark' => 'Rule Mark',
            'publishedSite' => 'publishedSite',
            'site' => 'site'
        ];
    }
}
