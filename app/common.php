<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/28 0028
 * Time: 14:01
 */


function code($key)
{

    $code_map = array(
        '0'=>'是',
        '1'=>'否',

        'shop_type_jd'=>'京东',
        'shop_type_tb'=>'淘宝',
    );
    $val = '';
    if (array_key_exists($key, $code_map)) {
        $val=$code_map[$key];
    }
    return $val;
}

function task_status($val)
{
    $code_map = array(
        0=>'待审核',
        1=>'进行中',
        2=>'拒绝',
        3=>'结束',
        9=>'草稿'
    );
    return $code_map[$val];
}

function order_status($val, $type=1)
{
    $code_map = ['进行中','待审核','未通过','已打款','已结束','已过期','申诉中','已拒绝'];
    if ($type==1) {
        return $code_map[$val];
    } else {
        return $code_map;
    }

}

function get_code($val, $p1, $p2)
{
    return $val?$p1:$p2;
}

function get_codes($val, $p=array())
{
    if (key_exists($val, $p)) {
        return $p[$val];
    }
    return '';
}

function sendSmsCode($mobile, $params) {
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendCode($mobile, $params);
    $rs['code']=0;
    $rs['msg']='短信验证码发送成功';
    return $rs;
}

function sendActiveMsg($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,296054);
    $rs['code']=0;
    $rs['msg']='激活信息发送成功';
    return $rs;
}

function sendOrderTip($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,296053);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}

function resetPassword($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,315001);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}

function orderDoMark($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,315000);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}

function shopkeeperAdd($mobile, $params){
    require_once EXTEND_PATH."/TXSMS.php";
    $sendAPI = new TXSMS();
    $sendAPI->sendMsg($mobile, $params,315819);
    $rs['code']=0;
    $rs['msg']='提醒信息发送成功';
    return $rs;
}

/**
 *  根据身份证号码获取生日
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 *  @return $birthday
 */
function get_birthday_from_id_card($idcard,$type='') {
    if(empty($idcard)) return null;
    $bir = substr($idcard, 6, 8);
    $year = (int) substr($bir, 0, 4);
    $month = (int) substr($bir, 4, 2);
    $day = (int) substr($bir, 6, 2);
    if ($type=='Y'){
        return $year;
    } else {
        return $year . "-" . $month . "-" . $day;
    }
}

