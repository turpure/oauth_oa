<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-11-29
 * Time: 16:32
 * Author: henry
 */

/**
 * @name WorkermanController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-11-29 16:32
 */


namespace console\controllers;

use Workerman\Worker;
use yii\helpers\Console;
use yii\console\Controller;

class WorkermanController extends Controller
{
    public $send;
    public $daemon;
    public $gracefully;

    // 这里不需要设置，会读取配置文件中的配置
    public $config = [];
    private $ip = '0.0.0.0';
    private $port = '2346';

    public function options($actionID)
    {
        return ['send', 'daemon', 'gracefully'];
    }

    public function optionAliases()
    {
        return [
            's' => 'send',
            'd' => 'daemon',
            'g' => 'gracefully',
        ];
    }

    public function actionIndex()
    {
        $this->worker();

    }


    public function worker()
    {
        if (PHP_OS === 'WINNT') {
          $this->initWorker();
          Worker::runAll();
        }
        else {

            if ('start' == $this->send) {
                try {
                    $this->start($this->daemon);
                } catch (\Exception $e) {
                    $this->stderr($e->getMessage() . "\n", Console::FG_RED);
                }
            } else if ('stop' == $this->send) {
                $this->stop();
            } else if ('restart' == $this->send) {
                $this->restart();
            } else if ('reload' == $this->send) {
                $this->reload();
            } else if ('status' == $this->send) {
                $this->status();
            } else if ('connections' == $this->send) {
                $this->connections();
            }
        }
    }

    public function initWorker()
    {
        //var_dump($this->config['ip']);exit;
        $ip = isset($this->config['ip']) ? $this->config['ip'] : $this->ip;
        $port = isset($this->config['port']) ? $this->config['port'] : $this->port;
        $wsWorker = new Worker("websocket://{$ip}:{$port}");

        // 4 processes
        if(PHP_OS !== 'WINNT') {
            $wsWorker->count = 4;
        }

        // Emitted when new connection come
        $wsWorker->onConnect = function ($connection) {
            echo "New connection\n";
        };

        // Emitted when data received
        $wsWorker->onMessage = function ($connection, $data) {
            // Send hello $data
            $connection->send('dddd hello ' . $data);
        };

        // Emitted when connection closed
        $wsWorker->onClose = function ($connection) {
            echo "Connection closed\n";
        };
    }

    /**
     * workman websocket start
     */
    public function start()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'start';
        if ($this->daemon) {
            $argv[2] = '-d';
        }
        //var_dump($argv);exit;
        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket restart
     */
    public function restart()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'restart';
        if ($this->daemon) {
            $argv[2] = '-d';
        }

        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket stop
     */
    public function stop()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'stop';
        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket reload
     */
    public function reload()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'reload';
        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket status
     */
    public function status()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'status';
        if ($this->daemon) {
            $argv[2] = '-d';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket connections
     */
    public function connections()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'connections';

        // Run worker
        Worker::runAll();
    }
}