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

namespace app\service\service;

use think\admin\Service;

/**
 * 公众号授权配置
 * Class ConfigService
 * @package app\service\service
 */
class ConfigService extends Service
{
    /**
     * 数据查询条件
     * @var array
     */
    protected $map;

    /**
     * 当前微信APPID
     * @var string
     */
    protected $appid;

    /**
     * 当前微信配置
     * @var array
     */
    protected $config;

    /**
     * 授权配置初始化
     * @param string $appid
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function init($appid)
    {
        $this->map = ['authorizer_appid' => $this->appid = $appid];
        $this->config = $this->app->db->name('WechatConfig')->where($this->map)->find();
        if (empty($this->config)) throw new \think\Exception("公众号{$appid}还没有授权！");
        return $this;
    }

    /**
     * 获取当前公众号配置
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 设置微信接口通知URL地址
     * @param string $notifyUri 接口通知URL地址
     * @return boolean
     * @throws \think\Exception
     * @throws \think\db\exception\DbException
     */
    public function setApiNotifyUri($notifyUri)
    {
        if (empty($notifyUri)) throw new \think\Exception('请传入微信通知URL');
        return $this->app->db->name('WechatConfig')->where($this->map)->update(['appuri' => $notifyUri]) !== false;
    }

    /**
     * 更新接口Appkey(成功返回新的Appkey)
     * @return string
     * @throws \think\db\exception\DbException
     */
    public function updateApiAppkey()
    {
        $data = ['appkey' => md5(uniqid())];
        $this->app->db->name('WechatConfig')->where($this->map)->update($data);
        return $data['appkey'];
    }

    /**
     * 获取公众号的配置参数
     * @param string $name 参数名称
     * @return array|string
     */
    public function config($name = null)
    {
        return WechatService::WeChatScript($this->appid)->config->get($name);
    }

    /**
     * 微信网页授权
     * @param string $sessid 当前会话id(可用session_id()获取)
     * @param string $source 当前会话URL地址(需包含域名的完整URL地址)
     * @param integer $type 网页授权模式(0静默模式,1高级授权)
     * @return array|boolean
     */
    public function oauth($sessid, $source, $type = 0)
    {
        $fans = $this->app->cache->get("{$this->appid}_{$sessid}_fans", []);
        $openid = $this->app->cache->get("{$this->appid}_{$sessid}_openid", '');
        if (!empty($openid) && !empty($type) && !empty($fans)) {
            return ['openid' => $openid, 'fans' => $fans, 'url' => ''];
        }
        $mode = empty($type) ? 'snsapi_base' : 'snsapi_userinfo';
        $params = ['mode' => $type, 'sessid' => $sessid, 'enurl' => enbase64url($source)];
        $location = url('@service/api.push/oauth', [], false, true) . '?' . http_build_query($params);
        $oauthurl = WechatService::WeOpenService()->getOauthRedirect($this->appid, $location, $mode);
        return ['openid' => $openid, 'fans' => $fans, 'url' => $oauthurl];
    }

    /**
     * 微信网页JS签名
     * @param string $url 当前会话URL地址(需包含域名的完整URL地址)
     * @return array|boolean
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function jsSign($url)
    {
        return WechatService::WeChatScript($this->appid)->getJsSign($url);
    }
}