<?php

namespace ffan\php\git;

use ffan\php\logger\Logger;
use ffan\php\logger\LoggerFactory;
use ffan\php\utils\ConfigBase;
use ffan\php\utils\InvalidConfigException;
use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Str as FFanStr;

class GitRepo extends ConfigBase
{
    /**
     * @var string 目录
     */
    private $repo_path;

    /**
     * @var string 配置名称
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
     * @var array 结果消息
     */
    private $result_msg;

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
    }

    /**
     * 初始化
     */
    public function init()
    {
        if (!is_dir($this->repo_path) || !is_dir(FFanUtils::joinPath($this->repo_path, '.git'))) {
            $this->cloneFromRemote();
            $branch = $this->getConfigString('branch');
            if (!empty($branch)) {
                $this->checkout($branch);
            }
            $user_name = $this->getConfigString('username');
            $email = $this->getConfigString('email');
            if (!empty($user_name) && !empty($email)) {
                $this->runCommand('config user.name ' . escapeshellcmd($user_name));
                $this->runCommand('config user.email ' . escapeshellcmd($email));
            }
        }
        $this->pushResult('Git init');
    }

    /**
     * 运行结果
     * @param string $msg
     */
    private function pushResult($msg)
    {
        if (!is_array($this->result_msg)) {
            $this->result_msg = array();
        }
        $this->result_msg[] = $msg;
    }

    /**
     * 设置结果数组
     * @param array $result_msg
     */
    public function setResultMsg(array &$result_msg)
    {
        $this->result_msg = &$result_msg;
    }

    /**
     * 获取执行结果¬
     * @return string
     */
    public function getResultMsg()
    {
        if (empty($this->result_msg)) {
            return '';
        }
        return join(PHP_EOL, $this->result_msg);
    }

    /**
     * 从远程克隆
     * @return array
     * @throws InvalidConfigException
     */
    private function cloneFromRemote()
    {
        $url = $this->getConfigString('url');
        if (empty($url)) {
            throw new InvalidConfigException('Can not get git remote url config');
        }
        return $this->runCommand('clone ' . $url . ' ' . $this->repo_path);
    }

    /**
     * 运行git 命令
     * @param string $command
     * @return array
     */
    private function runCommand($command)
    {
        $command = $this->bin_path . ' ' . $command;
        return $this->executeCommand($command);
    }

    /**
     * 执行linux 命令
     * @param string $command
     * @return array;
     */
    private function executeCommand($command)
    {
        $fd_pec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();
        $this->logger->info($command);
        $this->pushResult($command);
        $resource = proc_open($command, $fd_pec, $pipes, $this->repo_path);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($resource);
        $result = '';
        if (!empty($stdout)) {
            $result .= "\n" . $stdout;
        }
        if (!empty($stderr)) {
            $result .= "\n" . $stderr;
        }
        $this->pushResult($result);
        $this->logger->info($result);
        return array('cmd' => $command, 'result' => $result);
    }

    /**
     * 获取状态
     * @param bool $is_short 是否是简洁模式
     * @return array
     */
    public function status($is_short = false)
    {
        $cmd = 'status';
        if ($is_short) {
            $cmd .= ' -s';
        }
        return $this->runCommand($cmd);
    }

    /**
     * 添加文件
     * @param string $files
     * @param string $arg add 参数
     * @return array
     */
    public function add($files = '*', $arg = '')
    {
        $files = escapeshellarg($files);
        $cmd = 'add '. $files;
        if (!empty($arg)) {
            $cmd .= ' -'. $arg;
        }
        return $this->runCommand($cmd);
    }

    /**
     * 提交
     * @param string $message
     * @return array
     */
    public function commit($message)
    {
        return $this->runCommand('commit -m ' . escapeshellarg($message));
    }

    /**
     * 切换分支
     * @param string $branch
     * @return array
     */
    public function checkout($branch)
    {
        return $this->runCommand('checkout ' . escapeshellarg($branch));
    }

    /**
     * 拉取远程分支到本地
     * @param string $branch
     * @return array
     */
    public function fetch($branch)
    {
        if (!is_string($branch) || empty($branch)) {
            throw new \InvalidArgumentException('Invalid branch');
        }
        $cmd = 'checkout --track origin/'. escapeshellarg($branch);
        return $this->runCommand($cmd);
    }

    /**
     * 推送
     * @param string $remote
     * @param string $branch
     * @return array
     */
    public function push($remote = '', $branch = '')
    {
        return $this->runCommand('push ' . $remote . ' ' . $branch);
    }

    /**
     * 拉代码
     * @param string $remote 远程仓库地址
     * @param string $branch 分支
     * @return array
     */
    public function pull($remote = '', $branch = '')
    {
        $cmd = 'pull';
        if (!empty($remote)) {
            $cmd .= ' ' . $remote;
        }
        if (!empty($branch)) {
            $cmd .= ' ' . $branch;
        }
        return $this->runCommand($cmd);
    }

    /**
     * merge
     * @param $branch
     * @return array
     */
    public function merge($branch)
    {
        return $this->runCommand('merge ' . escapeshellarg($branch) . ' --no-ff');
    }

    /**
     * 获取所在的目录
     */
    public function getRepoPath()
    {
        return $this->repo_path;
    }

    /**
     * 获取本地分支列表
     * @return array
     */
    public function getLocalBranch()
    {
        $result = $this->runCommand('branch');
        $branch_list = explode(PHP_EOL, $result['result']);
        $result = array();
        foreach ($branch_list as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }
            if ('*' === $item{0}) {
                $item = ltrim($item, '* ');
                $result['use'] = $item;
            }
            $result['branch'][] = $item;
        }
        return $result;
    }

    /**
     * 执行git 命令
     * @param string $cmd
     * @return array
     */
    public function run($cmd)
    {
        return $this->runCommand($cmd);
    }

    /**
     * 获取所有变更过的文件
     * @param bool $ignore_tmp_file 是否过滤以 . 开始的文件
     * @return array
     */
    public function getChangeFiles($ignore_tmp_file = true)
    {
        $result = array();
        $run_re = $this->status(true);
        $status_str = $run_re['result'];
        if (empty($status_str)) {
            return $result;
        }
        $status_lines = FFanStr::split($status_str, PHP_EOL);
        foreach( $status_lines as $each_line) {
            $tmp = FFanStr::split($each_line, ' ');
            //如果是以 . 开始的文件, 忽略
            if (empty($tmp[1])) {
                continue;
            }
            $file = $tmp[1];
            if ('.' === $file{0} && $ignore_tmp_file) {
                continue;
            }
            $result[] = $file;
        }
        return $result;
    }
}
