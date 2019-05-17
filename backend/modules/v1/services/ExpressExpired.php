<?php
/**
 * @brief 标记物流超时的订单
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-16 16:44
 */

namespace backend\modules\v1\services;


class ExpressExpired
{
    public static function getDb($name)
    {
        return \Yii::$app->$name;
    }
    /**
     * @brief 获取订单
     * @return mixed
     */
    public static function getTrades()
    {
        $sql = "select pt.nid, logs,name from p_tradeun(nolock) as pt 
          LEFT JOIN P_TradeLogs(nolock) as plog on cast(pt.Nid as varchar(20)) = plog.tradenid 
          LEFT JOIN b_logisticWay as bw on pt.logicsWayNid = bw.nid where PROTECTIONELIGIBILITYTYPE='缺货订单'  
          and (pt.trackNo is not null and plog.logs like 
          '%预获取转单号成功%' or plog.logs like '%跟踪号成功,跟踪号%'or plog.logs like '%提交订单成功!   跟踪号:%')";
        $db = static::getDb('py_db');
        return  $db->createCommand($sql)->queryAll();
    }

    /**
     * @brief 获取物流
     * @return array
     */
    public static function getExpress()
    {

        $sql = 'select expressName,`days` from urTools.express_deadline';
        $db = static::getDb('db');
        $ret = $db->createCommand($sql)->queryAll();
        $express = [];
        foreach ($ret as $row) {
            $express[$row['expressName']] = $row['days'];
        }
        return $express;
    }

    /**
     * @brief 解析日期
     * @return array
     */
    public static function parse()
    {
        $trades = static::getTrades();
        $out = [];
        foreach ($trades as $row) {
            preg_match('/\d{4}-\d{2}-\d{2}/',$row['logs'],$ret);
            $date = $ret[0];
            if(!in_array($row['nid'],$out,true)) {
                $out[$row['nid']] = ['express' => $row['name'], 'date' => $date];
            }
            else {
                if ($out[$row['nid']]['date'] < $date) {
                    $out[$row['nid']] = ['express' => $row['name'], 'date' => $date];
                }
                }
        }
        return $out;

    }

    /**
     * @brief 标记订单
     * @param $nid
     * @throws \Exception
     * @return mixed;
     */
    public static function mark($nid)
    {
        try {
            $sql = 'SELECT count(*) AS ret FROM CG_OutofStock_Total WHERE tradeNid=:nid';
            $db = static::getDb('py_db');
            $ret = $db->createCommand($sql)->bindValues([':nid' => $nid])->queryOne();
            $isExisted = (int)$ret['ret'];
            if ($isExisted === 0) {
                $sql = 'INSERT INTO CG_OutofStock_Total(TradeNid, PrintMemoTotal) VALUES (:nid, :memo)';
                $db = static::getDb('py_db');
                $db->creatCommand($sql)->bindValues([':nid' => $nid, ':memo' => '跟踪号超时'])->execute();
                return $nid;
            }
        } catch (\Exception $why) {
            throw new \Exception($why->getMessage());
        }
        return false;
    }

    /**
     * @brief 标记订单
     * @param $nid
     * @throws \Exception
     */
    public static function unMark($nid)
    {
        try {
            $sql = 'delete from CG_OutofStock_Total where tradeNid=:nid and PrintMemoTotal=:memo';
            $db = static::getDb('py_db');
            $db->createCommand($sql)->bindValues([':nid' => $nid, ':memo' => '跟踪号超时'])->execute();
        } catch (\Exception $why) {
            throw new \Exception($why->getMessage());
        }
    }

    /**
     * @brief 检查订单物流是否超时
     * @param $expressInfo
     * @throws \Exception
     * @return mixed
     */
    public static function check($expressInfo)
    {
        $out = [];
        $trades = static::parse();
        $today = date('Y-m-d');
        $expressSet = array_keys($expressInfo);
        foreach ($trades as $nid => $row) {
            $date = $row['date'];
            $express = $row['express'];
            if (in_array($express, $expressSet,true)) {
                if(static::diffBetweenTwoDays($today, $date) >= $expressInfo[$express]) {
                    $ret = static::mark($nid);
                    if($ret) {
                        $out[] = ['tradeNid' => $ret];
                    }
                }
                else {
                    static::unMark($nid);
                }
            }
        }
        return $out;

    }

    /**
     * @brief 运行入口
     * @throws \Exception
     */
    public static function run()
    {
        $expressInfo = static::getExpress();
        try {
                return static::check($expressInfo);
            }
        catch (\Exception $why) {
            throw new \Exception($why->getMessage());
        }
    }

    /**
     * @brief 计算天数差
     * @param $day1
     * @param $day2
     * @param $differenceFormat
     * @return float|int
     */
    private static function diffBetweenTwoDays ($day1, $day2, $differenceFormat='%a')
    {
        $date1 = date_create($day1);
        $date2 = date_create($day2);
        $interval = date_diff($date1, $date2);
        return $interval->format($differenceFormat);
    }

}