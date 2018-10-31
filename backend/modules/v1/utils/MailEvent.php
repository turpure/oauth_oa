<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-10-31
 * Time: 14:16
 */

namespace backend\modules\v1\utils;


use yii\base\Event;

class MailEvent extends Event
{

    public $email;

    public $subject;

    public $content;

}