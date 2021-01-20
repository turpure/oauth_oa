<?php
/**
 * @name AbstractService.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-01-19 11:25
 */


namespace backend\modules\v1\services\shopify;

use GuzzleHttp\Psr7\Request;

abstract class AbstractService
{
    const DEFAULT_API_VERSION = '2019-07';
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_METHOD_DELETE = 'DELETE';

    /**
     * Domain of the Shopify store
     * 对应店铺
     * @var string
     */
    protected $myshopify_domain;

    protected $base_uri;

    protected $headers;

    protected $api_version = self::DEFAULT_API_VERSION;


    /**
     * Build our private API instance
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(
                    "Property '{$key}' does not exist on ".get_called_class()
                );
            }
            $this->{$key} = $value;
        }
        $this->init();
    }


    /**
     * Get current store domain
     *
     * @return string
     */
    public function getMyshopifyDomain()
    {
        return $this->myshopify_domain;
    }

    /**
     * Set the current store domain. Initialize
     * a new client, with new details
     *
     * @param string $domain
     * @return $this
     */
    public function setMyshopifyDomain($domain)
    {
        $this->myshopify_domain = $domain;
        $this->init();
        return $this;
    }


    /**
     * Set the API Version
     * @param $apiVersion
     * @return $this
     */
    public function setApiVersion($apiVersion)
    {
        $this->api_version = $apiVersion;
        $this->init();
        return $this;
    }

    /**
     * Get the API Version
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
        $this->init();
        return $this;
    }

    /**
     * Get the API Version
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Initialize our Client, using settings based on the app type
     *
     * @var void
     */
    abstract public function init();




}
