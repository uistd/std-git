<?php

namespace ffan\php\git;

use ffan\php\logger\Logger;
use ffan\php\logger\LoggerFactory;
use ffan\php\utils\ConfigBase;
use ffan\php\utils\InvalidConfigException;
use ffan\php\utils\Utils as FFanUtils;

class GitRepo extends ConfigBase
{
    /**
     * @var string 目录
     */
    private $repo_path;

    /**
     * @var string 执行错误
     */
    private $std_error;

    /**
     * @var string aliasa 名称
     */
    private $name;

    /**
     * @var string git可执行文件目录
     */
    private $bin_path;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * GitRepo constructor.
     * @param string $name
     * @param array $config_arr
     */
    public function __construct($name, $config_arr)
    {
        $this->name = $name;
        $this->initConfig($config_arr);
        $this->logger = LoggerFactory::get($this->getConfigString('log_file', 'git'));
        $this->bin_path = $this->getConfigString('git_bin', '/usr/bin/git');
        $this->repo_path = FFanUtils::fixWithRuntimePath($this->getConfigString('repo_path', $this->name));
        $this->init();
    }

    /**
     * 初始化
     */
    private function init()
    {
        if (!is_dir($this->repo_path) || !is_file(FFanUtils::joinPath($this->repo_path, '.git'))) {
            $this->cloneFromRemote();
        }
        $branch = $this->getConfigString('branch');
        $this->checkout($branch);

    }

    /**
     * 从远程克隆
     */
    private function cloneFromRemote()
    {
        $url = $this->getConfigString('url');
        if (empty($url)) {
            throw new InvalidConfigException('Can not get git remote url config');
        }
        $this->runCommand('clone '. $url .' '. $this->repo_path);
    }

    /**
     * 运行git 命令
     * @param string $command
     * @return string
     */
    private function runCommand($command)
    {
        $fd_pec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $pipes = array();
        $command = $this->bin_path . ' ' . $command;
        $this->logger->info($command);
        $resource = proc_open($command, $fd_pec, $pipes, $this->repo_path);
        $stdout = stream_get_contents($pipes[1]);
        $this->std_error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($resource);
        if (!empty($stdout)) {
            $this->logger->info($stdout);
        }
        if (!empty($this->std_error)) {
            $this->logger->info($this->std_error);
        }
        return $stdout;
    }

    /**
     * 获取状态
     * @return string
     */
    public function status()
    {
        return $this->runCommand('status');
    }

    /**
     * 添加文件
     * @param string $files
     * @return string
     */
    public function add($files = '*')
    {
        return $this->runCommand("add $files -v");
    }

    /**
     * 提交
     * @param string $message
     * @return string
     */
    public function commit($message)
    {
        return $this->runCommand('commint -m ' . escapeshellarg($message));
    }

    /**
     * 切换分支
     * @param string $branche
     * @return string
     */
    public function checkout($branche)
    {
        return $this->runCommand('checkout ' . escapeshellarg($branche));
    }

    /**
     * 推送
     * @param string $remote
     * @param string $branche
     * @return string
     */
    public function push($remote = '', $branche = '')
    {
        return $this->runCommand('push ' . $remote . ' ' . $branche);
    }

    /**
     * 拉代码
     * @param string $remote
     * @param string $branche
     * @return string
     */
    public function pull($remote = '', $branche = '')
    {
        return $this->runCommand('pull ' . $remote . ' ' . $branche);
    }

    /**
     * merge
     * @param $branch
     * @return string
     */
    public function merge($branch)
    {
        return $this->runCommand('merge ' . escapeshellarg($branch) . ' --no-ff');
    }
}
