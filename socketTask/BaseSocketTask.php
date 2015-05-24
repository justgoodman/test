<?php
namespace SocketTask;

use Exception;

class BaseSocketTask
{
    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $greetingText;

    /**
     * @var resource
     */
    protected $conn;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array structure 'task_name' => command
     */
    protected $tasks;

    /**
     * @param string $host
     * @param int $port
     * @param string $greetingText
     * @param int $backlog
     * @throws Exception
     */
    public function __construct($host, $port, $greetingText, $backlog = 5)
    {
        $this->greetingText = $greetingText;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            throw new Exception(
                sprintf(
                    'Can\'t create socket message: "%s"',
                    socket_strerror(socket_last_error())
                )
            );
        }
        if (!socket_bind($this->socket, $host, $port)) {
            throw new Exception(
                sprintf(
                    'Can\'t bind socket host: "%s", port: "%s", message: "%s"',
                    $host,
                    $port,
                    socket_strerror(socket_last_error($this->socket))
                )
            );
        }

        if (!socket_listen($this->socket, $backlog)) {
            throw new Exception(
                sprintf('Can\'t listen socket message: "%s"', socket_strerror(socket_last_error($this->socket)))
            );
        }
        $this->id = $this->getId();
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        do {
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
        } while (true);
    }

    /**
     * simple Logging can be override
     * @param $message
     */
    protected function logError($message)
    {
        echo 'Error: ' . $message;
    }

    protected function connect()
    {
        $this->conn = socket_accept($this->socket);
        if (!($this->conn)) {
            throw new Exception(
                sprintf('Can\'t accept connection message: "%s"', socket_strerror(socket_last_error($this->socket)))
            );
        }
    }

    protected function closeConnection()
    {
        socket_close($this->conn);
    }

    /**
     * @param string $text
     */
    protected function write($text)
    {
        socket_write($this->conn, $text, strlen($text));
    }

    /**
     * @throws Exception
     * @return string
     */
    protected function read()
    {
        $message = socket_read($this->conn, 2048, PHP_NORMAL_READ);
        if (!$message) {
            throw new Exception(
                sprintf('Can\'t read message: "%s"', socket_strerror(socket_last_error($this->conn)))
            );
        }
        return trim($message);
    }
    /**
     * @throws Exception
     */
    protected function processCommand($command)
    {
        switch ($command) {
            case 'exit':
                return false;
            default:
                $this->write('Command :' . $command . '!');
        }
        return true;
    }
}
