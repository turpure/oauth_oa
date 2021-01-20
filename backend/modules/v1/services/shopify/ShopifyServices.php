<?php
/**
 * @name ShopifyServices.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-01-19 10:36
 */

namespace backend\modules\v1\services\shopify;


use backend\modules\v1\services\Logger;
use backend\modules\v1\utils\Helper;
use yii\db\Exception;

class ShopifyServices extends AbstractService
{
    /**
     * getApiKey
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return string
     */
    public function getApiKey()
    {
        $sql = "SELECT apikey FROM [dbo].[S_ShopifySyncInfo]";
        $keyQuery = \Yii::$app->py_db->createCommand($sql)->queryOne();
        $apiKey = $keyQuery ? $keyQuery['apikey'] : '';
        return $apiKey;
    }

    /**
     * getPassword
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return mixed
     */
    public function getPassword()
    {
        $pwdSql = "SELECT password FROM [dbo].[S_ShopifySyncInfo] WHERE hostname='{$this->myshopify_domain}'";
        $pwdQuery = \Yii::$app->py_db->createCommand($pwdSql)->queryOne();
        return $pwdQuery['password'];
    }

    /**
     * createBasicAuthHeader
     * @param $apiKey
     * @param $password
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return string
     */
    public function createBasicAuthHeader($apiKey, $password)
    {
        $input = "{$apiKey}:{$password}";
        $input = base64_encode($input);
        return "Basic {$input}";
    }

    /**
     * getUrl
     * @param $account
     * @param $endpoint
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return string
     */
    public function init()
    {
        $this->base_uri = sprintf(
            "https://%s:%s@%s.myshopify.com/admin/api/%s/",
            $this->getApiKey(),
            $this->getPassword(),
            $this->myshopify_domain,
            $this->getApiVersion()
        );
//        $this->headers = array(
//            'Authorization' => $this->createBasicAuthHeader(
//                $this->getApiKey(),
//                $this->getPassword()
//            )
//        );
        return $this;
    }

    /**
     * Create a new Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return bool
     */
    public function createProduct($product)
    {
        try {
            $endpoint = 'products.json';
            $url = $this->base_uri . $endpoint;
            $res = Helper::post($url, json_encode(['product' => $product]));
            //$res = [201, ['product'=>['id' => 4914039488589]]];
            //var_dump($res);
            if ($res[0] > 400) {
                $out = false;
                $product_id = '';
                $content = json_encode($res[1]['errors']);
            } else {
                $out = true;
                $content = 'success';
                $product_id = $res[1]['product']['id'];
            }
            $params = [
                'suffix' => $this->myshopify_domain,
                'sku' => $product['sku'],
                'product_id' => (string) $product_id,
                'creator' => \Yii::$app->user->identity->username,
                'type' => 'product',
                'content' => $content
            ];
            $aa = Logger::shopifyLog($params);
//            var_dump($aa);
            return $product_id;
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * Get a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return array
     */
    public function getProduct($product_id)
    {
        $endpoint = 'products/' . $product_id . '.json';
        $url = $this->base_uri . $endpoint;
        $res = Helper::post($url, '', [], 'GET');
        if($res[0] == 200){
            return $res[1]['product'];
        }else{
            return [];
        }
    }

    /**
     * Get Images List a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return array
     */
    public function getImages($product_id)
    {
        $endpoint = 'products/' . $product_id . '/images.json';
        $url = $this->base_uri . $endpoint;
        $res = Helper::post($url, '', [], 'GET');
        if($res[0] == 200){
            return $res[1]['images'];
        }else{
            return [];
        }
    }

    /**
     * Get Images List a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return string
     */
    public function updateImages($product_id, $image_id, $variant_ids, $sku)
    {
        $endpoint = 'products/' . $product_id . '/images/' . $image_id . '.json';
        $url = $this->base_uri . $endpoint;
        $data = [
            'image' => [
                'id' => $image_id,
                'variant_ids' => $variant_ids
            ]
        ];
        $res = Helper::post($url, json_encode($data), [], 'PUT');
        if($res[0] >= 400){
            return json_encode($res[1]);
        }else{
            return 'success';
        }
    }


}
