<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/7 7:39
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付仓库具体类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\eloquent;

use app\common\model\Order;
use think\Db;
use think\Exception;
use think\facade\App;
use think\facade\Log;
use think\facade\Request;

class PayRepository extends PayAbstractRepository
{
    /**
     * 支付渠道
     * @var array
     */
    protected $channel = [];

    /**
     * 支付方式对应方法
     * @var array|mixed
     */
    protected $payment = [];

    /**
     * 渠道对应的class
     * @var array|mixed
     */
    protected $channelClass = [];

    public function __construct()
    {
        parent::__construct();
        $this->payment = config('payment_method');
        $this->channelClass = config('channel_class');
    }

    /**
     * 非模型，直接返回false,和数据库无关的操作
     * @return bool
     */
    public function model()
    {
        // TODO: Implement model() method.
        return false;
    }

    /**
     * 支付请求
     * @param $payment
     * @param $options
     * @return mixed
     */
    public function pay($payment, $options)
    {
        if (empty($this->payment[$payment])) {
            return $this->setError(false, '支付方式 ' . $payment . ' 不存在');
        }

        // 查询渠道配置
        $this->channel = 'alipay';
        // 实例化渠道类
        $channelObj = App::invokeClass($this->channelClass[$this->channel]);

        //发起支付请求
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $options['order_no'] = $order_no;
        $options['total_fee'] = rand(11, 99);
        $options['goods'] = '商品测试00' . rand(1111, 9999);
        $channelFun = $this->payment[$payment];
        // 接口请求返回数据
        $result = $channelObj->$channelFun($options);
        Log::error('接口请求返回数据 -- ' . json_encode($result));
        if (!$result) {
            $error = $channelObj->getError();
            return $this->setError(false, $error['msg'], $error['code'], $error['data']);
        }

        // 创建订单
        $insertData = [
          'mch_id' => '2025801203065130',
          'order_no' => $order_no,
          'total_fee' => $options['total_fee'],
          'goods' => $options['goods']
        ];
        $order = Order::create($insertData);
        if ($order) {
            $res = [
              'status' => 1,
              'order_no' => $insertData['order_no'],
            ];
            return $res;
        }
        return $this->setError(false, '平台方错误，订单创建异常！');
    }

    /**
     * 同步回调
     * @return string
     */
    public function returnUrl()
    {
        $getData = Request::param();
        Log::debug(get_current_date() . ' [1] 支付同步结果 ' . json_encode($getData));
        $channelName = 'alipay';
        try {
            if ($getData['trade_no']) {
                $channelName = 'alipay';
            } elseif ($getData['trade_wechat']) {
                $channelName = 'wechat';
            }
        } catch (Exception $e) {
            return $this->setError(false, '接口参数不合法' . $e->getMessage());
        }
        // 3、实例化渠道类，具体是哪一个三方接口返回的异步
        $channelObj = App::invokeClass($this->channelClass[$channelName]);

        // 4、同步返回参数签名验证
        $result = $channelObj->returnUrl($getData);
        if (!$result) {
            $channelError = $channelObj->getReturnMsg();
            return $this->setError(false, $channelError['msg'], $channelError['code']);
        }
        return $result;
    }

