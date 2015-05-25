<?php


namespace SocketDaemon\ClientTask;

use Exception;


class SocketClient
{

    protected  $socket;

    public function __construct($socket = null)
    {
        if (!$socket) {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        }
        $this->socket = $socket;

    }

    public function connect($host, $port)
    {
        if (!socket_connect($this->socket, $host, $port)) {
            throw new Exception(
                sprintf(
                    'Can\'t connect message %s',
                    socket_strerror(socket_last_error($this->socket))
                )
            );
        }
    }

    /**
     * @return boolean
     */
    public function ping()
    {
        $this->write('ping');
        return ($this->read() === 'pong');
    }

    public function read()
    {
        return socket_read($this->socket, 2048);
    }

    public function write($text)
    {
        socket_write($this->socket, $text, strlen($text));
    }

    public function connectionClose()
    {
        socket_close($this->socket);
    }


}
