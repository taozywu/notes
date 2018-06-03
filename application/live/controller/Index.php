<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\live\controller;

use app\common\queue\MultiTask;
use app\common\queue\Worker;
use think\facade\Config;
use think\facade\Log;
use think\Queue;

class Index
{
    public function index()
    {
        Log::error("1111111111111111111111111");
        var_dump(Config::get('email.qq'));
        return "Hi";
    }

    public function redis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        var_dump($redis->keys('*'));
    }

    public function sendEmail()
    {
        $res = send_email_qq('756684177@qq.com', 'test', 'content');
        var_dump($res);
    }

    // 一个使用了队列的 action
    public function queue()
    {
        //当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        $data = [
            'email' => '28456049@qq.com',
            'username' => 'Tinywan' . rand(1111, 9999)
        ];

        // 当前任务归属的队列名称，如果为新队列，会自动创建
        $queueName = 'workerQueue';

        // 将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push(Worker::class, $data, $queueName);

        // database 驱动时，返回值为 1|false; redis驱动时，返回值为 随机字符串|false
        if ($isPushed !== false) {
            echo '[' . $queueName . ']' . " Job is Pushed to the MQ Success";
        } else {
            echo 'Pushed to the MQ is Error';
        }
    }

    /**
     * 测试多任务队列
     * @return string
     */
    public function testMultiTaskQueue()
    {
        $taskType = MultiTask::EMAIL;
        $data = [
            'email' => 'tinywan@aliyun.com',
            'title' => "注册邮件",
            'content' => "邮件内容".rand(11111,999999)
        ];
        //$res = send_email_qq($data['email'], $data['title'], $data['content']);
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            return "Job is Pushed to the MQ Success";
        } else {
            return 'Pushed to the MQ is Error';
        }
    }

}