<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
   // 默认模板渲染规则 1 解析为小写+下划线
  'auto_rule'    => 1,
  'layout_on'     =>  true,
  'layout_name'   =>  'layout',
  // 全局替换
  'tpl_replace_string'  =>  [
    '__STATIC__'=>'/static',
    '__COMMON__'=>'/assets',
    '__JS__' => '/assets/pay/assets/js',
    '__CSS__' => '/assets/pay/assets/css',
    '__IMG__' => '/assets/pay/assets/images',
    '__PHOTOS__' => '/assets/pay/photos',
    '__PLUGINS__' => '/assets/pay/plugins',
  ]
];
