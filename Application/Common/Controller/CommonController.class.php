<?php
/*
* Created by PhpStorm.
* User: allen
* Date: 2017/10/3
* Time: 11:28
*
 */
namespace Common\Controller;

use Think\Controller;
use Think\Model;
require_once   APP_PATH. '../vendor/autoload.php';
class CommonController extends Controller
{

    //存储操作记录
    public function save_record($what)
    {
        $model = new Model();
        $data['who'] = session('user');
        $data['what'] = $what;
        $model->table('operation')->add($data);
    }

    //根据id返回姓名 每次记录操作时带上名字
    public function get_name($uid)
    {
        $model = new Model();
        $map['id'] = (int)$uid;
        return $model->table('client')->where($map)->getField('name');
    }

}