<?php

namespace Home\Controller;

use Common\Controller\CommonController;
use Think\Model;

class ClientController extends CommonController
{
    //空控制器: 防止用户访问本控制器下不存在的方法
    public function _empty()
    {
        $this->redirect('Home/client/index');
    }

    //客户管理控制器
    public function index()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        C('TOKEN_ON', false);//关闭表单验证
        $query_field = array('', 'name', 'property');
        $client = M('client');
        $property = M('property');

        if (I('query_type') == '1') {//按照名字查询 like
            $like = '%' . trim(I('query')) . '%';
            $map[$query_field[I('query_type')]] = array('like', $like);
        } elseif (I('query_type') == '2') {//按照房产查询
            $like = '%' . trim(I('query')) . '%';
            $map['project'] = array('like', $like);
            $res = $property->where($map)->field('cid')->select();
            unset($map);

            if (count($res) > 0) {
                $map['id'] = array('in', array_column($res, 'cid'));
            } else {
                $map['id'] = false;
            }
        } elseif (I('query_type') == '3') {//按余额小于查询
            $balance = (int)I('query');
            $map['balance'] = array('elt', $balance);
        } else {//直接过来的非查询
            $map['name'] = array('exp', 'is not null');
        }


        $count = $client->where($map)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $show = bootstrapPagination($show);
        if (($res = $client->where($map)->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->field('id,name,balance,profile,sale,saletype,referencenum')->select()) === false) {
            $this->error('系统繁忙,请再次尝试');
            die();
        };
        //查询完客户资料后 追查property;
        if ((count($res)) > 0) {
            unset($map);
            foreach ($res as $key => $val) {
                $map['cid'] = $val['id'];
                if ($pro = $property->where($map)->field('project')->select()) {
                    $prostr = null;
                    foreach ($pro as $p) {
                        $prostr = $prostr . ';' . $p['project'];
                    }
                    $res[$key]['property'] = substr($prostr, 1);
                } else {
                    $res[$key]['property'] = '';
                }
            }
        }


