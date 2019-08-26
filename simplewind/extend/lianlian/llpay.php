<?php
namespace lianlian;

class llpay
{
    //连连支付  用户申请货款
    static public function applypay($no_order,$money_order,$dt_order,$real_name,$bank_number,$info_order='提现或用户申请货款'){
        //需要引入这三个文件
        require_once ("llpay.config.php");
        require_once ("lib/llpay_apipost_submit.class.php");
        require_once ("lib/llpay_security.function.php");

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $no_order = trim($no_order);
        //付款金额，必填
        $money_order = trim($money_order);
        //商户订单时间，必填
        $dt_order = trim($dt_order);
        //订单描述。说明付款用途
        $info_order = trim($info_order);
        //收款人姓名
        $acct_name = trim($real_name);
        //银行账号
        $card_no = trim($bank_number);
        //对私标记
        $flag_card = '0';
        //服务器异步通知地址
        $notify_url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/home/Llpay/notifyurl';
        //平台来源
        $platform = 'test.com';
        //版本号
        $api_version = '1.0';
        //实时付款交易接口地址
        $llpay_payment_url = 'https://instantpay.lianlianpay.com/paymentapi/payment.htm';
        //需http://格式的完整路径，不能加?id=123这类自定义参数
        //构造要请求的参数数组，无需改动
        $parameter = array (
            "oid_partner" => trim($llpay_config['oid_partner']),
            "sign_type" => trim($llpay_config['sign_type']),
            "no_order" => $no_order,
            "dt_order" => $dt_order,
            "money_order" => $money_order,
            "acct_name" => $acct_name,
            "card_no" => $card_no,
            "info_order" => $info_order,
            "flag_card" => $flag_card,
            "notify_url" => $notify_url,
            "platform" => $platform,
            "api_version" => $api_version
        );
        //建立请求
        $llpaySubmit = new \LLpaySubmit($llpay_config);
        //对参数排序加签名
        $sortPara = $llpaySubmit->buildRequestPara($parameter);
        //传json字符串
        $json = json_encode($sortPara);
        //print_r($json);die;
        $parameterRequest = array (
            "oid_partner" => trim($llpay_config['oid_partner']),
            "pay_load" => ll_encrypt($json,$llpay_config['LIANLIAN_PUBLICK_KEY']) //请求参数加密
        );
        $html_text = $llpaySubmit->buildRequestJSON($parameterRequest,$llpay_payment_url);
        //调用付款申请接口，同步返回0000，是指创建连连支付单成功，订单处于付款处理中状态，最终的付款状态由异步通知告知
        //出现1002，2005，4006，4007，4009，9999这6个返回码时或者没返回码，抛exception（或者对除了0000之后的code都查询一遍查询接口）调用付款结果查询接口，明确订单状态，不能私自设置订单为失败状态，以免造成这笔订单在连连付款成功了，
        //而商户设置为失败,用户重新发起付款请求,造成重复付款，商户资金损失
        $back_params = json_decode($html_text,true);
        if ($back_params['ret_code']=='0000') {
            return ['status'=>'ok','msg'=>$back_params['ret_msg']];
        } else {
            return ['status'=>'error','msg'=>$back_params['ret_msg']];
        }
    }

    static public function findResult($no_order)
    {
        //需要引入这三个文件
        require_once ("llpay.config.php");
        require_once ("lib/llpay_apipost_submit.class.php");
        require_once ("lib/llpay_security.function.php");
        //平台来源
        $platform = 'test.com';
        //版本号
        $api_version = '1.0';
        $parameter = array (
            "oid_partner" => trim($llpay_config['oid_partner']),
            "sign_type" => trim($llpay_config['sign_type']),
            "no_order" => $no_order,
            "api_version" => $api_version
        );
        //建立请求
        $llpaySubmit = new \LLpaySubmit($llpay_config);
        //对参数排序加签名
        $sortPara = $llpaySubmit->buildRequestPara($parameter);
        //实时付款交易查询接口地址
        $llpay_payment_url = 'https://instantpay.lianlianpay.com/paymentapi/queryPayment.htm';
        $html_text = $llpaySubmit->buildRequestJSON($sortPara,$llpay_payment_url);
        //echo $html_text;
        $back_params = json_decode($html_text,true);
        if ($back_params['ret_code']=='0000' && $back_params['result_pay'] != '' ) {
            $data = array(
                'ret_code' =>$back_params['ret_code'],
                'ret_msg' =>$back_params['ret_msg'],
                'result_pay'=>$back_params['result_pay'],
                'settle_date'=>isset($back_params['settle_date'])?$back_params['settle_date']:'-',
                'memo'=>isset($back_params['memo'])?$back_params['memo']:'无异常',
            );
            //var_dump($data);die;
            return ['status'=>'ok','msg'=>$data];
        } else {
            $data = array(
                'ret_code' =>$back_params['ret_code'],
                'ret_msg' =>$back_params['ret_msg'],
                'memo'=>isset($back_params['memo'])?$back_params['memo']:$back_params['ret_msg'],
                'settle_date'=>isset($back_params['settle_date'])?$back_params['settle_date']:'-'
            );
            return ['status'=>'error','msg'=>$data];
        }
    }
}