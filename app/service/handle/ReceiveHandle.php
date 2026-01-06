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

namespace app\service\handle;

use app\service\service\WechatService;
use think\admin\Service;

/**
 * 授权公众号消息转发处理
 * Class ReceiveHandle
 * @package app\service\serivce
 */
class ReceiveHandle extends Service
{
    /**
     * 事件初始化
     * @param string $appid
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handler($appid)
    {
        try {
            $wechat = WechatService::WeChatReceive($appid);
        } catch (\Exception $exception) {
            return "Wechat message handling failed, {$exception->getMessage()}";
        }
        // 验证微信配置信息
        $config = $this->app->db->name('WechatConfig')->where(['authorizer_appid' => $appid])->find();
        if (empty($config) || empty($config['appuri'])) {
            sysoplog('微信接口', $message = "微信{$appid}授权配置验证无效");
            return $message;
        }
        try {
            [$data, $openid] = [$wechat->getReceive(), $wechat->getOpenid()];
            if (isset($data['EventKey']) && is_object($data['EventKey'])) $data['EventKey'] = (array)$data['EventKey'];
            $params = ['appid' => $appid, 'openid' => $openid, 'params' => json_encode($data)];
            [$params['receive'], $params['encrypt']] = [serialize($data), intval($wechat->isEncrypt())];
            p($params);
            if (is_string($result = http_post($config['appuri'], $params, ['timeout' => 30]))) {
                return is_array($json = json_decode($result, true)) ? $wechat->reply($json, true, $wechat->isEncrypt()) : $result;
            }
        } catch (\Exception $exception) {
            sysoplog('微信接口', "微信{$appid}接口调用异常，{$exception->getMessage()}");
        }
        return 'success';
    }
}