    /**
     * 异步回调
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notifyUrl()
    {
        // 1、IP地址白名单验证
        if(!in_array(getenv('HTTP_X_REAL_IP'),config('security.ip.white_list'))){
            return json(['msg'=>'ip is error']);
        }
        // 2、请求方式验证
        if(!Request::isPost()){
            return json(['msg'=>'not is post']);
        }
        // 3、公共的数据处理 这里应该考虑 xml处理的,微信
        $postStr = Request::getInput();
        Log::debug('[异步通知] 请求参数 ' . json_encode($postStr));
        // 2、是那个渠道
        $channel_str = ''; //渠道标识
        $mch_order_no = ''; //渠道订单号
        $mch_id = ''; //商户号

        $channelName = 'alipay';
        try {
            // 3、数据格式转换
            $tmpArr = explode('&', $postStr);
            $postData = [];
            foreach ($tmpArr as $value) {
                $tmp = explode('=', $value);
                $postData[$tmp[0]] = $tmp[1];
            }
            // 2、支付渠道路由，这里判断的时候回跑出异常，
            if ($postData['trade_status']) {
                $channelName = 'alipay';
            } elseif ($postData['trade_wechat']) {
                $channelName = 'wechat';
            }
        } catch (Exception $e) {
            return $this->setError(false, '接口参数不合法' . $e->getMessage());
        }

        Log::debug('[异步通知] 支付渠道 ' . $channelName);
        // 3、实例化渠道类，具体是哪一个三方接口返回的异步
        $channelObj = App::invokeClass($this->channelClass[$channelName]);

        // 4、渠道类通知是否成功，有返回数据，否则返回 false 设置错误
        $result = $channelObj->notifyUrl($postData);
        if (!$result) {
            $channelError = $channelObj->getReturnMsg();
            return $this->setError(false, $channelError['msg'], $channelError['code']);
        }

        // 5、订单处理
        $orderNo = $result['order_no'];
        $handleRes = $this->payNotifyHandle($channelName, $result);
        if (!$handleRes) {
            return $this->setError(false, $handleRes['msg']);
        }
        // 返回对应第三方渠道的内容，如：success
        return $channelObj->notifySuccess();
    }

    /**
     * 支付异步处理
     * @param $channelName
     * @param $result
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payNotifyHandle($channelName, $result)
    {
        // 1、订单验证
        $order_no = $result['order_no'];
        Log::debug('[异步通知] 开始订单处理 ' . $order_no);
        $orderInfo = Db::name('order')->where(['order_no' => $order_no])->lock(true)->find();
        if (empty($orderInfo)) {
            return $this->setError(false, $order_no . '订单未找到');
        }

        // 2、支付金额验证
        if ($orderInfo['total_amount'] != $result['total_amount']) {
            return $this->setError(false, '订单金额与发起支付金额不一致');
        }

        // 3、未支付
        if ($orderInfo['status'] == 0) {
            Log::debug(' 订单未支付 ');
            // 4、根据支付渠道结果更新订单
            $orderUpdate = [];
            if ($result['status'] == 'success') {
                $orderUpdate['status'] = 1;
                $orderUpdate['pay_time'] = time();
            } elseif ($result['status'] == 'fail') {
                $orderUpdate['status'] = -1;
                $orderUpdate['pay_time'] = time();
            } elseif ($result['status'] == 'wait') {
                return $this->setError(false, '等待支付中');
            } else {
                return $this->setError(false, '支付渠道未知状态');
            }
            $orderUpdate['channel'] = $channelName;
            $orderUpdate['channel_order_no'] = $result['channel_order_no'];
            $orderUpdate['channel_return_data'] = json_encode($result);
            // 5、修改用户账户
            try {
                Db::startTrans();
                // 6、更新订单状态
                Db::name('order')->where(['id' => $orderInfo['id']])->update($orderUpdate);
                //写入余额记录日志
                Db::name('merchant_balance_record')->insert([
                    'mch_id' => $orderInfo['mch_id'],
                    'channel' => $channelName,
                    'record_type' => 2,
                    'type' => 2,
                    'money' => $orderInfo['total_amount'],
                    'after_money' => $orderInfo['total_amount'],
                    'before_money' => $orderInfo['total_amount'],
                    'remark' => '订单['.$orderInfo['order_no'].']支付'.$orderInfo['total_amount'].'元',
                    'created_at' => time()
                ]);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                Log::error('系统异常=》' . $e->getMessage() . '|' . $e->getTraceAsString());
                return $this->setError(false, '数据库事务系统异常');
            }
            return $this->setError(true, '处理成功');
        }elseif ($orderInfo['status'] == 1){
            return $this->setError(true, '处理成功');
        }
        return $this->setError(false, '未知的订单状态');
    }
}