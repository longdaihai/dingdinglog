<?php
namespace longdaihai\ding;

use longdaihai\ding\DingBot;
use think\contract\LogHandlerInterface;
use think\facade\Request;
/**
 * 钉钉日志驱动
 */
class DingLog implements LogHandlerInterface
{
    /**
     * 钉钉自定义机器人类
     * @var DingBot
     */
    protected $ding;
    /**
     * 配置信息
     * @var array
     */
    protected $config = [
        'webhook' => '',
        'at' => [],
        'show_params' => false,
        'show_included_files' => false,
        'debug' => true,
    ];

    function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config,$config);
        }
        $this->ding = new DingBot($this->config);
    }

    /**
     * 日志消息保存
     * @param  array  $log 日志信息
     * @return [type]      [description]
     */
    public function save(array $log = []):bool
    {

        $trace = [];

        if ($this->config['debug']) {

            if (isset($_SERVER['HTTP_HOST'])) {
                $current_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            } else {
                $current_uri = 'cmd:' . implode(' ', $_SERVER['argv']);
            }

            // 基本信息
            $trace[] = $current_uri;
        }
        if ($this->config['show_params']) {
            $trace[] = '[params]'.var_export(Request::param(),true);
        }

        foreach ($log as $type => $val) {
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }
                $trace[] = '[ ' . $type . ' ]'.$msg;
            }
        }

        if ($this->config['show_included_files']) {
            $trace[] = '[ file ]'.implode("\n", get_included_files());
        }

        $res = $this->send($trace);

        return true;
    }

    /**
     * 发送钉钉消息
     * @param  array  $logs 日志内容数组
     * @return [type]       [description]
     */
    protected function send(array $logs):bool
    {
        $msg = "";
        foreach ($logs as $k => $v) {
            $msg = $msg.$v."\n";
        }
        $res = $this->ding->at($this->config['at'])->text($msg);
        return $res;
    }
}