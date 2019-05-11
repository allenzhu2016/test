<?php

namespace Home\Model;
class AdminModel
{
    //用户验证登录
    public function verify($user, $pwd)
    {
        $admin = M('admin');
        $map['user'] = trim($user);
        $pwd = trim($pwd);
        if (($admin = $admin->where($map)->find())) {
            if ($admin['pwd'] == md5($pwd)) {//密码不匹配
                return $admin['lev'];
            } else {
                return false;
            }
        } else {//不存在账号
            return false;
        }
    }
}