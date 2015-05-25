<?php
namespace SocketDaemon\ClientTask;

use SocketDaemon\ClientTask\SocketClient;
use Exception;

class ControllerClientTask
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var SocketClient
     */
    protected $socketClient;

    /**
     * @var string
     */
    protected $lastStatus;
    /**
     * @var int
     */
    protected $runCount = 0;

    /**
     * @var int
     */
    protected $failedCount = 0;

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param $command
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param $pid
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return SocketClient
     */
    public function getSocketClient()
    {
        return $this->socketClient;
    }

    /**
     * @param SocketClient $socketClient
     */
    public function setSocketClient($socketClient)
    {
        $this->socketClient = $socketClient;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }

    /**
     * @param int $failedCount
     */
    public function setFailedCount($failedCount)
    {
        $this->failedCount = $failedCount;
    }

    /**
     * @return int
     */
    public function getRunCount()
    {
        return $this->runCount;
    }

    /**
     * @param int $runCount
     */
    public function setRunCount($runCount)
    {
        $this->runCount = $runCount;
    }

    public function checkConnection()
    {
        if ($this->getSocketClient()) {
            return $this->socketClient->ping();
        }
        return false;
    }

    public function connect()
    {
        $this->socketClient = new SocketClient();
        $this->socketClient->connect($this->host, $this->port);
    }

    public function run()
    {
        $this->setRunCount($this->getRunCount()+1);
        $this->setPid(
            exec(sprintf("%s > /dev/null 2>&1 & echo $!", $this->getCommand()))
        );
        if (!$this->getPid()) {
            throw new Exception(sprintf('Can\'t run task %s', $this->getName()));
        }
        $this->connect();
    }

    /**
     * @return bool
     */
    public function isRun()
    {
        if (!$this->getPid()) {
            return false;
        }
        return $this->checkConnection();
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $this->socketClient->write('status');
        return $this->socketClient->read();
    }

    public function sendTerminate()
    {
        $this->socketClient->write('exit');
    }

    public function closeConnection()
    {
        $this->socketClient->connectionClose();
    }

    /**
     * @return string
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }

    /**
     * @param string $lastStatus
     */
    public function setLastStatus($lastStatus)
    {
        $this->lastStatus = $lastStatus;
    }


}
