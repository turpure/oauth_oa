<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-07 11:02
 */

namespace backend\modules\v1\utils;
use yii\base\Arrayable;
use yii\data\DataProviderInterface;
use yii\rest\Serializer;


class PowerfulSerializer extends Serializer
{

    public function serialize($data)
    {
        if(is_array($data) && isset($data['provider'], $data['extra'])) {
            $provider = $data['provider'];
            $extra = $data['extra'];
            $ret = parent::serialize($provider);
            $ret['extra'] = $extra;
            return $ret;
        }

        return parent::serialize($data);
    }
}