        $refer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        cookie('clientpage', $refer);
        $query = array('query' => I('query'), 'type' => I('query_type'));
        $this->assign('query', $query);//
        $this->assign('count', $count);// 查到总的记录数
        $this->assign('client', $res);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板
    }

    //修改客户资料
    public function info()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {//id为空
            $this->redirect('Home/Client/index');
            die();
        }
        $id = (int)I('id');
        $client = D('client');
        $property = D('property');
        if (!($res = $client->clientid($id))) {
            $this->redirect(cookie('clientpage'));
            die();
        };

        //存在客户 继续查询房产信息
        $map['cid'] = $res['id'];
        if (($pro = $property->where($map)->select()) === false) {//查询失败
            $this->redirect(cookie('clientpage'));
            die();
        } else {
            $res['property'] = $pro;
        }


        $this->assign('client', $res);
        $this->display();
    }


    //查看客户详细资料
    public function detail()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {//id为空
            $this->redirect('Home/Client/index');
            die();
        }

        $id = (int)I('id');
        $client = D('client');
        if (!($res = $client->clientid($id))) {
            $this->redirect('Home/Pay/index');
            die();
        };
        $this->assign('client', $res);
        $this->display();
    }

    //接收数据信息
    public function update()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {//id为空
            $this->redirect('Home/Client/index');
            die();
        }
        $model = new Model();
        if ($model->autoCheckToken($_POST)) {
            if (I('id') == '') {
                $this->redirect('Client/index');
                die();
            } else {
                $map['id'] = (int)I('id');
                $data['name'] = trim(I('name'));
                $data['balance'] = number_format(trim(I('balance')), 2, '.', '');
                $data['sale'] = trim(I('sale'));
                $data['saletype'] = trim(I('saletype'));
                $data['referencenum'] = trim(I('referencenum'));
                $data['profile'] = trim(I('profile'));
            }

            if ($model->table('client')->where($map)->save($data)) {
                $this->success('客户信息更新成功!', cookie('clientpage'));
                die();
            } else {
                $this->error('您暂未更新任何信息....');
            }
        } else {
            $this->success('亲,您在重复提交表单,请再次编辑提交');
        }
    }

    //删除客户-->如果readytopay里面有客户的支付记录以及transfer表里面有客户的汇款记录  就不能删除该客户
    public function delete()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {//id为空
            $this->redirect('Home/Client/index');
            die();
        } else {
            $map['uid'] = (int)I('id');
        }
        $flag = true;//false代表不能删除该数据
        $model = new Model();
        if ($model->table('readytopay')->where($map)->select()) {//检测是否有数据在readytopay表里面
            $flag = false;
        }
        if ($flag) {//readytopay表没有数据.检验transfer表是否有数据
            if ($model->table('transfer')->where($map)->find()) {//transfer表有数据
                $flag = false;
            }
        }
        if ($flag) {
            unset($map);
            $map['id'] = (int)I('id');
            $condition['cid'] = $map['id'];
            $res1 = $model->table('client')->where($map)->delete();
            $res2 = $model->table('property')->where($condition)->delete();
            if ($res1 !== false && $res2 !== false) {
                $this->success('您已成功删除该用户', cookie('clientpage'), 3);
                die();
            } else {
                $this->error('系统繁忙,请再次尝试操作', cookie('clientpage'), 3);
                die();
            }
        } else {
            $this->success('系统检测到该用户有账单存在,您暂时不能删除', cookie('clientpage'), 3);
            die();
        }
    }

    //客户资料打印
    public function printing()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        session('time', null);
        session('note', null);
        $this->display();
    }


    public function produce()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if ((!IS_POST) || !(I('uid'))) {
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
        $transfer = M('transfer');
        $map['uid'] = (int)I('uid');
        $max_regtime = strtotime($transfer->where($map)->max('regtime'));
        $readytopay = M('readytopay');
        $max_time = $readytopay->where($map)->max('time');
        unset($map['uid']);

        $map['id'] = (int)I('uid');
        $client = M('client');
        //此处根据id更新过户时间
        $data['passtime'] = $_POST['time'];
        $data['notes'] = $_POST['note'];
        if (($res = $client->where($map)->data($data)->save()) === false) {
            $this->error('网络出错啦,请再次尝试操作...');
            die();
        };
        $name = $client->where($map)->getField('name');
        session('time', I('time'));
        session('note', I('note'));
        $this->assign('max_regtime', $max_regtime);
        $this->assign('max_time', $max_time);
        $this->assign('name', $name);
        $this->assign('clientid', I('uid'));
        $this->display();
    }

    //生成PDF
    public function pdf()
    {// 客户姓名作为文件名

        $config = [
            'mode' => '+aCJK',
            // "allowCJKoverflow" => true,
            "autoScriptToLang" => true,
            // "allow_charset_conversion" => false,
            "autoLangToFont" => true,
        ];

        $mpdf = new  \Mpdf\Mpdf($config);
        $mpdf->SetWatermarkImage(
            './public/logo.png',
            1,
            '',
            array(90, 120)
        );
        $mpdf->watermarkImageAlpha = 0.3;
        $mpdf->showWatermarkImage = true;
//获取要生成的静态文件
        $clientid = I('uid');
        $time = I('time');
        $note = I('note');
        $html = $this->clientpdf($clientid);
//设置PDF页眉内容
        $header = '';
//设置PDF页脚内容
        $footer = '';
//添加页眉和页脚到pdf中
        //$mpdf->SetHTMLHeader($header);
        //$mpdf->SetHTMLFooter($footer);
//设置pdf显示方式
        $mpdf->SetDisplayMode('fullpage');
//设置pdf的尺寸为270mm*397mm
//$mpdf->WriteHTML('<pagebreak sheet-size="270mm 397mm" />');
//创建pdf文件
        $mpdf->WriteHTML($html);

//删除pdf第一页(由于设置pdf尺寸导致多出了一页)
//$mpdf->DeletePages(1,1);
//输出pdf
        $client = M("client"); // 实例化User对象
        $condition['id'] = $clientid;
// 把查询条件传入查询方法
        if (!($name = $client->where($condition)->getField('name'))) {
            $name = $clientid;
        }
        $name .= '.pdf';
        $mpdf->Output($name, 'I');
        exit;
    }

    //又要修改 (灬ꈍ ꈍ灬):Printing 页面调出--->Notes

    public function getnotes()
    {
        if (!session('?user')) {
            die();
        }
        if (IS_GET) {
            header('content-type:text/html;charset=utf8');
            $client = D('client');
            if ($res = $client->clientnote(I('id'))) {
                echo json_encode($res);
            } else {//不存在数据
                echo '';
            }
        }

    }


    //调用打印的PDF-->客户信息,readytopay pid不为null的记录 ; transfer表里面的记录
    private function clientpdf($clientid)
    {
        if (!$clientid) {
            $this->error();
            die();
        } else {
            $clientid = (int)$clientid;
            $map['id'] = $clientid;
        }

        $time = session('time');
        $note = session('note');

        $flag = true;//检验数据是否全面
        $model = new Model();
        if (($client = $model->table('client')->where($map)->field('name,balance,referencenum')->find()) === false) {
            $flag = false;//失败: 提取客户资料时
        } else {
            $client['property'] = explode('$$', $client['property']);
        }
        unset($map);
        $map['uid'] = (int)$clientid;
        if (($transfer = $model->table('transfer')->where($map)->field('transfertime,amount,account,type,receipt,remark')->order('transfertime ASC,id ASC')->select()) === false) {
            $flag = false;//失败:提取客户汇款记录时
        }
        unset($map);
        $map['readytopay.uid'] = $clientid;
//       $map['readytopay.pid']=array('exp','is not null');
        $rtp = M('readytopay');
        if (($paid = $rtp->join('LEFT JOIN paid ON readytopay.pid = paid.id')->where($map)->field('paid.regtime,readytopay.id,readytopay.amount,paid.receiver,paid.account,paid.receipt,readytopay.applytime,readytopay.remark')->order('paid.regtime ASC,paid.id desc')->select()) === false) {
            $flag = false;//失败:提取总部支出记录时
        }

        $paid_1 = [];
        $paid_2 = [];
        $new_list = [];
        foreach ($paid as $key => $val) {
            //	   $val['remark']=preg_replace('/Salt Water Coast/','SWC', $val['remark']);
            if (!$val['regtime']) {//支付时间不存在
                $paid_2[$key] = $val;//未支付的
            } else {
                $paid_1[$key] = $val;//已支付的
            }
        }

        if (count($paid_2)) {
            $new_list = $this->list_sort_by($paid_2, 'id', 'desc');
        }

        $paid = array_merge($paid_1, $new_list);


        $income = 0;//计算客户总汇入
        foreach ($transfer as $t) {
            $income += $t['amount'];
        }

        $outcome = 0;//计算总部总支出
        foreach ($paid as $p) {
            $outcome += $p['amount'];
        }
        //客户房产
        $property = M('property');
        $condition['cid'] = $clientid;
        if (($properties = $property->where($condition)->field('project,loancomp,passtime')->select()) === false) {
            $flag = false;
        };

        if (!$flag) {//提取数据不完整
            $this->success('系统繁忙,请再次尝试操作...');
            die();
        }


        $this->assign('client', $client);
        $this->assign('time', $time);
        $this->assign('note', $note);
        $this->assign('transfer', $transfer);
        $this->assign('income', $income);
        $this->assign('property', $properties);
        $this->assign('outcome', $outcome);
        $this->assign('paid', $paid);
        $content = $this->fetch('Client:clientpdf');
        return $content;
    }


    private function list_sort_by($list, $field, $sortby = 'asc')
    {
        if (is_array($list)) {
            $refer = $resultSet = array();
            foreach ($list as $i => $data) {
                $refer[$i] = &$data[$field];
            }
            switch ($sortby) {
                case 'asc': // 正向排序
                    asort($refer);
                    break;
                case 'desc': // 逆向排序
                    arsort($refer);
                    break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
            }
            foreach ($refer as $key => $val) {
                $resultSet[] = &$list[$key];
            }
            return $resultSet;
        }
        return false;
    }


    //添加新客户
    public function register()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        $this->display();
    }

    //接收新客户数据
    public function handle()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }

        $flag = true;
        $Model = new Model();
        $Model->startTrans();
        if ($Model->autoCheckToken($_POST)) {
//添加客户基本信息
            $dir = "D://客户档案/" . I('name') . '/';
            if (!(is_dir($dir))) {
                mkdir(iconv('utf-8', 'gbk', $dir));
            };
            $clientid = $Model->table('client')->add(['name' => I('name'), 'sale' => I('sale'), 'saletype' => I('saletype'), 'balance' => I('balance'), 'referencenum' => I('referencenum'), 'profile' => $dir]);

            $passtime = I('passtime');
            $lot = I('lot');
            $loancomp = I('loancomp');
//成功后就继续添加房产信息
            if ($clientid) {
                if (count($lot) > 0) {//添加客户房产
                    foreach (I('lot') as $key => $val) {
                        if ($val) {//有效值
                            if (!($propertyRes = $Model->table('property')->add(['passtime' => $passtime[$key], 'project' => $val, 'loancomp' => $loancomp[$key], 'cid' => $clientid]))) {
                                $flag = false;//有房产未添加成功
                            };
                        }
                    }
                }
            } else {
                //添加客户信息失败
                $flag = false;
            }

            if ($flag) {
                $Model->commit();
                $this->success('添加成功');
            } else {
                $Model->rollback();
                $this->success('系统繁忙,请再次尝试添加');
            }
        } else {
            $this->success('亲,请不要重复提交...');
            die();
        }
    }


    //客户付款
    public function transfer()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        $this->display();
    }

    //接收客户付款处理
    public function receive()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('transfertime') == '' or I('account') == '' or I('receipt') == '' or I('type') == '' or I('remark') == '' or I('uid') == '') {
            $this->redirect('Home/Client/transfer');
            die();
        } else {
            $data['transfertime'] = strtotime(I('transfertime'));
            $data['amount'] = trim(I('amount'));
            $data['account'] = trim(I('account'));
            $data['type'] = trim(I('type'));
            $data['remark'] = trim(I('remark'));
            $data['uid'] = (int)trim(I('uid'));
            $data['receipt'] = trim(I('receipt'));
            I('amount') ? $amount = trim(I('amount')) : $amount = 0;
        }
        $flag = false;
        $model = new Model();
        $model->startTrans();//添加汇款记录--->更改balance
        if ($model->table('transfer')->add($data)) {//添加汇款记录成功
            $map['id'] = (int)I('uid');//客户id
            if (($model->table('client')->where($map)->setInc('balance', $amount))) {
                $model->commit();
                $flag = true;
            }//更新余额
        }
        if ($flag) {
            $this->success('添加客户汇款成功!', U('Client/transfer'), 3);
            die();
        } else {
            $model->rollback();
            $this->error('系统繁忙,请再次操作');
            die();
        }
    }

    //所有客户付款的记录
    public function record()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        C('Token_on', false);
        if (I('query_type') == '1') {//按照名字查询 like
            $like = '%' . trim(I('query')) . '%';
            $map['client.name'] = array('like', $like);
        } elseif (I('query_type') == '2') {//按距离天数查询
            $days = (int)I('query');
            $querytime = time() - ($days * 3600 * 24);
            $map['transfertime'] = array('egt', $querytime);
        } elseif (I('query_type') == '3') {//按备注查询
            if (strripos(I('query'), 'and')) {//多条件查询
                $remark = explode('and', I('query'));
                foreach ($remark as $val) {
                    if (($val = trim($val))) {
                        $arr[] = '%' . $val . '%';
                    }
                }
                $map['transfer.remark'] = array('like', $arr, 'and');
            } else {//但条件查询
                $like = "%" . trim(I('query')) . "%";
                $map['transfer.remark'] = array('like', $like);
            }
        } elseif (I('query_type') == '4') {//按照发票类型
            $like = '%' . trim(I('query')) . '%';
            $map['transfer.receipt'] = array('like', $like);
        } elseif (I('query_type') == '5') {//按照方式
            $like = '%' . trim(I('query')) . '%';
            $map['transfer.type'] = array('like', $like);

        } else {
            $map['transfertime'] = array('egt', 1);
        }
        $transfer = D('TransferView'); // 实例化User对象
        $count = $transfer->where($map)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $transfer->order('transfertime DESC,id Desc')->limit($Page->firstRow . ',' . $Page->listRows)->where($map)->select();
        $refer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        cookie('historypage', $refer);

