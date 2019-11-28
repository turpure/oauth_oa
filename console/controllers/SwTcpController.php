<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-11-27
 * Time: 16:18
 * Author: henry
 */
/**
 * @name SwTcpController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-11-27 16:18
 */


namespace console\controllers;


class SwTcpController
{
    // sw tcp 服务
    private $_tcp;
    // 控制台应用方法
    public function actionRun()
    {
        $this->_tcp = new \swoole_server('0.0.0.0', 9503);
        $this->_tcp->on('connect', [$this, 'onConnect']);
        $this->_tcp->on('receive', [$this, 'onReceive']);
        $this->_tcp->on('close', [$this, 'onClose']);
        $this->_tcp->start();
    }
    // sw connect 回调函数
    public function onConnect($server, $fd)
    {
        echo "connection open: {$fd}\n";
    }
    // sw receive 回调函数
    public function onReceive($server, $fd, $reactor_id, $data)
    {
        // 向客户端发送数据
        $server->send($fd, "Swoole: {$data}");
        // 关闭客户端
        $server->close($fd);
    }
    // sw close 回调函数
    public function onClose($server, $fd)
    {
        echo "connection close: {$fd}\n";
    }

}