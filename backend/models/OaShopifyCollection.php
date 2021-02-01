<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shopifyCollection".
 *
 * @property int $id
 * @property string $suffix
 * @property string $title
 * @property string $image
 * @property string $template_suffix
 * @property string $sort_order
 * @property string $coll_id
 * @property string $body_html
 * @property string $handle
 * @property string $admin_graphql_api_id
 * @property string $published_scope
 * @property string $updated_at
 * @property string $published_at
 */
class OaShopifyCollection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyCollection';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coll_id', 'suffix', 'handle', 'title','sort_order','admin_graphql_api_id','image',
                'updated_at','published_at','template_suffix','body_html','published_scope'], 'string', 'max' => 255],
        ];
    }

}
