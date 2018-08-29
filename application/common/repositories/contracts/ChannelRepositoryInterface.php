<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付渠道接口
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\contracts;


interface ChannelRepositoryInterface
{
//    /**
//     * 设置渠道id
//     * @return mixed
//     */
//    public function setChannelId();
//
//    /**
//     * 银行网银网关
//     * @param $option
//     * @return mixed
//     */
//    public function gateWay($option);
//
//    /**
//     * 银行快捷支付
//     * @param $option
//     * @return mixed
//     */
//    public function unQuickpay($option);
//
    /**
     * 银联wap
     * @param $option
     * @return mixed
     */
    public function unPayWap($option);
//
//    /**
//     * qq扫码
//     * @param $option
//     * @return mixed
//     */
//    public function qqSm($option);
//
//    /**
//     * 异步通知
//     * @param $data
//     * @return mixed
//     */
//    public function notify($data);
//
//    /**
//     * 提现
//     * @param $option
//     * @return mixed
//     */
//    public function cash($option);
//
//    /**
//     * 订单查询
//     * @param $option
//     * @return mixed
//     */
//    public function cashQuery($option);

    /**
     * 设置渠道id
     * @return mixed
     */
    public function setChannelId();

    /**
     * 银行网银网关
     * @param $option
     * @return mixed
     */
    public function gateWay($option);

    /**
     * 银行快捷支付
     * @param $option
     * @return mixed
     */
    public function unQuickpay($option);
}