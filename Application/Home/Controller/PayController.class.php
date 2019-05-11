<?php

namespace Home\Controller;

use Common\Controller\CommonController;
use Think\Model;

class PayController extends CommonController
{
    //空控制器: 防止用户访问本控制器下不存在的方法
    public function _empty()
    {
        $this->redirect('Home/Pay/index');
        die();
    }


    //构造方法
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Australia/Sydney");
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
            die();
        }
    }

    //待付款录入
    public function index()
    {
        $this->display();
    }

    //aJax 检查输入的客户id是否存在
    public function checkid()
    {
        if (IS_AJAX) {
            $client = M('client');
            $map['id'] = I('uid');
            if ($name = $client->where($map)->getField('name')) {
                $arr = array('valid' => 'true', 'name' => $name);//存在表示可以
            } else {
                $arr = array('valid' => 'false');//不存在表示不可以
            }
            echo json_encode($arr);
        } else {
            return false;
        }
    }

    //待付款录入时查询客户资料
    public function search()
    {
        $User = M('client'); // 实例化User对象
        $like = "%" . trim(I('name')) . "%";
        $map['name'] = array('like', $like);
        $count = $User->where($map)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 5);// 实例化分页类 传入总记录数和每页显示的记录数(10)
        $Page->rollPage = 5;
        $show = $Page->show();// 分页显示输出
// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        if ($res = $User->where($map)->order('id')->limit($Page->firstRow . ',' . $Page->listRows)->select()) {
            //追加property
            $flag = true;
            if (count($res) && $res) {
                $property = M('property');
                foreach ($res as $key => $val) {
                    unset($map);
                    $map['cid'] = $val['id'];
                    if (($pro = $property->where($map)->field('project')->select()) !== false) {
                        if (count($pro) > 0 && $pro) {
                            $str = null;
                            foreach ($pro as $p) {
                                $str = $str . ';' . $p['project'];
                            }
                            $res[$key]['property'] = substr($str, '1');
                        }
                    } else {
                        $flag = false;
                    }
                }
            }

            echo '<table class="table table-bordered text-center">
                   <tr>
                        <td style="width: 20%">#</td>
                        <td style="width: 20%">客户</td>
                        <td style="width: 40%">Lot</td>
                        <td style="width: 20%">余额</td>
                    </tr>';

            foreach ($res as $val) {
                echo '<tr>
                        <td style="height: 20px;"><p><a target="_blank" href="' . U('client/info', array('id' => $val['id'])) . '">' . $val['id'] . '</a></p></td>
                        <td style="height: 20px;"><p style="overflow: hidden;white-space: nowrap;text-overflow: ellipsis">' . $val['name'] . '</p></td>
                        <td style="height: 20px;"><p style="overflow: hidden;white-space: nowrap;text-overflow: ellipsis">' . $val['property'] . '</p></td>
                        <td style="height: 20px;"><p>' . $val['balance'] . '</p></td>
                    </tr>';
            }
            echo '</table>' . '<nav aria-label="Page navigation"><ul class="pagination">' . bootstrapPagination($show) . '</ul></nav>';
        } else {//不存在数据
            echo '<p style="color: red;">没有查到相关信息...</p>';
        }

    }


    //已录入查询控制器
    public function check()
    {
        C('TOKEN_ON', false);
        $rtp = M('readytopay'); // 实例化User对象
        $map['pid'] = array('exp', "is null");
        $count = $rtp->where($map)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 1000);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
        $readytopayview = D('ReadytopayView');
        $list = $readytopayview->where($map)->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //记录url
        $refer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        cookie('backward', $refer);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板
    }

    //checkorder打印
    public function order()
    {
        if (!I('params')) {
            $this->redirect('Home/pay/check');
        } else {
// 拿到order[]
            $bill_id = explode(',', base64_decode(I('params')));
            $model = new Model();
            $map['readytopay.id'] = array('in', $bill_id);
            $res = $model->table('readytopay')->join('client on readytopay.uid=client.id')->field('readytopay.id,client.name as name,client.referencenum,readytopay.remark,readytopay.amount')->where($map)->select();
            $htmlstr_head = '<html><head><style>body{font-family: BIG5}</style></head><body><table  border="1" cellpadding="0" cellspacing="0" bordercolor="black" style="padding: 5px;border-collapse:collapse;font-size: 12px;margin-right:auto;margin-left:auto;width: 550px"><thead><tr class="text-center"><th style="padding: 7px;width: 100px;text-align: center">Client Name</th><th style="padding: 7px;width: 100px;text-align: center">Reference Number</th><th style="padding: 7px;width: 300px;text-align: center">Purpose</th><th style="padding: 7px;width: 100px;text-align: center">Amount</th></tr></thead><tbody>';
            $html_body = '';
            $subtotal = 0;

//要根据$bill_id再次排序
            if ($res) {
                foreach ($bill_id as $key => $val) {
                    foreach ($res as $k => $v) {
                        if ($v['id'] == $val) {
                            $arr[] = $v;
                        }
                    }
                }
            } else {
                $this->redirect('Home/pay/check');
            }
            $res = $arr;
            foreach ($res as $t) {
                $subtotal += $t['amount'];
                $new = '<tr style="border:0.2px solid black"><td style="padding: 7px;text-align: center">' . $t['name'] . '</td>
            <td style="text-align:center;padding: 7px">' . $t['referencenum'] . '</td>
            <td style="text-align:center;padding: 7px">' . $t['remark'] . '</td>
            <td style="text-align:center;padding: 7px">$' . $t['amount'] . '</td></tr>';
                $html_body = $html_body . $new;
            }
            $htmlstr_foot = '<tr><td style="padding: 7px;text-align:center">Cheque Order Subtotal</td><td colspan="3" style=";text-align:center;font-size: 14px;font-weight: 700;padding: 7px">$' . $subtotal . '</td></tr></tbody></table>';
            $htmlstr = $htmlstr_head . $html_body . $htmlstr_foot . '</body></html>';
        }
        if ($res) {

            $mpdf = new  \Mpdf\Mpdf();
//获取要生成的静态文件
            $clientid = I('uid');
            $time = I('time');
            $html = $htmlstr;
            //   $mpdf->SetDisplayMode('fullpage');
            $mpdf->WriteHTML($html);
            $name = date('Y-m-d') . " checkorder.pdf";
            $mpdf->Output($name, 'I');
            exit;
        } else {
            $this->redirect('Home/pay/check');
        }
    }


    //已录入查询 页面的数据查询
    public function checksearch()
    {
        C('TOKEN_ON', false);
        $query_type = array('1', '2');
        if (!in_array(I(query_type), $query_type) || I('query') == '') {
            $this->redirect('Home/pay/check');
            die();
        }
        $model = new Model();
        if (I('query_type') == '1') {//表示按照名字来查询
            $sql = "SELECT client.name,readytopay.amount,readytopay.applytime,readytopay.remark,readytopay.time,readytopay.id FROM client JOIN readytopay ON readytopay.uid=client.id AND readytopay.pid IS NULL AND client.name LIKE '%" . trim(I('query')) . "%'";
        } else {
            if (strripos(I('query'), 'and')) {//多条件查询
                $remark = explode('and', I('query'));
                foreach ($remark as $val) {
                    if (($val = trim($val))) {
                        $queries[] = "remark like '%" . $val . "%'";
                    }
                }
                $remark = implode(' and ', $queries);
            } else {//单条件查询
                $remark = "remark like '%" . trim(I('query')) . "%'";
            }
            $sql = "SELECT client.name,readytopay.amount,readytopay.applytime,readytopay.remark,readytopay.time,readytopay.id FROM client JOIN readytopay ON readytopay.uid=client.id AND readytopay.pid IS NULL AND " . $remark;
        }
        if (($list = $model->query($sql)) === false) {
            $this->redirect('Home/pay/check');
            die();
        } else {
            $query['query'] = I('query');
            $query['type'] = I('query_type');
            $this->assign('query', $query);
            $this->assign('list', $list);// 赋值数据集
            $this->display('pay/check'); // 输出模板
        }
    }

    //已录入数据修改
    public function edit()
    {
        $map['readytopay.id'] = (int)I('rid');
        $model = new Model();
        if ($res = $model->table('readytopay')->join('client on readytopay.uid=client.id')->field('readytopay.id as id,name,amount,applytime,remark,time,uid')->where($map)->find()) {
            $this->assign('reg', $res);
            $this->display();
        } else {
            $this->redirect('Pay/check');
        }

    }

    //修改数据接收
    public function update()
    {
        if (I('applytime') == '' or I('amount') == '' or I('remark') == '' or I('rid') == '') {
            $this->error();
            die();
        }
        $rtp = M('readytopay');
        if (!($rtp->autoCheckToken($_POST))) {
            $this->success('您在重复提交修改,请刷新后再次操作');
            die();
        }
        $map['id'] = (int)I('rid');//账单id
        if ($res = $rtp->where($map)->find()) {
            //比对结果
            if (trim(I('remark')) !== $res['remark']) {
                $data['remark'] = I('remark');
            }
            if (number_format(I('amount'), 2, '.', '') !== $res['amount']) {
                $data['amount'] = I('amount');
            }
            if (trim(strtotime(I('applytime'))) !== trim($res['applytime'])) {
                $data['applytime'] = strtotime(I('applytime'));
            }
            $client_id = $res['uid'];
            $old_amount = $res['amount'];
            if ($data !== null) {
                //更新数据
                if (!$data['amount']) {//有数据要更新的前提下看看是否要更新balance
                    if ($rtp->where($map)->save($data)) {
                        if (cookie('backward') == '') {
                            $url = U('Pay/check');
                        } else {
                            $url = cookie('backward');
                        }
                        $dd = '';
                        if ($data['applytime']) {
                            $data['applytime'] = date('Y-m-d', $data['applytime']);
                        }
                        foreach ($data as $d) {
                            $dd = $dd . ' (' . $d . ') ';
                        }

                        $clientname = $this->get_name($client_id);
                        $what = "(位置:已录入查询) (操作:编辑) 修改了($clientname) 的一笔已录入账单 修改信息:$dd";
                        $this->save_record($what);
                        $this->success('更新成功', $url);
                    } else {
                        $this->error('系统繁忙');
                    }
                } else {//需要事务操作更新balance
                    $rtp->startTrans();
                    $client = M('Client');
                    $where['id'] = $client_id;
                    $amount = $old_amount - $data['amount'];  //需要更新的钱 假设原来是10块 现在是5块 增加5块
                    if (($rtp->where($map)->save($data)) && ($client->where($where)->setInc('balance', $amount))) {
                        $rtp->commit();
                        if (cookie('backward') == '') {
                            $url = U('Pay/check');
                        } else {
                            $url = cookie('backward');
                        }
                        //记录用户操作开始
                        $dd = '';
                        if ($data['applytime']) {
                            $data['applytime'] = date('Y-m-d', $data['applytime']);
                        }

                        $data['amount'] = '$' . $data['amount'];

                        foreach ($data as $d) {
                            $dd = $dd . ' (' . $d . ') ';
                        }
                        $clientname = $this->get_name($client_id);
                        $what = "(位置:已录入查询) (操作:编辑) 修改了($clientname) 的一笔已录入账单 修改信息:$dd";
                        $this->save_record($what);
                        //记录用户操作结束
                        $this->success('更新成功', $url);
                    } else {
                        $rtp->rollback();
                        $this->error('系统繁忙');
                    }
                }
            } else {
                $this->success('您并未做任何修改');
            }
        } else {
            $this->error('系统繁忙');
        }
    }


    //待付款录入---->事务操作: 增加readytopay表记录 ----- 更新balance
    public function regist()
    {
        if (I('applytime') == '') {//due day
            $applytime = time();//默认为当前时间
        } else {
            $applytime = strtotime(trim(I('applytime')));//换成时间轴
        }
        $readytopay = M('readytopay');
        $readytopay->startTrans();
        $client = M('Client');
        $data = array(
            "uid" => I('uid'),
            "amount" => I('amount'),
            "applytime" => $applytime,
            "remark" => I('remark'),
            'time' => time()
        );
        $map['id'] = I('uid');
        $off = (int)I('amount');
        if (($res1 = $readytopay->add($data)) && ($res2 = $client->where($map)->setDec('balance', $off))) {
            $readytopay->commit();//成功则提交
            $this->success('申请成功!');
        } else {
            $readytopay->rollback();
            $this->error('系统繁忙,请再次尝试');
        }
    }

    //删除已录入数据  流程: 查到金额  删除记录 更新balance
    public function delete()
    {
        if (I('rid') == '') {
            $this->redirect('Home/Index/login');
            die();
        } else {
            $map['id'] = (int)I('rid');
        }

        $rtp = M('readytopay');
        if (!($info = $rtp->where($map)->field('remark,amount,uid')->find())) {
            $this->redirect('Home/pay/check');
            die();
        };
        $where['id'] = (int)$info['uid'];

        $rtp->startTrans();
        $client = M('Client');
        if (($res1 = $rtp->where($map)->delete()) && ($res2 = $client->where($where)->setInc('balance', $info['amount']))) {
            $rtp->commit();
            //记录操作信息
            $clientname = $client->where($where)->getField('name');
            $what = "(位置:已录入查询) (操作:撤销) 删除了 ($clientname) 的一笔金额 (\${$info['amount']}),该金额用途: {$info['remark']}";
            $this->save_record($what);
            $this->success('删除成功');
        } else {
            $rtp->rollback();
            $this->error('系统繁忙 请再次尝试操作...');
        }

    }


    //待付款支付展示
    public function readytopay()
    {
        $query_arr = array(//与前端query_type 一一对应
            array(),
            array(" remark like '%Council rate%' ", " remark like  '%市政管理费%'"),
            array(" remark like '%Water rate%'", "remark like '%水费%'"),
            array(" remark like '%Gas bill%'", " remark like '%Gas Bill%'", "remark like '%煤气费%'"),
            array(" remark like '%Land Deposit%'", "remark like '%土地定金%'", "remark like '%土地订金%'"),
            array(" remark like '%Building Deposit%'", " remark like '%建筑定金%'", "remark like '%建筑订金%'"),
            array(" remark like '%Land Tax%'", " remark like '%土地税%'"),
            array(" remark like '%FIRB%'")
        );
        $query = '';
        if (I('query_type') !== 0) {
            $query = implode(' or ', $query_arr[I('query_type')]);
        }

        if (I('query') !== '') {
            if ($query) {
                $query = '(' . $query . ') and ' . "client.name like '%" . I('query') . "%'";
            } else {
                $query = "client.name like '%" . I('query') . "%'";
            }
        }
        //query字段已准备好
        if (!$query) {
            $sql = 'SELECT client.name,client.referencenum,readytopay.id,readytopay.time,readytopay.amount,readytopay.applytime,readytopay.remark FROM client JOIN readytopay ON readytopay.pid IS NULL AND readytopay.uid=client.id';
        } else {
            $sql = 'SELECT client.name,client.referencenum,readytopay.id,readytopay.time,readytopay.amount,readytopay.applytime,readytopay.remark FROM client JOIN readytopay ON readytopay.pid IS NULL AND  readytopay.uid=client.id AND (' . $query . ')';
        }
        $model = new Model();
        $bill = $model->query($sql);
        $query = array('query' => I('query'), 'type' => I('query_type'));
        $this->assign('query', $query);
        $this->assign('bill', $bill);
        $this->display();
    }

    //自定义支付表单
    public function myform()
    {
        $this->display();
    }

    //选择时-->形成支付注册表单
    public function paynow()
    {
        $readytopay = D('ReadytopayView');
        if (!($res = $readytopay->prepay($_POST['pay']))) {
            $this->redirect('Pay/readytopay');
            die();
        }
        $readytopay = D('Readytopay');
        $this->assign('total', $readytopay->totalmoney($res));
        $this->assign('bill', $res);
        $this->display();
    }

    //自定义支付录入处理
    public function handle()
    {
        $flag = true;
        $model = new Model();
        $model->startTrans();
        if (!$model->autoCheckToken($_POST)) {
            $this->success('您在重复提交表单,请再次尝试操作');
            die();
        }
        if (I('source') == '1') {//判断页面 如果是非自定义支付则执行
            $data['amount'] = trim(I('amount'));
            $data['receiver'] = I('receiver');
            $data['receipt'] = I('receipt');
            $data['payfor'] = I('payfor');
            $data['regtime'] = trim(strtotime(I('regtime')));
            $data['cat'] = 1;
            $data['account'] = I('account');
            if ($pid = $model->table('paid')->add($data)) {//支付成功  更改readytopay的pid
                unset($data);
                $map['id'] = array('in', session('id'));//这是readytopay中所有pid
                $data['pid'] = $pid;
                if ($model->table('readytopay')->where($map)->save($data)) {//更改pid成功
                    if ($flag) {
                        $model->commit();//成功
                        session('id', null);
                        $this->success('录入成功,正在为您跳转中...', U("Pay/readytopay"));
                    } else {
                        session('id', null);
                        $model->rollback();
                        $this->error('系统繁忙,请再次尝试...', U("Pay/readytopay"));
                        die();
                    }
                } else {//更改pid不成功
                    session('id', null);
                    $model->rollback();
                    $this->error('系统繁忙,请再次尝试...', U("Pay/readytopay"));
                    die();
                }
            } else {//支付不成功
                session('id', null);
                $model->rollback();
                $this->error('系统繁忙,请再次尝试...', U("Pay/readytopay"));
                die();
            }

        } else {//处理自定义支付信息 需要同时更改三个表 paid->readytopay->client
            $f = false;
            $data['amount'] = I('amount');
            $data['receiver'] = I('receiver');
            $data['receipt'] = I('receipt');
            $data['payfor'] = I('payfor');//这时就相当于remark
            $data['account'] = I('account');
            $data['cat'] = 0;//0表示自定义支付的账单  1表示选择支付型账单
            $data['regtime'] = strtotime(trim(I('regtime')));

            //两种情况  1.是带uid 2.是不带uid的

            if ($pid = $model->table('paid')->add($data)) {//修改paid成功
                unset($data);
                $data['pid'] = $pid;
                I('uid') ? $data['uid'] = I('uid') : $data['uid'] = null;
                $data['amount'] = I('amount');
                $data['applytime'] = strtotime(trim(I('regtime')));
                $data['time'] = time();
                $data['remark'] = I('payfor');
                if ($model->table('readytopay')->add($data)) {//修改readytopay成功

                    if (I('uid')) {
                        unset($map);
                        $map['id'] = I('uid');
                        if ($model->table('client')->where($map)->setDec('balance', I('amount'))) {//修改balance成功
                            $f = true;
                        }
                    } else {//不带uid的 无需操作balance
                        $f = true;
                    }
                }
            }
            if ($f) {
                $model->commit();
                $this->success('操作成功,系统正在为您跳转中...');
            } else {
                $model->rollback();
                $this->success('系统繁忙,请再次尝试');
            }
        }

    }


    //查询是否含有未关联客户的账单
    private function hasnouser()
    {
        $rtp = M('readytopay');
        $map['uid'] = array('EXP', 'IS NULL');
        if ($total = $rtp->where($map)->count()) {
            return $total;
        } else {
            return 0;
        }
    }

    //已支付查询
    public function paid()
    {
        $paid = M('paid');
        $count = $paid->count();// 查询满足要求的总记录数
        $model = new Model();
        $Page = new \Think\Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
        $show=bootstrapPagination($show);
        $currentpage = ((int)I('p'));
        if ($currentpage <= 1 or $currentpage > (floor($count / 15))) {
            $start = 0;
        } else {
            $start = (((int)I('p')) - 1) * 15;
        }
        $sql = 'SELECT client.name,readytopay.amount,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account,paid.receiver  FROM readytopay JOIN paid ON readytopay.pid = paid.id JOIN client ON readytopay.uid = client.id ORDER BY regtime DESC,receipt DESC LIMIT ' . $start . ' ,15';
        $list = $model->query($sql);
        $refer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];//记录当前页码
        cookie('history', $refer);
        $this->assign('list', $list);// 赋值数据集
        if ($count > 15) {
            $this->assign('page', $show);// 赋值分页输出
        }
        $this->assign('payment', $list);
        $this->assign('nouser', $this->hasnouser());
        $this->display();
    }


