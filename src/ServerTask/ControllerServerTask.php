<?php

namespace SocketDaemon\ServerTask;

use Psr\Log\LoggerInterface;
use SocketDaemon\ServerTask\BaseServerTask;
use Exception;
use SocketDaemon\ClientTask\ControllerClientTask;

class ControllerServerTask extends BaseServerTask
{
    /**
     * @var array structure 'task_name' => command
     */
    protected $tasks;

    /**
     * @param string $host
     * @param int $port
     * @param array $tasks
     * @param string $greetingText
     * @param LoggerInterface $logger
     * @param int $backlog
     * @throws Exception
     */
    public function __construct($host, $port, array $tasks, $greetingText, LoggerInterface $logger, $backlog = 5)
    {
        parent::__construct($host, $port, $greetingText, $logger, $backlog);
        $this->tasks = $tasks;
    }


    /**
     * @throws Exception
     */
    public function run()
    {
        do {
            try {
                $this->connect();
                $this->write($this->greetingText . "\n");
                do {
                    $command = $this->read();
                } while ($this->processCommand($command));
                $this->closeConnection();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                // Нужно сделать закрытие коннеткта если ошибка была в $this->processCommand($command))
            }
        } while (true);
    }


    /**
     * @param $command
     * @return int pId
     */
    public function runProcess($command)
    {
        $this->logger->alert('Run command :'.$command);
        return exec(sprintf("%s > /dev/null 2>&1 & echo $!", $command));
    }


    /**
     * @param $id
     * @return bool
     */
    function isProcessRunning($id)
    {
        if (!$id) {
            return false;
        }
        $result = shell_exec(sprintf("ps %d", $id));
        if (count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
        return false;
    }

    public function killProcess($id)
    {
        exec(sprintf("kill %d", $id));
    }

    /**
     * @param $command
     * @return bool
     * @throws Exception
     */
    protected function processCommand($command)
    {
        if (!$command) {
            return true;
        }
        $this->logger->debug(sprintf('Process message: "%s"', $command));
        $commandParams = $this->getCommandParams($command);
        if (!$commandParams) {
            return true;
        }
        $this->logger->debug(print_r($commandParams, 1));
        if (!isset($this->tasks[$commandParams['taskName']])) {
            $this->logger->debug(sprintf('Task is not supported: "%s"', $command));
            return true;
        }
        /**
         * @var $task ControllerClientTask
         */
        $task = $this->tasks[$commandParams['taskName']];
        $this->logger->alert($task->getPid());
        switch ($commandParams['command']) {
            case 'task start':
                if ($this->isTaskRunning($task)) {
                    $this->write('Task is already running' . "\n");
                    break;
                }
                $this->write(sprintf('Task run pid: "%d"', $this->runTask($task)));
                break;
            case 'task stop':
                if (!$this->isTaskRunning($task)) {
                    $this->write('Task is not running' . "\n");
                    break;
                }
                $this->terminateTask($task);
                $this->write('Terminate task success' . "\n");
                break;
            case 'task info':
                if (!$this->isTaskRunning($task)) {
                    $this->write(sprintf('Task is not running. Last status : "%s"', $task->getLastStatus()));
                    break;
                }
                $this->logger->alert('Go');
                $status = $task->getStatus();
                // Check if task done
                switch ($status) {
                    case 'taskDone':
                        $this->clearTask($task);
                        break;
                    case 'errorDone':
                        $this->clearTask($task);
                        $task->setFailedCount($task->getFailedCount() + 1);
                        break;
                };
                $task->setLastStatus($status);
                $this->write(sprintf('Status : "%s"', $status));
                break;
            case 'daemon stat':
                // Обходим массив тасок собираем по каждой инорфмацию по каждой таксе
                // можно либо запрашивать данные у таски либо самому узнат используя метод getProcessParams
                //  $res = $this->getProcessParams($this->id);
                break;
            default:
                $this->write('Command :' . $command . '!');
        }
        return true;
    }

    /**
     * @param $command
     * @return array|null ['command' => ','taskName' = >]
     */
    protected function getCommandParams($command)
    {
        $commandParams = explode(' ', $command);
        if (count($commandParams) != 3) {
            return null;
        }
        return [
            'taskName' => $commandParams[2],
            'command' => $commandParams[0] . ' ' . $commandParams[1]
        ];
    }

    protected function isTaskRunning(ControllerClientTask $task)
    {
        if (!$task->checkConnection()) {
            if ($this->isProcessRunning($task->getPid())) {
                $this->killProcess($task->getPid());
                // Free memory for socket
                $task->closeConnection();
                $task->setPid(0);
            }
            return false;
        }
        return true;
    }

    protected function getProcessParams($pId)
    {
        $res = exec(sprintf('ps %d  -eo pcpu,pmem', $pId));
        // Парсим результат (не успел сделать)
        return $res;
    }

    /**
     * I did not have time to do
     * @param $command
     */
    protected function getTaskNameFromMessage($command)
    {

    }

    /**
     * I did not have time to do
     * @param $command
     */
    protected function getCommandFormMessage($command)
    {

    }


    /**
     * @param ControllerClientTask $task
     * @return int
     * @throws Exception
     */
    public function runTask(ControllerClientTask $task)
    {
        $pId = $this->runProcess($task->getCommand());
        $this->logger->alert('Pid:'.$pId);
        if (!$pId) {
            throw new Exception(sprintf('Can\'t run task %s', $task->getName()));
        }
        $task->setRunCount($task->getRunCount() + 1);
        sleep(2);
        $task->connect();
        $task->setPid($pId);
        return $pId;
    }

    /**
     * @param $task
     */
    public function clearTask(ControllerClientTask $task)
    {
        // Free memory for socket
        $task->closeConnection();
        $task->setPid(0);
    }

    /**
     * @param ControllerClientTask $task
     */
    public function terminateTask(ControllerClientTask $task)
    {
        $task->sendTerminate();
        if ($this->isProcessRunning($task->getPid())) {
            $this->killProcess($task->getPid());
        }
        $this->clearTask($task);
        $task->setFailedCount($task->getFailedCount() + 1);
    }

}
