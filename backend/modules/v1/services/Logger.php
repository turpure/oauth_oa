<?php
/**
 * @desc LoggerService.
 * @author: turpure
 * @since: 2019-09-26 9:17
 */

namespace backend\modules\v1\services;

use backend\models\OaIbayLog;

class Logger
{
    /**
     * @brief 记录导入ibay的日志
     * @param $logData
     * @return bool
     */
    public static function ibayLog($logData)
    {
        $userName = \Yii::$app->user->identity->username;
       $log = new OaIbayLog();
       $attrs = [
           'userName' => $userName,
           'infoId' => $logData['infoId'],
           'ibayTemplateId' => $logData['ibayTemplateId'],
           'result' => $logData['result'],
           'platForm' => $logData['platForm'],
           'createdDate' => date('Y-m-d H:i:s')
       ];
       $log->setAttributes($attrs);
       return $log->save();
    }
}