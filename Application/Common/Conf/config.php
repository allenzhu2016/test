<?php
return array(
    //配置数据库 PDO
    'DB_TYPE' => 'mysql',
    'DB_USER' => 'root',
    'DB_PWD' => '1234',
    'DB_NAME' => 'acfinance',
    'DB_PORT' => 3306,
    //配置分界符
    'TMPL_L_DELIM' => '<{',
    'TMPL_R_DELIM' => '}>',
    //配置模板
    'LAYOUT_ON' => true,
    'LAYOUT_NAME' => 'Layout/layout',
    //配置表单令牌
    'TOKEN_ON' => true,  // 是否开启令牌验证 默认关闭
    'TOKEN_NAME' => '__hash__',    // 令牌验证的表单隐藏字段名称，默认为__hash__
    'TOKEN_TYPE' => 'md5',  //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET' => true,  //令牌验证出错后是否重置令牌 默认为true
    'URL_MODEL' => 2,
    'DEFAULT_MODULE' => 'Home',
    'URL_HTML_SUFFIX' => ''
);