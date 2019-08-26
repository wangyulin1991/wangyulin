<?php

/* *
 * 配置文件
 * 版本：1.0
 * 日期：2016-11-28
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201306081000001016
$llpay_config['oid_partner'] = '201904170002473012';

//秘钥格式注意不能修改（左对齐，右边有回车符）  商户私钥，通过openssl工具生成,私钥需要商户自己生成替换，对应的公钥通过商户站上传
$llpay_config['RSA_PRIVATE_KEY'] ='-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA1fPIcnP1H2NNfAHsqPinG0FcxG9FqwNMyH2ukLS23/dtwmBo
EZ83aHyiIGmgQQEZ4oJ5M2E9/AVkRvK0mtVY9M5aAnZx1Gll7uRyfroD1dLAl1O3
Kv+ruUEmlSmoGnj44rREbep4tunN/dLbW3Hh8np0XQm5IyuScK7qX1OEgk3OGPNg
DgQab9oETCSYph3x/ZFl3obTxQXoguTvVTNZvIQxpcU9VHbi+5Ayj/Wxgx8FwLIE
9dyCxdPZxuwH93GO062cTS43lMQSB025MDjTSj/QKwptcy5KhdEwSuDnCcmpSnKg
9YJHE8caxI0Ja+PFdxmSfmGyQZvjpD3x65dtiQIDAQABAoIBAQCbpyr6UXBQsI8L
m97QI253frr90jIuM01mQ0F/12mAUWNR2Y982oeWBa5xxEapZCKvztpcTe+pbUbB
8wr/5h08pO+JASDZNwDIpvzBQ5VMt3IT5fzJVI5bTZHDTTYWZFI1pI5wJPhDop+R
fRjHU3fdT8dedJdzhdyZDDhIt9scbCdu94OF20y/dR6QI4YSmOukHHz/Z+m4heGE
tcfbKye0NTbv6jeWRabv+gi6a4bg52RV44s6uSxwLmRUL1GVAvO1GydhsNAoQPFO
/WZfKx0tkM8t6QecjLjUqyTvQeHIaw1uFImLVRK/xbofCvD4JfEutGoa76sd0SQ3
lmHOpAaBAoGBAPcdwYn/Ppg+ev1WTL3yZSgVYcd6ar6sRczhMdVlbFfSwTWutGw4
1yO7XKnT+iRaNbubE0m42mah0bVPnqOZUmRxhlJpaVgGI4vJZ7cLYq7XePCCPSaa
Jjv+Pm6JBevCAJcbUvNyVS31aipMViPoykXEJ5TLicE/XsUbm4bE7vW5AoGBAN2k
0Ir7pbFMarHGpEgYIyxtT2zuIGHq3JYO0SvgIv/VUkcZ/0R4AYDyt8vTbcDn/ueo
UDjDrAeGt6b5GafCtiqMrxh3ec0mqXNOCjuR+tQa0Wo0EW5bXjREk3Zdl1hT5joQ
6qR3R6zoY44H+xrHpNAOlDJf1LbYti9JhswMsB5RAoGBAM99mKg6PWCv9a0J7V4A
TZeefH919nMrS2CAJcu8YYBMYhalHrFP+LTz+RZP+tTTOhLQXx2jrR5H+UF4HQfZ
ESlteQ9xmxZh8S7Kad90G+Qa4F1xvc7P2BK/o8REIUiXmM9IDhqDgaoZSo9BkYKN
UGnMDkANtxkgEH8Ic0pxyQNhAoGBAL3VQrQ28mQXYi6fRudknQOZH/TZQyz6XjG7
wRWGJBPgXlb9gi/fZNJPkHHnxVH6oc1B0Gu906KilLtTENEpqKJq2jDna4/1NB7I
0WTSE/YEfiYrMF7HrLixn2c6o7yIJ2DGSmHQs7f5VgM4/K2kHpoAUpeOkn6EwA1i
OSydFV7xAoGAFjXKpWmJ91NuDFvu0pGoRfX79RCk1Ss+2AvJq7DKQI/+URMZfG80
uGR2m9Gx9URPRLl9YwDi5pKB11XgPXnowawnmJlNqzW/d4e283ztyu0KLbZVkMZg
heNdspMtZYtmbra40/LQnQROAtk1kFRjAjFeRYLpTPwe0z5flM4f6F4=
-----END RSA PRIVATE KEY-----';

//连连银通公钥
$llpay_config['LIANLIAN_PUBLICK_KEY'] ='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCSS/DiwdCf/aZsxxcacDnooGph3d2JOj5GXWi+
q3gznZauZjkNP8SKl3J2liP0O6rU/Y/29+IUe+GTMhMOFJuZm1htAtKiu5ekW0GlBMWxf4FPkYlQ
kPE0FtaoMP3gYfh+OwI+fIRrpW3ySn3mScnc6Z700nU/VYrRkfcSCbSnRwIDAQAB
-----END PUBLIC KEY-----';

//安全检验码，以数字和字母组成的字符
$llpay_config['key'] = '201408071000001539_sahdisa_20141205';

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('RSA');

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');
?>
