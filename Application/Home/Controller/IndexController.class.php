<?php

namespace Home\Controller;

use Common\Controller\CommonController;
use Home\Model\AdminModel;
use Think\Model;


class IndexController extends CommonController
{

    //空操作器: 防止用户访问本控制器下不存在的方法
    public function _empty()
    {
        $this->redirect('Home/Pay/index');
    }

    //本控制器下的默认方法: 为了让URL更容易理解 直接转至login方法
    public function index()
    {
        $this->redirect('Index/login');
    }

    //用户登录界面展示: 只允许在非登录状态下访问该方法
    public function login()
    {
        if (session('?user')) {//验证是否登录
            $this->redirect('Home/Pay/index');
            die();
        };
        $this->display();
    }

    //验证登录
    public function loginverify()
    {
        if (IS_POST) {
            if (session('?user')) {//验证是否登录
                $this->redirect('Home/Pay/index');
                die();
            };
            $m = new Model();
            if ($m->autoCheckToken($_POST)) {//验证令牌
                if (I('user') == '' or I('pwd') == '') {
                    $this->redirect('Index/login');
                } else {
                    $admin = new AdminModel();//数据合法进行验证
                    if ($res = $admin->verify(I('user'), I('pwd'))) {//返回lev
                        session('user', I('user'));
                        session('lev', $res);
                        $this->redirect('Pay/index');
                    } else {
                        $this->assign('error', '账号或密码不正确');
                        $this->display('Index/login');
                    }
                }
            } else {
                $this->redirect('Index/login');
            }
        } else {
            $this->redirect('Index/login');
        }
    }

    //用户退出登录
    public function logout()
    {
        if (session('?user')) {
            session('[destroy]'); // 销毁session
        };
        $this->redirect('Index/login');
    }

}







