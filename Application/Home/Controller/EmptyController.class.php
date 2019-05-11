<?php

namespace Home\Controller;

use Think\Controller;

class EmptyController extends Controller
{
    //防止用户访问不存在的控制器
    public function index()
    {
        redirect(U('Home/Index/index'));
    }
}