<?php

// +----------------------------------------------------------------------
// | ThinkOpen
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkOpen
// | github 代码仓库：https://github.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------

namespace app\service\controller;

use think\admin\Controller;

/**
 * 开放平台参数配置
 * Class Config
 * @package app\service\controller
 */
class Config extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'WechatConfig';

    /**
     * 开放平台配置
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '开放平台配置';
        $this->geoip = $this->app->cache->get('mygeoip', '');
        if (empty($this->geoip)) {
            $this->geoip = gethostbyname($this->request->host());
            $this->app->cache->set('mygeoip', $this->geoip, 360);
        }
        $this->fetch();
    }

    /**
     * 修改开放平台参数
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            $this->fetch('form');
        } else {
            $post = $this->request->post();
            foreach ($post as $k => $v) sysconf($k, $v);
            $this->success('参数修改成功！');
        }
    }

}