//删除已支付
    public function deletepaid()
    {
        if (I('cat') == '' or I('id') == '') {
            $this->redirect('Pay/paid');
            die();
        }
        if (I('cat') !== '0' && I('cat') !== '1') {
            $this->redirect('Pay/paid');
            die();
        }//根据参数来处理

        $model = new Model();
        $map['pid'] = (int)I('id');
        $existname = $model->table('readytopay')->where($map)->getField('uid');
        unset($map);
        $map['id'] = (int)I('id');
        $deleteinfo = $model->table('paid')->where($map)->find();
        unset($map);

        $model->startTrans();
        $flag = true;
        if (I('cat') == '0') {//自定义支付的  paid表删除-->readytopay删除-->balance更改
            $map['id'] = (int)I('id');
            if (!$model->table('paid')->where($map)->delete()) {
                //paid表删除成功
                $flag = false;
            }
            $map['pid'] = (int)I('id');
            unset($map['id']);

            if ((($um = $model->table('readytopay')->where($map)->field('uid,amount')->find()) === false)) {
                $flag = false;
            } else {//成功后删除readytopay
                if (!$model->table('readytopay')->where($map)->delete()) {
                    $flag = false;
                } else {
                    unset($map);
                    $map['id'] = $um['uid'];
                }
            }
            if ($map['id']) {//cat==0的情况需不需要修改balance取决于 自定义支付时是带uid还是不带uid
                if (!$model->table('client')->where($map)->setInc('balance', $um['amount'])) {
                    $flag = false;
                }
            }
        } else {//选择型支付 paid表删除-->readytpay表pid释放-->balance更改
            $map['id'] = (int)I('id');
            if (!$model->table('paid')->where($map)->delete()) {
                $flag = false;
            }
            unset($map);
            $data['pid'] = null;
            $map['pid'] = (int)I('id');
            $userid = $model->table('readytopay')->where($map)->field('uid')->select();
            //要拿到uid跟对应的amount
            if (($um = $model->table('readytopay')->where($map)->field('uid,amount')->select()) === false) {
                $flag = false;
            }
            if (!$model->table('readytopay')->where($map)->save($data)) {
                $flag = false;
            }
        }
        if ($flag) {
            $model->commit();

            //记录用户操作开始
            unset($deleteinfo['id']);
            $deleteinfo['regtime'] = '原始支付时间:' . date('Y-m-d', $deleteinfo['regtime']);
            $deleteinfo['amount'] = '原始支付金额: $' . $deleteinfo['amount'];
            $deleteinfo['receipt'] = '原始支票号码:' . $deleteinfo['receipt'];
            $deleteinfo['account'] = '原始支付账号:' . $deleteinfo['account'];
            $deleteinfo['receiver'] = '原始收款人:' . $deleteinfo['receiver'];
            $deleteinfo['payfor'] = '原始支付用途:' . $deleteinfo['payfor'];

            $dd = '';
            foreach ($deleteinfo as $d) {
                $dd = $dd . ' (' . $d . ') ';
            }
            if (I('cat') == 0) {

                if ($existname) {
                    $clientname = $this->get_name($um['uid']);
                    $what = "(位置:已支付查询) (操作:撤销) (账单类型: 自定义支付(带客户ID)) 删除了($clientname) 的一笔已支付账单 原支付信息:$dd";
                } else {//不存在名字的
                    $what = "(位置:已支付查询) (操作:撤销) (账单类型: 自定义支付(无客户ID)) 该账单没有客户ID 原支付信息:$dd";
                }
            } else {//选择型支付信息
                unset($map);
                $userid = array_unique(array_column($userid, 'uid'));
                $map['id'] = array('in', $userid);
                $clientnames = $model->table('client')->where($map)->field('name')->select();
                $clientnames = array_column($clientnames, 'name');
                $nn = '';
                foreach ($clientnames as $n) {
                    $nn = $nn . ' ' . $n;
                }

                $what = "(位置:已支付查询) (操作:撤销) (账单类型: 选择型支付) 相关联的客户( $nn ) 原支付信息:$dd";
            }
            $this->save_record($what);
            //记录用户操作结束

            $this->success('操作成功!', cookie('history'));
            die();
        } else {
            $model->rollback();
            $this->error('系统繁忙,请再次尝试...', cookie('history'));
            die();
        }
    }

    //修改已支付-->如果为0 就可能涉及到修改readytopay表  如果为1 就不会涉及到readytopay表
    public function paidedit()
    {
        if (I('cat') == '' || I('id') == '' || (I('cat') !== '1' && I('cat') !== '0')) {
            $this->redirect('Home/Pay/paid');
            die();
        }
        $model = new Model();
        $map['paid.id'] = (int)I('id');
        if ($paid = $model->table('paid')->join('LEFT JOIN readytopay ON readytopay.pid=paid.id')->where($map)->find()) {//查询到数据就载入
            if (I('cat') == '0') {//自定义支付  涉及到修改readytopay表
                $this->assign('paid', $paid);
                if ($paid['uid']) {
                    $this->display(); //如果存在uid表示之前支付时就输入了客户的id
                } else {
                    $this->display('Pay/paidedit_2');//说明当初输入账单时是没有输入客户id的
                }

            } else {//选择型支付 查询pid数据
                unset($map);
                $map['pid'] = (int)I('id');
                $M = D("ReadytopayView");
                if ($reg = $M->where($map)->select()) {//查到数据了
                    $count = count($reg);//账单数目
                    $total = 0;
                    foreach ($reg as $val) {
                        $total += $val['amount'];
                    }

                    $this->assign('count', $count);
                    $this->assign('total', $total);
                    $this->assign('paid', $paid);
                    $this->assign('reg', $reg);
                    $this->display('Pay/updatepaid');
                } else {//有id 没pid不合法
                    $this->error('系统繁忙,请再次尝试', cookie('history'));
                    die();
                }
            }
        } else {//没有查到数据
            $this->redirect('Pay/paid');
            die();
        }
    }

    //支付时候ajax索引出收款人
    public function receiver()
    {
        if (IS_AJAX) {//非ajax请求不予处理
            if (I('query') !== '') {//接收receiver参数
                $Model = new Model();
                $query = '%' . trim(I('query')) . '%';
                $map['receiver'] = array('like', $query);
                $receiver = $Model->table('paid')->distinct(true)->where($map)->field('receiver')->limit(5)->select();
                if (count($receiver) > 0) {
                    foreach ($receiver as $key => $val) {
                        $data[$key]['label'] = $val['receiver'];
                    }
                }
                print_r(json_encode($data));
            }
        }
    }


    //更新支付处理---->0.自定义支付的paid->readytopay-->balance     1选择型支付 因为amount不变 所以只涉及到paid表
    public function process()
    {
        $model = new Model();
        $model->startTrans();
        $flag = true;
        if (!$model->autoCheckToken($_POST)) {
            $this->success('亲,请不要重复提交表单！...');
            die();
        }
        if (I('cat') == '1') {//选择型支付的
            $map["id"] = (int)I('id');
            $data["account"] = trim(I('account'));
            $data["receiver"] = trim(I('receiver'));
            $data["receipt"] = trim(I('receipt'));
            $data["payfor"] = trim(I('payfor'));
            $data["regtime"] = strtotime(trim(I('regtime')));
            if ($res = $model->table('paid')->where($map)->field('account,receiver,receipt,payfor,regtime')->find()) {
                $res['regtime'] = trim($res['regtime']);
                $diff = array_diff_assoc($data, $res);
                if (count($diff) > 0) {
                    if (!$model->table('paid')->where($map)->save($diff)) {//保存失败
                        $flag = false;
                    }
                } else {
                    $this->success('您并未做任何修改');
                    die();
                }
            } else {//查询失败
                $flag = false;//属于系统繁忙
            }
        } elseif (I('cat') == '0' && I('uid')) {//自定义支付的-->paid表--->readytopay表-->client balance
            $map['id'] = (int)I('id');
            $data["account"] = I('account');
            $data["receiver"] = I('receiver');
            $data["receipt"] = I('receipt');
            $data["payfor"] = I('payfor');
            $data["amount"] = number_format(I('amount'), 2, '.', '');
            $data["regtime"] = strtotime(trim(I('regtime')));
            if (($res = $model->table('paid')->where($map)->field('amount,receipt,payfor,receiver,account,regtime')->find())) {
                $res['regtime'] = trim($res['regtime']);
                $diff = array_diff_assoc($data, $res);
                if (count($diff) > 0) {//比对后需要更改
                    if ($model->table('paid')->where($map)->save($diff)) { //paid表更改成功
                        unset($map);
                        $map['pid'] = I('id');
                        unset($data);
                        if ($diff['amount']) {
                            $data["amount"] = trim(I('amount'));
                        }
                        if ($diff['payfor']) {
                            $data["remark"] = trim(I('payfor'));
                        }
                        if ($data) {
                            if ($model->table('readytopay')->where($map)->save($data)) {
                                //readytopay表更改成功 现在要更改balance -->要看需不需要更改balance
                                //原来的钱10  现在的钱5块  加5块
                                if (($change = ($diff['amount'] - $res['amount'])) !== 0) {//需要更改balance

                                    unset($map);
                                    unset($data);
                                    $map['id'] = I('uid');
                                    //$res['amount']就是原来的钱,(I('amount')新输入的钱
                                    if (!($model->table('client')->where($map)->setDec('balance', $change))) {
                                        $flag = false;
                                    }//客户余额更新失败
                                }
                            } else {
                                $flag = false;
                            }
                        }
                    } else {
                        $flag = false;
                    }

                } else {
                    $this->success('您并未做任何修改');
                    die();
                }
            } else {
                $flag = false;
            }
        } elseif (I('cat') == '2') {//自定义支付 但是最初没带uid的
            //两种情况 2. 还是没填写uid 1.已填写uid
            $map['id'] = (int)I('id');
            $data["account"] = trim(I('account'));
            $data["receiver"] = trim(I('receiver'));
            $data["receipt"] = trim(I('receipt'));
            $data["payfor"] = trim(I('payfor'));
            $data["amount"] = number_format(trim(I('amount')), 2, '.', '');
            $data["regtime"] = strtotime(trim(I('regtime')));
            if (($res = $model->table('paid')->where($map)->field('amount,receipt,payfor,receiver,account,regtime')->find())) {
                $res['regtime'] = trim($res['regtime']);
                $diff = array_diff_assoc($data, $res);
                if (!$diff && !I('uid')) {//其他数据没有变动 也没有绑定uid
                    $this->success('您并没有做任何修改喔...');
                    die();
                }
            } else {//没有数据
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($diff) {//其他数据没有变动 只是绑定uid
                if ($model->table('paid')->where($map)->save($diff)) { //paid表更改成功

                    unset($map);
                    $map['pid'] = I('id');//换成rtp表
                    unset($data);
                    if ($diff['amount']) {
                        $data["amount"] = trim(I('amount'));
                    }
                    if ($diff['payfor']) {
                        $data["remark"] = trim(I('payfor'));
                    }

                    if ($data) {
                        if (!($model->table('readytopay')->where($map)->save($data))) {
                            $flag = false;
                        }
                    }

                } else {
                    $flag = 3;
                }
            }
            //根据有没有uid判断要不要更新balance
            if (I('uid')) {//没有uid就不需要再操作了  有uid就要rtp表  client表
                unset($map);
                unset($data);
                $map['pid'] = (int)I('id');
                $data['uid'] = (int)I('uid');
                if (!($model->table('readytopay')->where($map)->save($data))) {
                    $flag = false;
                }
                unset($map);
                unset($data);
                $map['id'] = (int)I('uid');
                $money = trim(I('amount'));
                if (!($model->table('client')->where($map)->setDec('balance', $money))) {
                    $flag = false;
                }//绑定客户余额更新失败
            }
        } else {
            redirect($_SERVER['HTTP_REFERER']);
            die();
        }

        if ($flag) {
            $model->commit();

            //记录操作开始
            if (I('cat') == '2' && I('uid')) {
                unset($map);
                unset($data);
                $map['id'] = I('id');
                $data["id"] = '(支付ID:' . I('id') . ')';
                $data["account"] = '(支付账号:' . trim(I('account')) . ')';
                $data["receiver"] = '(收款人:' . trim(I('receiver')) . ')';
                $data["receipt"] = '(支票号码:' . trim(I('receipt')) . ')';
                $data["payfor"] = '(备注:' . trim(I('payfor'));
                $data["regtime"] = '(支付时间:' . (trim(I('regtime'))) . ')';
                $data["amount"] = '(支付金额:$' . number_format(trim(I('amount')), 2, '.', '') . ')';
                $dd = '';
                foreach ($data as $d) {
                    $dd = $dd . ' ' . $d;
                }
                $clientname = $this->get_name(I('uid'));
                $what = "(位置:缺少客户账单) (操作:添加客户编号) (账单类型: 追加已支付账单至客户: $clientname ) 支付信息:$dd";
                $this->save_record($what);
            }

            //记录操作结束

            $this->success('修改信息成功,正在为您跳转中...', cookie('history'));
        } else {
            $model->rollback();
            $this->success('系统繁忙,请再次操作...');
        }
    }

    //已支付账单查询控制器
    public function paidsearch()
    {
        $model = new Model();
        if (I('query') !== '' && I('query_type') == '1') {
            $mintime = strtotime(trim(I('query'))) - (3600 * 12) - 1;
            $maxtime = strtotime(trim(I('query'))) + (3600 * 12) - 1;
            if ($mintime < 0 or ($maxtime <= $mintime)) {
                redirect(U('Pay/paid'));
            }
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id and readytopay.uid = client.id and (paid.regtime BETWEEN {$mintime} AND {$maxtime}) ORDER BY paid.regtime desc";
        } elseif (I('query') !== '' && I('query_type') == '2') {
            $receipt = '%' . trim(I('query')) . '%';
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id AND readytopay.uid = client.id AND paid.receipt LIKE'" . $receipt . "'";
        } elseif (I('query') !== '' && I('query_type') == '3') {//按照
            if (strripos(I('query'), 'and')) {//多条件查询
                $payfor = explode('and', I('query'));
                foreach ($payfor as $val) {
                    if (($val = trim($val))) {
                        $queries[] = "payfor like '%" . $val . "%'";
                    }
                }
                $payfor = implode(' and ', $queries);
            } else {//但条件查询
                $payfor = "payfor like '%" . trim(I('query')) . "%'";
            }
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id AND readytopay.uid = client.id AND " . $payfor . "order BY paid.regtime DESC";

        } elseif (I('query') !== '' && I('query_type') == '4') {
            $name = trim(I('query'));
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id AND readytopay.uid = client.id AND client.name LIKE '%" . $name . "%'";
        } elseif (I('query') !== '' && I('query_type') == '5') {//按照距离天数查询
            $mintime = time() - (((int)trim(I('query'))) * 3600 * 24);
            $maxtime = time() + (((int)trim(I('query'))) * 3600 * 24);
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id and readytopay.uid = client.id and (paid.regtime BETWEEN {$mintime} AND {$maxtime})";
        } elseif (I('query_type') == '6') {//没有客户的账单
            $sql = "SELECT readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid ON readytopay.pid = paid.id AND (readytopay.uid IS NULL)";
        } elseif (I('query_type') == '7') {//按照lot查询
            $name = trim(I('query'));
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id AND readytopay.uid = client.id AND client.property LIKE '%" . $name . "%'";

        } elseif (I('query_type') == '8') {//按照lot查询
            $name = trim(I('query'));
            $sql = "SELECT client.name,readytopay.amount,paid.receiver,paid.id,paid.payfor,paid.cat,paid.regtime,paid.receipt,paid.account FROM readytopay JOIN paid  JOIN client ON readytopay.pid = paid.id AND readytopay.uid = client.id AND paid.receiver LIKE '%" . $name . "%' ORDER BY paid.regtime DESC";
        } else {

            redirect(U('Pay/paid'));
        }
        cookie('history', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $paid = $model->query($sql);
        $query['query'] = I('query');
        $query['type'] = I('query_type');
        $this->assign('query', $query);
        $this->assign('payment', $paid);
        $this->assign('nouser', $this->hasnouser());
        $this->display('Pay/paid');
    }


    public function operation()
    {
        C('TOKEN_ON', false);
        $oper = M('operation'); // 实例化operation对象
        $count = $oper->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
        $show=bootstrapPagination($show);
        $list = $oper->order('ID DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $key => $val) {
            $list[$key]['what'] = str_replace(array('撤销', '添加客户编号'), array('<span style="color: orange">撤销</span>', '<span style="color: #00d600">添加客户编号</span>'), $val['what']);
        }
        $this->assign('record', $list);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板
    }


    //ajax修改remark
    public function remark()
    {
        if (IS_AJAX) {
            $map['id'] = I('id');
            $data['remark'] = I('remark');

            $rdp = M('readytopay');
            if ($rdp->where($map)->save($data)) {
                echo $data['remark'];
            } else {
                echo 0;
            }
        } else {
            $this->redirect($_SERVER['HTTP_REFERER']);
        }

    }


}