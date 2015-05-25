<?php
namespace SocketDaemon\ServerTask;

use SocketDaemon\ServerTask\BaseServerTask;
use Exception;


class SimpleServerTask extends BaseServerTask
{
    /**
     * @throws Exception
     */
    public function run()
    {
        $this->connect();
        $this->write($this->greetingText . "\n");
        try {
            do {
                $command = $this->read();
                $this->logger->debug(sprintf('Read message: "%s"', $command));
            } while ($this->processCommand($command));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $this->closeConnection();
    }

    /**
     * @param $command
     * @return bool (need close process)
     */
    protected function processCommand($command)
    {
        if (!$command) {
            return true;
        }
        $this->logger->debug(sprintf('Process message: "%s"', $command));
        switch ($command) {
            case 'status':
                $status = $this->getStatus();
                $this->write($this->getStatus() . "\n");
                if ($status == 'Success') {
                    return true;
                }
                break;
            case 'terminate':
                $this->write('Exit' . "\n");
                return false;
            case 'ping':
                $this->write('pong' . "\n");
                return true;
            default:
                $this->write('errorInput' . "\n");
                break;
        }
        $this->doSomeIterationOperation();
        return true;
    }

    /**
     * In this place we do some operations
     */
    public function doSomeIterationOperation()
    {

    }

    /**
     * @return string
     */
    function getStatus()
    {
        switch (rand(0, 4)) {
            case 1:
                return 'InProcess';
            case 2:
                return 'ErrorDone';
            case 3:
                return 'TaskDone';
        }
    }
}
