<?php

namespace app\service\command;

use app\service\service\WechatService;
use think\admin\Command;
use think\console\Input;
use think\console\Output;

class WechatRun extends Command
{
    protected function configure()
    {
        $this->setName('xsync:wechat')->setDescription('Synchronize all authorized wechat data');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        $offset = 0;
        $wechat = WechatService::WeOpenService();
        while (true) {
            $result = $wechat->getAuthorizerList(500, $offset);
            foreach ($result['list'] as $item) {
                $ustr = str_pad(++$offset, strlen("{$result['total_count']}"), '0', STR_PAD_LEFT);
                $this->setQueueProgress(2, "({$ustr}/{$result['total_count']}) 开始同步公众号 {$item['authorizer_appid']} 数据");
                if (isset($item['authorizer_appid']) && isset($item['refresh_token']) && isset($item['auth_time'])) {
                    $data = array_merge(WechatService::buildAuthData($wechat->getAuthorizerInfo($item['authorizer_appid'])), [
                        'authorizer_refresh_token' => $item['refresh_token'], 'is_deleted' => '0',
                        'authorizer_appid'         => $item['authorizer_appid'], 'auth_time' => $item['auth_time'],
                    ]);
                    $where = ['authorizer_appid' => $item['authorizer_appid']];
                    $appkey = $this->app->db->name("WechatConfig")->where($where)->value('appkey');
                    if (empty($appkey)) $data['appkey'] = md5(uniqid('', true));
                    if (data_save("WechatConfig", $data, 'authorizer_appid')) {
                        $this->setQueueProgress("({$ustr}/{$result['total_count']}) 更新公众号 {$item['authorizer_appid']} 数据成功", $offset * 100 / $result['total_count']);
                    } else {
                        $this->setQueueProgress("({$ustr}/{$result['total_count']}) 更新公众号 {$item['authorizer_appid']} 数据失败", $offset * 100 / $result['total_count']);
                    }
                } else {
                    $this->setQueueProgress("({$ustr}/{$result['total_count']}) 更新公众号 {$item['authorizer_appid']} 数据失败", $offset * 100 / $result['total_count']);
                }
            }
            if ($offset >= $result['total_count']) break;
        }
    }
}