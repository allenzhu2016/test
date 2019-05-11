<?php
/*如果有需要 公共函数写在这里*/
//测试：类似laravel的dd函数
function dd($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    die();
}


//Bootstrap分页样式替换
function bootstrapPagination($show=false){
    if ($show){
        $search=['<div>','</div>','<a','</a>','<span class="current">','</span>'];
        $replace=['','','<li><a','</a></li>','<li class="active"><a href="#">','<span class="sr-only">(current)</span></a></li>'];
        $show=str_replace($search,$replace,$show);
    }else{
        $show='';
    }
    return $show;
}