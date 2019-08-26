<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/1/30
 * Time: 13:54
 */

namespace app\admin\controller;

use app\admin\model\JobsModel;
use cmf\controller\AdminBaseController;
use app\admin\model\UserModel;
use app\admin\model\BrushGuestModel;
use think\Db;

class
BrushGuestController extends AdminBaseController
{
    /**
     * 用户列表
     */
    public function index()
    {
        $param = $this->request->param();
        $where = 'b.active_status = 1 and b.audit_status = 1 and a.user_type = 3';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        $start_work_time = empty($param['start_work_time']) ? 0 : strtotime($param['start_work_time']);
        $end_work_time   = empty($param['end_work_time']) ? 0 : strtotime($param['end_work_time']);
        if (!empty($startTime)) {
            $where .= ' and a.create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and a.create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($start_work_time)) {
            $where .= ' and c.create_time > '.$start_work_time;
            $this->assign('start_work_time', $param['start_work_time']);
        }
        if (!empty($end_work_time)) {
            $where .= ' and c.create_time < '.$end_work_time;
            $this->assign('end_work_time', $param['end_work_time']);
        }
        if (!empty($param['mobile'])) {
            $where .= ' and b.cellphone = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }
        if (!empty($param['f_cellphone'])) {
            $where .= ' and b.f_cellphone = '.$param['f_cellphone'];
            $this->assign('f_cellphone', $param['f_cellphone']);
        }
        if (!empty($param['real_name'])) {
            $where .= ' and b.real_name = \''.$param['real_name'].'\'';
            $this->assign('real_name', $param['real_name']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and b.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['taobao_ww'])) {
            $where .= ' and b.taobao_ww = \''.$param['taobao_ww'].'\'';
            $this->assign('taobao_ww', $param['taobao_ww']);
        }
        if (!empty($param['taobao_age'])) {
            $where .= ' and b.taobao_age = '.$param['taobao_age'];
            $this->assign('taobao_age', $param['taobao_age']);
        }
        if (!empty($param['jd_account'])) {
            $where .= ' and b.jd_account = \''.$param['jd_account'].'\'';
            $this->assign('jd_account', $param['jd_account']);
        }
        if (!empty($param['bank_number'])) {
            $where .= ' and b.bank_number = '.$param['bank_number'];
            $this->assign('bank_number', $param['bank_number']);
        }
        if (!empty($param['user_status'])) {
            if($param['user_status'] == 11){
                $where .= ' and a.user_status = 1';
                $this->assign('user_status', $param['user_status']);
            }
            if($param['user_status'] == 10){
                $where .= ' and a.user_status = 0';
                $this->assign('user_status', $param['user_status']);
            }
        }
        if (!empty($param['is_black'])) {
            $where .= ' and b.is_black = '.$param['is_black'];
            $this->assign('is_black', $param['is_black']);
        }
        $order = 'a.id DESC';
        if (!empty($param['order'])) {
            if($param['order'] == 1){
                $order = 'a.create_time desc';
                $this->assign('order', $param['order']);
            }
            if($param['order'] == 2){
                $order = 'c.id desc';
                $this->assign('order', $param['order']);
            }
            if($param['order'] == 3){
                $order = 'a.last_login_time desc';
                $this->assign('order', $param['order']);
            }
        }

        $startDate = strtotime(date('Y-m-01', strtotime(date("Y-m-d"))));
        $endDate = strtotime("$startDate +1 month -1 day") + 86400;
        //$where .= ' and c.create_time > '.$startDate;
        $userModel = new UserModel();
        $result = $userModel->alias('a')
            ->join('brush_guest b', 'a.id=b.user_id','LEFT')
            ->join('order c', 'c.brush_guest_id=a.id', 'LEFT')
            ->join('user u','b.add_admin = u.id','LEFT')
            ->field('a.id as uid, a.mobile,b.create_time as ctime,a.last_login_time as last_time, a.user_status, b.*, SUM(IF(c.status = 4, 1, 0)) as succ_count,SUM(IF(c.status = 0, 1, 0)) as ongoing_count, SUM(IF(c.status = 5, 1, 0)) as fail_count,u.user_login,min(c.create_time) as min_time')
            ->where($where)
            ->group('a.id')
            ->order($order)
            ->paginate(30);
        $total = $userModel->alias('a')
            ->join('brush_guest b', 'a.id=b.user_id','LEFT')
            ->join('order c', 'c.brush_guest_id=a.id', 'LEFT')
            ->join('user u','b.add_admin = u.id','LEFT')
            ->where($where)
            ->group('a.id')
            ->count();
        $result->appends($param);
        $page = $result->render();
        $nowTotal = $result->count();
        $this->assign('brush_guests',$result);
        $this->assign('total',$total);
        $this->assign('nowTotal',$nowTotal);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 是否黑户列表（查号）
     */
    public function blackList()
    {
        $param = $this->request->param();
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        $start_login_time   = empty($param['start_login_time']) ? 0 : strtotime($param['start_login_time']);
        $end_login_time   = empty($param['end_login_time']) ? 0 : strtotime($param['end_login_time']);

        $where = 'b.active_status = 1 and  b.audit_status = 1 and b.is_black not in(2) and a.user_type = 3 and a.user_status = 1';

        if (!empty($param['is_black'])) {
            $where = 'b.active_status = 1 and  b.audit_status = 1 and a.user_type = 3 and a.user_status = 1';
            $where .= ' and b.is_black = '.$param['is_black'];
            $this->assign('is_black', $param['is_black']);
        }

        if (!empty($startTime)) {
            $where .= ' and a.create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and a.create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($start_login_time)) {
            $where .= ' and a.last_login_time > '.$start_login_time;
            $this->assign('start_login_time', $param['start_login_time']);
        }
        if (!empty($end_login_time)) {
            $where .= ' and a.last_login_time < '.$end_login_time;
            $this->assign('end_login_time', $param['end_login_time']);
        }
        if (!empty($param['mobile'])) {
            $where .= ' and b.cellphone = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and b.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['taobao_ww'])) {
            $where .= ' and b.taobao_ww = \''.$param['taobao_ww'].'\'';
            $this->assign('taobao_ww', $param['taobao_ww']);
        }
//        if (!empty($param['is_black'])) {
//            $where .= ' and b.is_black = '.$param['is_black'];
//            $this->assign('is_black', $param['is_black']);
//        }
        //$startDate = strtotime(date('Y-m-01', strtotime(date("Y-m-d"))));
        //$endDate = strtotime("$startDate +1 month -1 day") + 86400;
        $userModel = new UserModel();
        $result = $userModel->alias('a')
            ->join('brush_guest b', 'a.id=b.user_id','LEFT')
            ->join('order c', 'c.brush_guest_id=a.id', 'LEFT')
            ->join('user u','b.add_admin = u.id','LEFT')
            ->field('a.id as uid, a.mobile,b.create_time as ctime,a.last_login_time as last_time, a.user_status, b.*, SUM(IF(c.status = 4, 1, 0)) as succ_count,SUM(IF(c.status = 0, 1, 0)) as ongoing_count, SUM(IF(c.status = 5, 1, 0)) as fail_count,u.user_login,min(c.create_time) as min_time')
            ->where($where)
            ->group('a.id')
            ->order("b.query_time ASC")
            ->paginate(30);
        $total = $userModel->alias('a')
            ->join('brush_guest b', 'a.id=b.user_id','LEFT')
            ->join('order c', 'c.brush_guest_id=a.id', 'LEFT')
            ->join('user u','b.add_admin = u.id','LEFT')
           // ->field('a.id as uid, a.mobile,b.create_time as ctime,a.last_login_time as last_time, a.user_status, b.*, SUM(IF(c.status = 4, 1, 0)) as succ_count,SUM(IF(c.status = 0, 1, 0)) as ongoing_count, SUM(IF(c.status = 5, 1, 0)) as fail_count,u.user_login,min(c.create_time) as min_time')
            ->where($where)
            ->group('a.id')
            ->count();
        $result->appends($param);
        $page = $result->render();
        $nowTotal = $result->count();
        $this->assign('brush_guests',$result);
        $this->assign('total',$total);
        $this->assign('nowTotal',$nowTotal);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 查号结果填写
     */
    public function queryResult()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $refuseId = $data['id'];
            $data['query_time']= time();
            if(empty($data['query_img'])){
                return json(['msg'=>'请上传查号图片','code'=>400]);
            }
            $flag = false;
            if($refuseId){
                Db::startTrans();
                try{
                    $brushGuestModel = new BrushGuestModel;
                    $rs = $brushGuestModel->allowField(true)->save($data, ['id'=>$data['id']]);
                    if ($rs) {
                        $flag = true;
                        Db::commit();
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }
            } else {
                $this->error('操作错误', url('BrushGuest/blackList'));
            }
            if ($flag) {
                return json(['msg'=>'处理成功！','code'=>200]);
            } else {
                Db::rollback();
                return json(['msg'=>'处理失败','code'=>400]);
            }

        }else{
            $id = $this->request->param("id", 0, 'intval');
            $brushGuestModel = new BrushGuestModel;
            $result = $brushGuestModel->find($id)->toArray();
            $this->assign('data',$result);
            return $this->fetch();
        }
    }

    /**    * 批量修改查号结果     */
    public function updateQueryResult()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            unset($data['file']);
            $arr=array();
            foreach ($data as $k=>$v){
                $arr= $v;
            }

            foreach ($arr as $k=>$v){
                //$query_img = Db::name('brush_guest')->where('id',$v['id'])->value('query_img');
                if(!empty($v['query_img'])){
                    $res = Db::name('brush_guest')->where('id',$v['id'])->update([
                        'is_black'=>$v['is_black'],
                        'query_img'=>$v['query_img'],
                        'taobao_age'=>$v['taobao_age'],
                        'query_time'=>time(),
                    ]);
                }
            }
            $this->success('批量更改成功');

        }

    }

    /**
     * 待审核用户列表
     */
    public function auditList()
    {
        $param = $this->request->param();
        $where = 'a.audit_status = 0';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and a.create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and a.create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['cellphone'])) {
            $where .= ' and a.cellphone = '.$param['cellphone'];
            $this->assign('cellphone', $param['cellphone']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and a.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['taobao_ww'])) {
            $where .= ' and a.taobao_ww = \''.$param['taobao_ww'].'\'';
            $this->assign('taobao_ww', $param['taobao_ww']);
        }
        if (!empty($param['jd_account'])) {
            $where .= ' and a.jd_account = '.$param['jd_account'];
            $this->assign('jd_account', $param['jd_account']);
        }
        if (!empty($param['f_cellphone'])) {
            $where .= ' and a.f_cellphone = '.$param['f_cellphone'];
            $this->assign('f_cellphone', $param['f_cellphone']);
        }
        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->alias('a')
            ->join('user b','a.add_admin = b.id','left')
            ->field('a.*,b.user_login')
            ->where($where)
            ->order("create_time desc, id DESC")
            ->paginate(20);
        $result->appends($param);
        $page = $result->render();
        $this->assign('brush_guests',$result);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 待审核用户详细信息
     */
    public function auditDetail()
    {
        $id = $this->request->param("id", 0, 'intval');
        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->find($id);
        if ($result['p_user_id']>0) {
            $fBrush = $brushGuestModel->where(['user_id'=>$result['p_user_id']])->find();
            $result['f_id_card_first'] = $fBrush['id_card_first'];
            $result['f_id_card_second'] = $fBrush['id_card_second'];
        } else {
            $result['f_id_card_first'] = '';
            $result['f_id_card_second'] = '';
        }
        $res = get_tb_credit_level();
        $taobao_credit_level =[];
        foreach ($res as $value){
            $taobao_credit_level[$value['id']]=$value;
        }
        $this->assign('taobao_credit_level',$taobao_credit_level);
        $res = get_jd_credit_level();
        $jd_credit_level =[];
        foreach ($res as $value){
            $jd_credit_level[$value['id']]=$value;
        }
        $this->assign('jd_credit_level',$jd_credit_level);
        $this->assign('data',$result);

        return $this->fetch();
    }

    /**
     * 审核
     */
    public function audit()
    {
        $id = $this->request->param("id", 0, 'intval');
        $param['audit_status'] = $this->request->param("audit_status", 0, 'intval');
        $param['audit_remarks'] = $this->request->param("audit_remarks", '');
        $param['query_img'] = $this->request->param("query_img", '');
        $param['is_black'] = $this->request->param("is_black", '');
        $param['taobao_age'] = $this->request->param("taobao_age", '');
        $param['query_time'] = time();
        $param['auditor'] = cmf_get_current_admin_id();
        if (!$param['auditor']) {
            return json(['msg'=>'请刷新页面重新登陆','code'=>1003]);
            exit;
        }
        if (!in_array($param['audit_status'],[1,2])){
            return json(['msg'=>'审核状态错误','code'=>1001]);
            exit;
        }
        if ($param['audit_status']==2 && !$param['audit_remarks']) {
            return json(['msg'=>'审核备注不能为空','code'=>1002]);
            exit;
        }
        $brushGuestModel = new BrushGuestModel;
        $brushGuestModel->allowField(true)->save($param, ['id'=>$id]);

        return json(['msg'=>'审核通过','code'=>200]);
    }

    /**
     * 平台买手列表
     */
    public function platformBrush()
    {
        $param = $this->request->param();
        $where = 'a.active_status = 1 and a.audit_status = 1 and b.platform_status = 1';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and a.create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and a.create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['cellphone'])) {
            $where .= ' and a.cellphone = '.$param['cellphone'];
            $this->assign('cellphone', $param['cellphone']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and a.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['id_platform'])) {
            $where .= ' and b.id_platform = '.$param['id_platform'];
            $this->assign('id_platform', $param['id_platform']);
        }
        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->alias('a')
            ->join('brush_platform b','a.id = b.id_brush')
            ->join('platform c','b.id_platform = c.id')
            ->field('a.id,a.real_name,a.cellphone,a.qq,a.gender,a.create_time,b.id_platform,c.platform_name')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(20);
        $page = $result->render();
        $nowTotal = $result->count();
        $this->assign('brush_guests',$result);
        $this->assign('nowTotal',$nowTotal);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 买手平台认证列表
     */
    public function platformAuth()
     {
         $param = $this->request->param();
         $where = 'a.active_status = 1 and a.audit_status = 1 and b.platform_status = 0';
         $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
         $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
         if (!empty($startTime)) {
             $where .= ' and a.create_time > '.$startTime;
             $this->assign('start_time', $param['start_time']);
         }
         if (!empty($endTime)) {
             $where .= ' and a.create_time < '.$endTime;
             $this->assign('end_time', $param['end_time']);
         }
         if (!empty($param['cellphone'])) {
             $where .= ' and a.cellphone = '.$param['cellphone'];
             $this->assign('cellphone', $param['cellphone']);
         }
         if (!empty($param['qq'])) {
             $where .= ' and a.qq = '.$param['qq'];
             $this->assign('qq', $param['qq']);
         }
         $brushGuestModel = new BrushGuestModel;
         $result = $brushGuestModel->alias('a')
             ->join('brush_platform b','a.id = b.id_brush')
             ->join('platform c','b.id_platform = c.id')
             ->field('a.id,a.real_name,a.cellphone,a.qq,a.gender,a.create_time,b.id_platform,c.platform_name')
             ->where($where)
             ->order("a.id DESC")
             ->paginate(20);
         $page = $result->render();
         $this->assign('brush_guests',$result);
         $this->assign('page',$page);
         return $this->fetch();
     }

    /**
     * 买手平台认证详细信息
     */
    public function platformauthDetail()
    {
        $id = $this->request->param("id", 0, 'intval');
        $platformId = $this->request->param("pid");
        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->alias('a')
            ->join('brush_platform b','a.id = b.id_brush and b.id_platform ='.$platformId)
            ->field('a.*,b.id as bid,b.id_platform')
            ->where('a.id ='.$id)
            ->find()->toArray();
        if ($result['p_user_id']>0) {
            $fBrush = $brushGuestModel->where(['user_id'=>$result['p_user_id']])->find();
            $result['f_id_card_first'] = $fBrush['id_card_first'];
            $result['f_id_card_second'] = $fBrush['id_card_second'];
        } else {
            $result['f_id_card_first'] = '';
            $result['f_id_card_second'] = '';
        }
        $res = get_tb_credit_level();
        $taobao_credit_level =[];
        foreach ($res as $value){
            $taobao_credit_level[$value['id']]=$value;
        }
        $this->assign('taobao_credit_level',$taobao_credit_level);
        $res = get_jd_credit_level();
        $jd_credit_level =[];
        foreach ($res as $value){
            $jd_credit_level[$value['id']]=$value;
        }
        $this->assign('jd_credit_level',$jd_credit_level);
        $this->assign('data',$result);

        return $this->fetch();
    }

    //平台认证
    public function auth(){
        $param['id'] = $this->request->param("id", 0, 'intval');
        $param['platform_status'] = $this->request->param("platform_status", 0, 'intval');
        $param['platform_remarks'] = $this->request->param("platform_remarks", '');
        $param['authenticator'] = cmf_get_current_admin_id();
        if (!$param['authenticator']) {
            return json(['msg'=>'请刷新页面重新登陆','code'=>1003]);
            exit;
        }
        if (!in_array($param['platform_status'],[1,2])){
            return json(['msg'=>'认证状态错误','code'=>1001]);
            exit;
        }
        if ($param['platform_status']==2 && !$param['platform_remarks']) {
            return json(['msg'=>'认证备注不能为空','code'=>1002]);
            exit;
        }
        $rs = Db::name('brush_platform')->update($param);
        if($rs){
            return json(['msg'=>'认证通过','code'=>200]);
        }

    }
    /**
     * 添加用户
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'BrushGuest');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $userModel = new UserModel();
                $user = $userModel->where(['mobile'=>$data['cellphone']])->find();
                if (isset($user['id'])&&$user['id']>0) {
                    $this->error('手机号码已被使用');
                    exit;
                }
                if ($data['f_cellphone']) {
                    $user = $userModel->where(['mobile'=>$data['f_cellphone'],'user_type'=>3])->find();
                    if (isset($user['id'])&&$user['id']>0) {
                        $data['p_user_id'] = $user['id'];
                    } else {
                        $this->error('未找到推荐人');
                    }
                }
                if (!empty($data['id_card_no'])) {
                    $birth_year = get_birthday_from_id_card($data['id_card_no'],'Y');
                    $data['birth_year'] = $birth_year;
                }
                $data['audit_status'] = 0;
                $data['add_admin'] = cmf_get_current_admin_id();
                $data['create_time'] = time();
                $brushGuestModel = new BrushGuestModel;
                $brushGuestModel->save($data);
                //$bid = $brushGuestModel->insertGetId($data);
                $this->success("添加成功！", url('BrushGuest/activeList'));
            }
        } else {
            $jobsModel = new JobsModel();
            $jobs = $jobsModel->where('status = 1')->select()->toArray();
            $taobao_credit_level = get_tb_credit_level();
            $this->assign('taobao_credit_level',$taobao_credit_level);
            $jd_credit_level = get_jd_credit_level();
            $this->assign('jd_credit_level',$jd_credit_level);
            $this->assign('jobs',$jobs);
            return $this->fetch();
        }
    }

    /**
     * 编辑用户
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'BrushGuest');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                if ($data['f_cellphone']) {
                    $userModel = new UserModel();
                    $master = $userModel->where(['mobile'=>$data['f_cellphone'],'user_type'=>3])->find();
                    if (isset($master['id'])&&$master['id']>0) {
                        $data['p_user_id'] = $master['id'];
                    } else {
                        $this->error('未找到推荐人');
                    }
                }
                if (!empty($data['id_card_no'])) {
                    $birth_year = get_birthday_from_id_card($data['id_card_no'],'Y');
                    $data['birth_year'] = $birth_year;
                }
                $data['query_time'] = time();
                $brushGuestModel = new BrushGuestModel;
                $brushGuestModel->allowField(true)->save($data, ['id'=>$data['id']]);
                $brush = $brushGuestModel->find($data['id']);
                if ($brush['user_id']>0) {
                    $userModel = new UserModel();
                    $userModel->save(['mobile'=>$data['cellphone'],'user_login'=>$data['cellphone']],['id'=>$brush['user_id']]);
                }
                if($brush['jd_account']){
                    $arr= array(
                        'id_brush'=>$data['id'],
                        'id_platform'=>1,//认证京东
                        'platform_status'=>0,
                        'id_user'=>$brush['user_id'],
                    );
                    $rs = Db::name('brush_platform')->where(['id_brush'=>$data['id'],'id_platform'=>1])->find();
                    if(!$rs){
                        Db::name('brush_platform')->insert($arr);
                    }
                }
                if($brush['taobao_ww']){
                    $arr= array(
                        'id_brush'=>$data['id'],
                        'id_platform'=>2,//认证淘宝/天猫
                        'platform_status'=>0,
                        'id_user'=>$brush['user_id'],
                    );
                    $rs = Db::name('brush_platform')->where(['id_brush'=>$data['id'],'id_platform'=>2])->find();
                    if(!$rs){
                        Db::name('brush_platform')->insert($arr);
                    }
                }
                $this->success("编辑成功！", url('BrushGuest/index'));

            }
        }else{
            $id = $this->request->param("id", 0, 'intval');
            $brushGuestModel = new BrushGuestModel;
            $result = $brushGuestModel->find($id)->toArray();
            $this->assign('data',$result);
            $jobsModel = new JobsModel();
            $jobs = $jobsModel->where('status = 1')->select()->toArray();
            $temp = get_tb_credit_level();
            $taobao_credit_level = [];
            foreach ($temp as $value){
                $taobao_credit_level[$value['id']] = $value;
            }
            $this->assign('taobao_credit_level',$taobao_credit_level);
            $jd_credit_level = get_jd_credit_level();
            $this->assign('jd_credit_level',$jd_credit_level);
            $this->assign('jobs',$jobs);
            return $this->fetch();
        }
    }


    /**
     * 封禁/解封用户
     */
    public function delete_view(){
        $id = $this->request->param("id", 0, 'intval');
        $this->assign('uid',$id);
        return $this->fetch();
    }
    public function delete()
    {
        $id = $this->request->param("id", 0, 'intval');
        $audit_remarks = $this->request->param("audit_remarks");
        $userModel = new UserModel();
        $result = $userModel->find($id);
        if (isset($result['id']) && $result['id'] > 0 && in_array($result['user_status'],[0,1])) {
            if ($result['user_status'] == 1) {
                $data = ['user_status'=>0];
                $userModel->save($data,['id'=>$id]);
                Db::name('brush_guest')->where('user_id',$id)->update(['audit_remarks'=>$audit_remarks]);
                return json(['msg'=>'账号已封禁','code'=>200]);
            } else if ($result['user_status'] == 0) {
                $data = ['user_status'=>1];
                $userModel->save($data,['id'=>$id]);
                $this->success('账号已解封');
            }
        }
        $this->error('操作错误');
    }

    /**
     * 待激活列表
     */
    public function activeList()
    {
        $param = $this->request->param();
        $where = 'active_status = 0 and audit_status != 2';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['cellphone'])) {
            $where .= ' and cellphone = '.$param['cellphone'];
            $this->assign('cellphone', $param['cellphone']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['taobao_ww'])) {
            $where .= ' and taobao_ww = \''.$param['taobao_ww'].'\'';
            $this->assign('taobao_ww', $param['taobao_ww']);
        }
        if (!empty($param['f_cellphone'])) {
            $where .= ' and f_cellphone = '.$param['f_cellphone'];
            $this->assign('f_cellphone', $param['f_cellphone']);
        }

        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->where( $where)->order("id DESC")->paginate(20);
        $page = $result->render();
        $this->assign('brush_guests',$result);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 激活用户(同时判断是否进入平台认证)
     */
    public function active(){
        $id = $this->request->param("id", 0, 'intval');
        if ($id > 0) {
            //取出用户信息
            $brushGuestModel = new BrushGuestModel;
            $guest = $brushGuestModel->find($id)->toArray();
            if (isset($guest['user_id'])&&$guest['user_id']<1) {
                //新建用户
                $data = array(
                    'user_type'=>3,
                    'create_time' => time(),
                    'user_login'=>$guest['cellphone'],
                    'user_pass'=>cmf_password('123456'),
                    'user_nickname'=>$guest['cellphone'],
                    'mobile'=>$guest['cellphone'],
                    'user_status'=>1
                );
                $userModel = new UserModel();
                $user = $userModel->where(['mobile'=>$guest['cellphone']])->find();
                if (isset($user['id'])&&$user['id']>0) {
                    $this->error('手机号码已被使用');
                    return json(['code'=>1001,'msg'=>'手机号码已被使用!']);
                    exit;
                }
                $res = $userModel->insertGetId($data);

                if ($res) {
                    if ($guest['p_user_id']>0) {
                        $brushGuestModel->where('user_id = ' . $guest['p_user_id'])->setInc('next_level_users_count');
                    }
                    $data = ['user_id'=>$res, 'active_status'=>1];
                    $brushGuestModel->save($data,['id'=>$id]);
                    if($guest['taobao_ww']){
                        $arr= array(
                            'id_brush'=>$id,
                            'id_platform'=>2,//认证淘宝/天猫
                            'platform_status'=>0,
                            'id_user'=>$res,
                        );
                        $rs = Db::name('brush_platform')->where(['id_brush'=>$id,'id_platform'=>2])->find();
                        if(!$rs){
                            Db::name('brush_platform')->insert($arr);
                        }
                    }
                    if($guest['jd_account']){
                        $arr= array(
                            'id_brush'=>$id,
                            'id_platform'=>1,//认证京东
                            'platform_status'=>0,
                            'id_user'=>$res,
                        );
                        $rs = Db::name('brush_platform')->where(['id_brush'=>$id,'id_platform'=>1])->find();
                        if(!$rs){
                            Db::name('brush_platform')->insert($arr);
                        }
                    }
                    //Db::name('brush_platform')->where('id_brush',$id)->update(['id_user'=>$res]);
                    $rs = sendActiveMsg($guest['cellphone'], array('123456'));
                    return json(['code'=>200,'msg'=>'激活成功!']);
                } else {
                    return json(['code'=>1001,'msg'=>'激活失败!']);
                }

            } else if (isset($guest['user_id'])&&$guest['user_id']>1 && $guest['active_status']==0) {
                $data = ['active_status'=>1];
                $brushGuestModel->save($data,['id'=>$id]);
                return json(['code'=>200,'msg'=>'激活成功!']);
            } else {
                return json(['code'=>1001,'msg'=>'请勿重复激活!']);
            }
        }  else {
            return json(['code'=>1001,'msg'=>'参数错误!']);
        }
    }

    /**
     * 未通过列表
     */
    public function refuseList()
    {
        $param = $this->request->param();
        $where = 'audit_status = 2';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and create_time > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and create_time < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['cellphone'])) {
            $where .= ' and cellphone = '.$param['cellphone'];
            $this->assign('cellphone', $param['cellphone']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        if (!empty($param['taobao_ww'])) {
            $where .= ' and taobao_ww = \''.$param['taobao_ww'].'\'';
            $this->assign('taobao_ww', $param['taobao_ww']);
        }
        if (!empty($param['f_cellphone'])) {
            $where .= ' and f_cellphone = '.$param['f_cellphone'];
            $this->assign('f_cellphone', $param['f_cellphone']);
        }

        $brushGuestModel = new BrushGuestModel;
        $result = $brushGuestModel->where( $where)->order("id DESC")->paginate(20);
        $page = $result->render();
        $temp = get_tb_credit_level();
        $taobao_credit_level = [];
        foreach ($temp as $value){
            $taobao_credit_level[$value['id']] = $value;
        }
        $this->assign('taobao_credit_level',$taobao_credit_level);
        $jd_credit_level = get_jd_credit_level();
        $this->assign('jd_credit_level',$jd_credit_level);
        $this->assign('brush_guests',$result);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 编辑用户-未通过的
     */
    public function refuseEdit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'BrushGuest');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                if ($data['f_cellphone']) {
                    $userModel = new UserModel();
                    $master = $userModel->where(['mobile'=>$data['f_cellphone'],'user_type'=>3])->find();
                    if (isset($master['id'])&&$master['id']>0) {
                        $data['p_user_id'] = $master['id'];
                    } else {
                        $this->error('未找到推荐人');
                    }
                }
                $birth_year = get_birthday_from_id_card($data['id_card_no'],'Y');
                if (!$birth_year) {
                    $birth_year=0;
                }
                $data['birth_year'] = $birth_year;
                $data['auditor']=0;
                $data['audit_remarks']='';
                $data['audit_status']=0;
                $data['update_time']=time();
                $brushGuestModel = new BrushGuestModel;
                $brushGuestModel->allowField(true)->save($data, ['id'=>$data['id']]);
                $brush = $brushGuestModel->find($data['id']);

                if ($brush['user_id']>0) {
                    $userModel = new UserModel();
                    $userModel->save(['mobile'=>$data['cellphone'],'user_login'=>$data['cellphone']],['id'=>$brush['user_id']]);
                }
                $this->success("编辑成功！", url('BrushGuest/auditList'));

            }
        }else{
            $id = $this->request->param("id", 0, 'intval');
            $brushGuestModel = new BrushGuestModel;
            $result = $brushGuestModel->find($id)->toArray();
            $this->assign('data',$result);
            $jobsModel = new JobsModel();
            $jobs = $jobsModel->where('status = 1')->select()->toArray();
            $this->assign('jobs',$jobs);
            $res = get_tb_credit_level();
            $taobao_credit_level =[];
            foreach ($res as $value){
                $taobao_credit_level[$value['id']]=$value;
            }
            $this->assign('taobao_credit_level',$taobao_credit_level);
            $res = get_jd_credit_level();
            $jd_credit_level =[];
            foreach ($res as $value){
                $jd_credit_level[$value['id']]=$value;
            }
            $this->assign('jd_credit_level',$jd_credit_level);
            return $this->fetch();
        }
    }

    public function resetPass()
    {
        $id = $this->request->param("user_id", 0, 'intval');
        $data = [
        'user_pass'=>cmf_password('123456'),
        ];
        $userModel = new UserModel();
        $userModel->save($data,['id'=>$id]);
        $this->success('密码重置成功');
    }
}