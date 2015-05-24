<?php
namespace TestSocket;

use SocketTask\BaseSocketTask;
use Exception;

require_once('socketTask\BaseSocketSocket.php');

class SimpleSocketTask extends BaseSocketTask  {
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
            } while ($this->processCommand($command));
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
        $this->closeConnection();
    }

    /**
     * @param $command
     * @return bool (need close process)
     */
    protected function processCommand($command)
    {
        switch ($command) {
            case 'status':
                $status = $this->getStatus();
                $this->write($this->getStatus());
                if ($status == 'Success') {
                    return true;
                }
                break;
            case 'terminate':
                $this->write('Exit');
                return false;
            default:
                $this->write('errorInput');
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
        switch (rand(1,3)) {
            case 1:
                return 'Proccess OK';
            case 2:
                return 'Error';
            case 3:
                return 'Success';
        }
    }
}
