<?php


namespace SocketTask;

use SocketTask\BaseSocketTask;
use Exception;
use Model\Task;

class TaskController extends BaseSocketTask
{
    /**
     * @var array structure 'task_name' => command
     */
    protected $tasks;

    private $socket;

    private $socketTasks;

    /**
     * @param string $host
     * @param int $port
     * @param string $greetingText
     * @param int $backlog
     * @throws Exception
     */
    public function __construct($host, $port, $tasks, $greetingText, $backlog = 5)
    {
        parent::__construct($host, $port, $greetingText, $backlog);
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
                $this->logError($e->getMessage());
                // Нужно сделать закрытие коннеткта если ошибка была в $this->processCommand($command))
            }
        } while (true);
    }


    public function terminateTask($id)
    {
        exec(sprintf("kill %d", $id));
    }

    /**
     * public function taskInfo()
     * {
     * ps -eo pcpu,pmem
     * }
     */
    public function getId()
    {
        return exec('echo $$');
    }

    /**
     * @param $id
     * @return bool
     */
    function isTaskRunning($id)
    {
        $result = shell_exec(sprintf("ps %d", $id));
        if (count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
        return false;
    }


    /**
     * @param $command
     * @return bool
     * @throws Exception
     */
    protected function processCommand($command)
    {
        $taskName = $this->getTaskNameFromMessage($command);
        $command = $this->getCommandFormMessage($command);
        if (!isset($this->tasks[$taskName])) {
            return true;
        }
        /**
         * @var Task
         */
        $task = $this->tasks[$taskName];
        switch ($command) {
            case 'task start':
                $this->runTask($task);
                return false;
            case 'task stop':
                $this->sentCommandTask($task, 'terminrate');
                break;
            case 'task info':
                $status = $this->sentCommandTask($task, 'status');
                $task->setStatus($status);
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


    public function sentCommandTask($task, $command)
    {
        if (!socket_connect($this->socketTasks, '0.0.0.0', $task->getPort())) {
            throw new Exception(
                sprintf(
                    'Can\'t connect task %s message %s',
                    $task->getName(),
                    socket_strerror(socket_last_error($this->socket))
                )
            );
        }
        $this->socket_write($this->socketTasks, $command, strlen($command));
        try {
            $answer = $this->read();
        } catch (Exception $e) {
            $task->setStatus('Exit Error');
            $task->setPid(0);
        }
        socket_close($this->socketTasks);
        return $answer;
    }

    /**
     * @param $cmd
     * @param $outputfile
     * @param $pidfile
     */
    public function runTask(Task $task)
    {
        $task->setPid(
            exec(sprintf("%s > /dev/null 2>&1 & echo $!", $task->getCommand()))
        );
        if (!$task->getPid()) {
            throw new Exception(sprintf('Can\'t run task %s', $task->getName()));
        }
        $status = $this->sentCommandTask($task, 'status');
        $task->setStatus($status);
    }

}