//追加property查询
        $property = M('property');
        if (count($list) > 0 && $list) {
            foreach ($list as $key => $val) {
                unset($map);
                $map['cid'] = $val['cid'];
                if (($pro = $property->where($map)->field('project')->select())) {
                    $project = array_column($pro, 'project');
                    $list[$key]['property'] = implode(';', $project);
                } else {
                    $list[$key]['property'] = null;
                }

            }
        }

        //整合boostrap分页样式  替换div 并包裹<li>标签
        $show = bootstrapPagination($show);
        $this->assign('query', array('query' => I('query'), 'type' => I('query_type')));// 赋值数据集
        $this->assign('transfer', $list);// 赋值数据集
        $this->assign('count', $count);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板

    }


//编辑transfer
    public function edittransfer()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {
            $this->error();
        }
        //查询数据
        $model = D('TransferView');
        $map['id'] = (int)I('id');
        if (($res = $model->where($map)->find())) {
            $this->assign('trans', $res);
            $this->display();
        } else {
            $this->error('系统繁忙,请再次尝试操作');
        }

    }

//更新balance 修改transfer表-->修改balance
    public function updatetrans()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '' or I("name") == "" or I("account") == "" or I("type") == "" or I("receipt") == "" or I("transfertime") == "" or I("remark") == "") {
            $this->error();
            die();
        } else {
            $map['id'] = (int)I('id');
            $data = array(
                'amount' => number_format(trim(I('amount')), 2, '.', ''),
                'account' => trim(I('account')),
                'type' => trim(I('type')),
                'receipt' => trim(I("receipt")),
                'transfertime' => strtotime(I("transfertime")),
                'remark' => trim(I('remark')));
        }
        $model = new Model();
        $flag = true;//true代表可以commit
        if (!$model->autoCheckToken($_POST)) {//检验是否重复提交数据
            $this->success('亲,您在重复提交操作');
            die();
        }
        //对比数据 是否需要修改
        if ($res = $model->table('transfer')->where($map)->field('amount,account,type,receipt,transfertime,remark,uid')->find()) {
            $diff = array_diff_assoc($data, $res);
            if ((count($diff) < 1)) {//对比数据
                $this->success('您当前并没有做任何修改');
                die();
            }
        }
        //如果有修改则有两种情况 1没有动balance  2动balance
        unset($map);
        $map['id'] = (int)I('id');//transfer id
        if ($diff['amount'] == '') {//没有动amount-->balance无需改动
            if ($model->table('transfer')->where($map)->save($diff)) {
                $this->success('修改汇款信息成功', cookie('historypage'), 3);
                die();
            };
        }
        //  2动balance
        unset($map);
        unset($data);
        $model->startTrans();
        $map['id'] = $res['uid'];//客户id
        if (!($model->table('client')->where($map)->setDec('balance', $res['amount']))) {
            $flag = false;
        } else {
            if (!($model->table('client')->where($map)->setInc('balance', I('amount')))) {
                $flag = false;
            } else {//balance修改成功 -->修改transfer amount字段
                unset($map);
                $map['id'] = (int)I('id');//这是transfer id
                if (!($model->table('transfer')->where($map)->save($diff))) {//修改transfer失败
                    $flag = false;
                }
            }
        }
        if ($flag) {
            $model->commit();
            $this->success('修改汇款信息成功', cookie('historypage'), 3);
            die();
        } else {
            $model->rollback();
            $this->success('系统繁忙,请再次尝试操作');
            die();
        }
    }

    //删除汇款记录  需要删除transfer表记录 以及更改客户balance
    public function deletetrans()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (I('id') == '') {
            $this->error();
        }
        $flag = false;
        $map['id'] = base64_decode(I('id'));
        $model = new Model();
        if ($res = $model->table('transfer')->where($map)->field('id,amount,uid')->find()) {//transfer表是否存在该条记录
            $model->startTrans();//开启事务
            unset($map);
            $map['id'] = $res['uid'];
            if ($balance = $model->table('client')->where($map)->getField('balance')) {//查询balance
                $data['balance'] = $balance - $res['amount'];
                if ($model->table('client')->where($map)->save($data)) {//更改client表balance成功
                    unset($map);
                    $map['id'] = $res['id'];
                    if ($model->table('transfer')->where($map)->delete()) {  //更改transfer表成功 删除成功
                        $flag = true;
                    }
                }
            }
        }
        if ($flag) {
            $model->commit();
            $this->success('取消成功,正在为您跳转中...', cookie('historypage'), 3);
        } else {
            $model->rollback();
            $this->error('系统繁忙,请再次尝试操作...', cookie('historypage'), 3);
        }
    }


    //客户注册时验证客户是否存在
    public function clientname()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        }
        if (IS_AJAX) {
            if (I('name') !== '') {
                $client = M('client');
                $map['name'] = array('eq', trim(I('name')));
                if ($client->where($map)->count() == 0) {
                    $res['valid'] = true;
                } else {
                    $res['valid'] = false;
                }
            } else {
                $res['valid'] = false;
            }
            print_r(json_encode($res));
        }
    }


    //删除一套房子
    public function delproperty()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        } else {

            if (IS_AJAX) {
                $property = M('property');
                $map['id'] = I('id');
                if ($property->where($map)->delete() !== false) {
                    $res['valid'] = true;
                } else {
                    $res['valid'] = false;
                }
                print_r(json_encode($res));

            }
        }
    }

