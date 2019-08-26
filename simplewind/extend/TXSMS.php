<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/19
 * Time: 9:56
 */
require_once "qcloudsms/src/index.php";
use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;

class TXSMS
{
    private $app_id = 1400193175;
    private $app_key ='0edec72500e8568a4c020b50a481b543';

    /**
     * 发送验证码
     * @param $mobile 不带国家码的手机号
     * @param $params 模板参数列表
     */
    public function sendCode($mobile, $params) {
        // 短信模板ID，需要在短信应用中申请
        $templId = 296119;
        // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
        //$smsSign = "趣抓抓娃娃机";
        $this->send_single(86, $mobile, $templId , $params, "", "", "");
    }

    /**
     * 发送短信
     * @param $mobile 不带国家码的手机号
     * @param $params 模版参数列表
     * @param int $templId 模版id
     */
    public function sendMsg($mobile, $params,$templId=0) {
        // 短信模板ID，需要在短信应用中申请
//        $templId = 250062;
        // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
        //$smsSign = "趣抓抓娃娃机";
        if ($templId) {
            $this->send_single(86, $mobile, $templId , $params, "", "", "");
        }
    }

    /**
     * 指定模板单发
     *
     * @param string $nationCode  国家码，如 86 为中国
     * @param string $mobile 不带国家码的手机号
     * @param int    $templId     模板 id
     * @param array  $params      模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign        签名，如果填空串，系统会使用默认签名
     * @param string $extend      扩展码，可填空串
     * @param string $ext         服务端原样返回的参数，可填空串
     * @return string 应答json字符串，详细内容参见腾讯云协议文档
     */
    public function send_single($nationCode="86", $mobile, $templId = 0, $params, $sign = "", $extend = "", $ext = "") {
        try {
            $ssender = new SmsSingleSender($this->app_id, $this->app_key);
            $result = $ssender->sendWithParam(86, $mobile, $templId, $params, $sign, $extend, $ext);
            //$rsp = json_decode($result);
            //echo $result;
        } catch(\Exception $e) {
            echo var_dump($e);
        }
    }

    /**
     * 指定模板群发
     *
     *
     * @param  string $nationCode   国家码，如 86 为中国
     * @param  array  $mobile 不带国家码的手机号列表
     * @param  int    $templId      模板id
     * @param  array  $params       模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param  string $sign         签名，如果填空串，系统会使用默认签名
     * @param  string $extend       扩展码，可填空串
     * @param  string $ext          服务端原样返回的参数，可填空串
     */
    public function send_multi($nationCode="86", $mobile, $templId = 0, $params, $sign = "", $extend = "", $ext = "") {
        try {
            $msender = new SmsMultiSender($this->app_id, $this->app_key);
            $result = $msender->sendWithParam(86, $mobile, $templId, $params, $sign, $extend, $ext);
            //$rsp = json_decode($result);
            //echo $result;
        } catch(\Exception $e) {
            //echo var_dump($e);
        }
    }

}