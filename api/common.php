<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/28 0028
 * Time: 14:01
 */


/**
 * 发送请求
 *
 * @param string $url      请求地址
 * @param array  $dataObj  请求内容
 * @param integer  $type  请求类型
 * @return string 应答json字符串
 */
function sendCurl($url, $type = 0, $dataObj=array())
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if ($type) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));
    }
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    $ret = curl_exec($curl);
    curl_close($curl);

    return $ret;
}

function sendOrderTip($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,296053);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}

function sendOrderTime($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,376007);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}