//添加一套房产
    public function addproperty()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        } else {
            if (IS_AJAX) {
                $property = M('property');
                $data['cid'] = I('cid');
                if (($pid = $property->add($data)) !== false) {
                    $str = '<div class="row"><div class="col-xs-4"><input style="margin-bottom: 14px" value="" type="text" name="lot[]" placeholder="例如:Lot 188 Saltwater" class="form-control lot"></div><div class="col-xs-2"><input style="margin-bottom: 14px;" value="" type="text" name="loancomp[]" placeholder="贷款公司" class="form-control loancomp"></div><div class="col-xs-3"><input style="margin-bottom: 14px;" value="" type="text" name="passtime[]" placeholder="过户时间"  data-date-format="yyyy-mm-dd"  class="form-control reg_time"></div><div class="col-xs-3 text-right"><span data="' . $pid . '" style="margin-top: 3px" class="btn btn-sm btn-default fa fa-remove propertyremove" title="删除"></span><span data="' . $pid . '" style="margin-top: 3px;margin-left: 4px" class="btn btn-sm btn-default fa fa-refresh propertyrefresh" title="更新"></span></div></div>';
                    $res['valid'] = $str;
                } else {
                    $res['valid'] = false;
                }
                print_r(json_encode($res));

            }
        }
    }

    //更新一套房产
    public function updateproperty()
    {
        if (!session('?user')) {
            $this->redirect('Home/Index/login');
        } else {
            if (IS_AJAX) {
                $property = M('property');
                $data['project'] = I('project');
                $data['loancomp'] = I('loancomp');
                $data['passtime'] = I('passtime');
                $map['id'] = I('id');
                if (($pid = $property->where($map)->save($data)) !== false) {
                    $res = 1;
                } else {
                    $res = 0;
                }
                print_r($res);

            }
        }
    }

}