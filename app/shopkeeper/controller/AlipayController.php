<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/2/1 0001
 * Time: 13:35
 */

namespace app\shopkeeper\controller;


use cmf\controller\BaseController;
use think\Db;

class AlipayController extends BaseController
{
    //支付宝支付
    public function recharge($order_number,$money,$title='充值',$description=''){
        //需要引入这三个文件
        require_once EXTEND_PATH.'alipay/config.php';
        require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';
        require_once EXTEND_PATH.'alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($order_number);
        //订单名称，必填
        $subject = trim($title);

        //付款金额，必填
        $total_amount = trim($money);

        //商品描述，可空
        $body = trim($description);

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new \AlipayTradeService($config);

        /**
         * pagePay 电脑网站支付请求
         * @param $builder //业务参数，使用buildmodel中的对象生成。
         * @param $return_url //同步跳转地址，公网可以访问
         * @param $notify_url //异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */

        $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);
        return $response;
    }

    //请求支付宝支付二维码
    public function qr_recharge($order_number,$money,$title='充值',$description=''){
        //需要引入这三个文件
        require_once EXTEND_PATH.'alipay/config.php';
        require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';
        require_once EXTEND_PATH.'alipay/aop/request/AlipayTradePrecreateRequest.php';

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($order_number);
        //订单名称，必填
        $subject = trim($title);

        //付款金额，必填
        $total_amount = trim($money);

        //商品描述，可空
        $body = trim($description);

        $aop = new \AlipayTradeService($config);
        //构造参数
        $request = new \AlipayTradePrecreateRequest ();
        $request->setBizContent("{" .
            "'out_trade_no':'".$out_trade_no."'," .
            "'total_amount':".$total_amount."," .
            "'discountable_amount':8.88," .
            "'subject':'".$subject."'," .
            "'body':'".$body."'," .
            "'qr_code_timeout_express':'90m'" .
            "  }");
        $response = $aop->aopclientRequestExecute($request);
        if ($response->alipay_trade_precreate_response->code == '10000') {
            return $response->alipay_trade_precreate_response->qr_code;
        } else {
            return '';
        }
    }

    //查询订单支付状态 trade_no,out_trade_no如果同时存在优先取trade_no
    public function get_status($out_trade_no='',$trade_no='') {
    //需要引入这三个文件
        require_once EXTEND_PATH.'alipay/config.php';
        require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';
        require_once EXTEND_PATH.'alipay/aop/request/AlipayTradeQueryRequest.php';

        //订单支付时传入的商户订单号,和支付宝交易号不能同时为空。
        $out_trade_no = trim($out_trade_no);

        //支付宝交易号，和商户订单号不能同时为空
        $trade_no = trim($trade_no);

        $aop = new \AlipayTradeService($config);
        //构造参数
        $request = new \AlipayTradeQueryRequest();
        $request->setBizContent("{" .
            "'out_trade_no':'".$out_trade_no."'," .
            "'trade_no':'".$trade_no."'" .
            "  }");
        $response = $aop->aopclientRequestExecute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $response->$responseNode->code;
        if ($resultCode == 10000) {
            $trade_status = $response->$responseNode->trade_status;
            if ($trade_status == 'WAIT_BUYER_PAY') {
                //等待付款
            } elseif ($trade_status == 'TRADE_CLOSED') {
                //未付款交易超时关闭，或支付完成后全额退款
            } elseif ($trade_status == 'TRADE_SUCCESS' || $trade_status == 'TRADE_FINISHED') {
                //交易支付成功|交易结束，不可退款
                $record = Db::name('account_statement')->where(['order_no'=>$out_trade_no])->find();
                if ($record) {
                    Db::startTrans();
                    try {
                        $info = Db::name('user a')
                            ->join('shopkeeper b', 'a.id=b.user_id')
                            ->field('b.money, a.id as user_id')
                            ->where(['a.id'=>$record['user_id']])
                            ->find();
                        $data = array('balance'=>$info['money']+$record['money'], 'status'=>1, 'transferred_time'=>time(), 'create_time'=>time());
                        Db::name('account_statement')->where(['order_no'=>$out_trade_no])->update($data);
                        Db::name('shopkeeper')->where(['user_id'=>$record['user_id']])->setInc('money',$record['money']);
                        Db::name('shopkeeper')->where(['user_id'=>$record['user_id']])->setInc('total_money',$record['money']);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }
            }
        } else {
            return '';
        }
        return Db::name('account_statement')->where(['order_no'=>$out_trade_no])->value('status');
    }

    //config.php里配置的异步通知地址
    function notifyurl(){
        //引入这两个文件
        require_once EXTEND_PATH.'alipay/config.php';
        require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';

        $arr=$_POST;
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */

        if($result) {//验证成功

            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                $record = Db::name('account_statement')->where(['order_no'=>$out_trade_no])->find();
                if ($record) {
                    Db::startTrans();
                    try {
                        $info = Db::name('user a')
                            ->join('shopkeeper b', 'a.id=b.user_id')
                            ->field('b.money, a.id as user_id')
                            ->where(['a.id'=>$record['user_id']])
                            ->find();
                        $data = array('balance'=>$info['money']+$record['money'], 'status'=>1, 'transferred_time'=>time(), 'create_time'=>time());
                        Db::name('account_statement')->where(['order_no'=>$out_trade_no])->update($data);
                        Db::name('shopkeeper')->where(['user_id'=>$record['user_id']])->setInc('money',$record['money']);
                        Db::name('shopkeeper')->where(['user_id'=>$record['user_id']])->setInc('total_money',$record['money']);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }

            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";	//请不要修改或删除
        }else {
            //验证失败
            echo "fail";
        }

    }

    //页面跳转处理方法
    function returnurl(){
        //引入这两个文件
        require_once EXTEND_PATH.'alipay/config.php';
        require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';

        $arr=$_GET;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */

        if($result) {//验证成功

            //请根据您的业务逻辑来编写程序（已下代码仅为参考）

            //商户订单号
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);

            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);

            echo "<script> window.onload=function(){window.setTimeout('window.close()',3000)}</script>验证成功<br />支付宝交易号：".$trade_no."<br/>3秒后自动关闭...";

        }else {
            //验证失败
            echo "验证失败";
        }
    }

    protected function log_message($msg = '')
    {
        $file = '../data/log/log.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . "\r\n" . var_export($msg, true) . "\r\n\r\n", FILE_APPEND | LOCK_EX);
    }
}