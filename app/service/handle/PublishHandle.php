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
 * 授权公众上线测试处理
 * Class PublishHandle
 * @package app\service\serivce
 */
class PublishHandle extends Service
{
    /**
     * 事件初始化
     * @param string $appid
     * @return string
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function handler($appid)
    {
        try {
            $wechat = WechatService::WeChatReceive($appid);
        } catch (\Exception $e) {
            return "Wechat message handling failed, {$e->getMessage()}";
        }
        /* 分别执行对应类型的操作 */
        switch (strtolower($wechat->getMsgType())) {
            case 'text':
                $receive = $wechat->getReceive();
                if ($receive['Content'] === 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                    return $wechat->text('TESTCOMPONENT_MSG_TYPE_TEXT_callback')->reply([], true);
                } else {
                    $key = str_replace("QUERY_AUTH_CODE:", '', $receive['Content']);
                    WechatService::WeOpenService()->getQueryAuthorizerInfo($key);
                    return $wechat->text("{$key}_from_api")->reply([], true);
                }
            case 'event':
                $receive = $wechat->getReceive();
                return $wechat->text("{$receive['Event']}from_callback")->reply([], true);
            default:
                return 'success';
        }
    }
}