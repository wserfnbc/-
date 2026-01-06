<?php
namespace think;

use think\admin\service\SystemService;

// 加载基础文件
require __DIR__ . '/vendor/autoload.php';

// WEB应用初始化
SystemService::instance()->doInit();