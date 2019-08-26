CREATE TABLE `miss_shopkeeper` (
`id` int(10) unsigned not null auto_increment comment '编号',
`user_id` int(10) unsigned not null default 0 comment '用户id',
`section_id` int(10) unsigned not null default 0 comment '分站id',
`mobile` varchar(20) not null default '' comment '手机号',
`qq` varchar(20) not null default '' comment 'qq号',
`money` decimal(11,2) not null default 0 comment '余额',
`total_money` decimal(11,2) not null default 0 comment '支出总额',
`alipay_no` varchar(50) not null default 0 comment '支付宝账号',
`alipay_ower` varchar(100) not null default 0 comment '支付宝所有者',
`bank_no` varchar(50) not null default '' comment '银行账号',
`bank_addr` varchar(100) not null default '' comment '银行开户地',
`bank_ower` varchar(100) not null default '' comment '持卡人',
`open_account_money` decimal(11,2) not null default 0 comment '开户费',
`act_num` int(6) unsigned not null default 0 comment '任务数量',
`unusual_order_num` int(6) unsigned not null default 0 comment '异常任务数量',
`create_time` int(11) not null default 0 comment '创建时间',

PRIMARY KEY (`id`) 

)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商户信息';

CREATE TABLE `miss_shop` (
`id` int(10) unsigned not null auto_increment comment '编号',
`shopkeeper_id` int(10) unsigned not null default 0 comment '商户id',
`type` tinyint(2) unsigned not null default 0 comment '店铺类型 0:京东 1:淘宝',
`link` varchar(100) not null default '' comment '店铺链接',
`status` tinyint(1) unsigned not null default 0 comment '状态 0冻结 1正常',
`trademanager` varchar(50) not null default '' comment '旺旺账号',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`) 

)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='店铺信息';

CREATE TABLE `miss_task` (
`id` int(11) not null auto_increment comment '任务标识',
`shop_id` int(10) not null default 0 comment '店铺id',
`platform_id` int(11) not null default 0 comment '任务平台',
`shopkeeper_id` int(10) unsigned not null default 0 comment '商户id',
`task_no` varchar(20) not null default '' comment '任务编号',
`task_name` varchar(20) not null default '' comment '任务名称',
`keyword` varchar(255) not null default '' comment '关键词',
`product_img` varchar(255) not null default '' comment '产品首图',
`product_link` varchar(200) not null default '' comment '宝贝链接',
`product_price` decimal(10,2) not null default 0 comment '产品单价',
`commission` decimal(10,2) not null default 0 comment '佣金',
`total_money` decimal(10,2) not null default 0 comment '总金额',
`deal_num` int(10) not null default 0 comment '要拍数量',
`need_chat` tinyint(1) unsigned not null default 0 comment '是否聊天',
`special_desc` varchar(255) not null default '' comment '特殊说明',
`start_time` int(11) not null default 0 comment '做单开始时间',
`sub_task_num` int(6) not null default 0 comment '数量',
`task_num` int(6) not null default 0 comment '任务数',
`remain_task_num` int(6) not null default 0 comment '剩余任务数',
`process_id` int(10) not null comment '流程ID',
`status` tinyint(3) unsigned not null default '0' comment '状态0:审核中,1:进行中，2:拒绝,3:结束',
`reject_reason` varchar(255) not null default '' comment '拒绝原因',
`auditer` int(11) not null default 0  comment '审核者',
`audit_time` int(10) not null default 0  comment '审核时间',
`create_time` int(11) not null default 0 comment '创建时间',
`expire_time` int(11) not null default 0 comment '结束时间',
`fee_step` varchar(255) not null default '' comment '收费步骤',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='任务';

CREATE TABLE `miss_sub_task` (
`id` int(11) not null auto_increment comment '编号',
`task_id` int(10) not null default 0 comment '任务id',
`start_time` int(11) not null default 0 comment '开始时间',
`sub_task_num` int(6) not null default 0 comment '数量',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='任务分批发放';

CREATE TABLE `miss_task_comment` (
`id` int(11) not null auto_increment comment '编号',
`task_no` varchar(20) not null default '' comment '任务编号',
`content` varchar(200) not null default '' comment '评价内容',
`img` varchar(200) not null default 0 comment '评价图片',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='拍单商品评价';

CREATE TABLE `miss_account_statement` (
`id` int(11) not null auto_increment comment '编号',
`order_no` varchar(50) not null default '' comment '流水号',
`pay_way` tinyint(1) unsigned not null default 0 comment '支付方式 0:支付宝',
`type` tinyint(1) unsigned not null default 0 comment '类型: 1收入 2支出',
`purpose` tinyint(1) unsigned not null default 0 comment '目的: 1充值 2任务支出 3退款',
`money` decimal(11,2) not null default 0 comment '变动资金',
`balance` decimal(11,2) not null default 0 comment '余额',
`shop_id` int(11) not null default 0 comment '店铺id',
`user_id` int(11) not null comment '用户id',
`status` tinyint(1) not null default 0 comment '到账情况 0未到账 1到账',
`transferred_time` int(11) not null default 0 comment '到账时间',
`vild_code` int(11) not null default 0 comment '校验值',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='流水';


CREATE TABLE `miss_sms_record` (
`id` int(11) not null auto_increment comment '编号',
`type` tinyint(1) unsigned not null default 0 comment '短信类型 0:普通验证码 1:修改密码';
`mobile` varchar(20) not null default '' comment '手机号',
`code` varchar(20) not null default '' comment '验证码',
`expire_time` int(11) not null default 0 comment '逾期时间',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短信验证码发送记录';


CREATE TABLE `miss_section` (
`id` int(11) not null auto_increment comment '编号',
`user_id` int(11) not null default 0 comment '分站管理员id',
`name` varchar(100) not null default '' comment '分站名称',
`bank_no` varchar(50) not null default '' comment '银行卡',
`invite_count` int(10) not null default 0 comment '邀请人数',
`recharge_money` decimal(11,2) not null default 0 comment '充值金额',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='分站信息';

CREATE TABLE `miss_task_comment` (
`id` int(11) not null auto_increment comment '编号',
`task_id` int(11) not null default 0 comment '任务id',
`content` varchar(255) not null default '' comment '评论内容',
`img` varchar(255) not null default '' comment '评论图片',
`status` tinyint(1) unsigned not null default 0 comment '0未使用 1已使用',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='评论';

CREATE TABLE `miss_range_config` (
`id` int(11) not null auto_increment comment '编号',
`process_config_id` int(11) not null default 0 comment '任务类型配置id',
`commission` decimal(10,2) not null default 0 comment '总佣金',
`start_price` decimal(10,2) not null default 0 comment '前值',
`end_price` decimal(10,2) not null default 0 comment '后值',
`range_type` tinyint(1) unsigned not null default 0 comment '0固定金额 1 百分比',
`range_num` decimal(10,2) not null default 0 comment '数值',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='区间配置';

CREATE TABLE `miss_process_config` (
`id` int(11) not null auto_increment comment '编号',
`process_id` int(11) not null default 0 comment '任务类型配置id',
`type` tinyint(1) unsigned not null default 0 comment '0固定值 1区间',
`commission` decimal(10,2) not null default 0 comment '刷客佣金',
`parent_commission` decimal(10,2) not null default 0 comment '父辈佣金',
`ancestry_commission` decimal(10,2) not null default 0 comment '祖辈佣金',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='任务佣金配置';

CREATE TABLE `miss_config` (
`id` int(11) not null auto_increment comment '编号',
`max_num_month` int(11) not null default 0 comment '刷客每月最大接单数',
`max_num_user_month` int(11) not null default 0 comment '同一商户每月最大接单数',
`reg_day` int(11) not null default 0 comment '新刷客时间',
`limit_money` decimal(10,2) not null default 0 comment '接单限制金额',
`max_task_count_new` int(11) not null default 0 comment '新刷客每月最大接单数',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='配置';

CREATE TABLE `miss_appointment` (
`id` int(11) not null auto_increment comment '编号',
`task_id` int(11) not null default 0 comment '任务id',
`brush_guest_id` int(11) not null default 0 comment '刷客id',
`order_id` int(11) not null default 0 comment '订单id',
`status` tinyint(3) unsigned not null default 0 comment '0待审核 1已通过 2未通过',
`create_time` int(11) not null default 0 comment '创建时间',
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='预约表';



insert into miss_config (max_num_month,max_num_user_month,reg_day,limit_money,max_task_count_new,create_time) values(30,1,30,100,15,unix_timestamp());
insert into miss_process_config (process_id,commission,parent_commission,ancestry_commission,create_time) values(2,3,0.8,0.8,unix_timestamp());
insert into miss_process_config (process_id,commission,parent_commission,ancestry_commission,create_time) values(3,4,1,1,unix_timestamp());