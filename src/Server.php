<?php

namespace Nuage\Core;

use gymadarasz\phpwebsocket\WebSocketServer;
use gymadarasz\phpwebsocket\WebSocketUserInterface;
use Nuage\Core\Observer as Module;

function format(string $content, string $colorName = 'white', bool $bold = false) {
    $colorCode = array(
        'black' => 0,
        'red' => 1,
        'green' => 2,
        'yellow' => 3,
        'blue' => 4,
        'magenta' => 5,
        'cyan' => 6,
        'white' => 7,
        'grey' => 8,
        'orange' => 214,
    );

    return "\033[38;5;".($colorCode[$colorName]).(($bold) ? ';1' : '').'m'.$content."\033[0m";
}

abstract class Server extends WebSocketServer implements Observable
{
    protected $userClass = 'Nuage\Core\Client';
    public $debug = false;
    private $shuttingDown = false;

    public $allowedHosts = ['localhost'];

    private $observers = [];

    public function __construct(string $addr, int $port, int $bufferLength = 2048)
    {
        parent::__construct($addr, $port, $bufferLength);

        declare(ticks=1);
        pcntl_signal(SIGINT, array($this, 'sig_handler'));
        pcntl_signal(SIGTERM, array($this, 'sig_handler'));
        pcntl_signal(SIGHUP, array($this, 'sig_handler'));

        register_shutdown_function(array(&$this, 'shutdown'));

        mb_internal_encoding('UTF-8');
    }

    public function subscribe(Module $observer)
    {
        $this->observers[] = $observer;
    }

    public function unsubscribe(Module $observer)
    {
        foreach($this->observers as $key => $value) {
            if ($value == $observer) {
                unset($this->observers[$key]);
            }
        }
    }

    public function post(WebSocketUserInterface $receiver, string $request, $content)
    {
        $this->sendJSON($receiver, $request, 'post', $content);
    }

    public function postToAllOthers(WebSocketUserInterface $sender, string $request, $content)
    {
        foreach ($this->users as $user)
            if ($user != $sender)
                $this->post($user, $request, $content);
    }

    public function put(WebSocketUserInterface $receiver, string $request, $content)
    {
        $this->sendJSON($receiver, $request, 'put', $content);
    }

    public function putToAllOthers(WebSocketUserInterface $sender, string $request, $content)
    {
        foreach ($this->users as $user)
            if ($user != $sender)
                $this->put($user, $request, $content);
    }

    public function patch(WebSocketUserInterface $receiver, string $request, $content)
    {
        $this->sendJSON($receiver, $request, 'patch', $content);
    }

    public function patchToAllOthers(WebSocketUserInterface $sender, string $request, $content)
    {
        foreach ($this->users as $user)
            if ($user != $sender)
                $this->patch($user, $request, $content);
    }

    public function delete(WebSocketUserInterface $receiver, string $request, $content)
    {
        $this->sendJSON($receiver, $request, 'delete', $content);
    }

    public function deleteToAllOthers(WebSocketUserInterface $sender, string $request, $content)
    {
        foreach ($this->users as $user)
            if ($user != $sender)
                $this->delete($user, $request, $content);
    }

    protected function sendJSON(WebSocketUserInterface $receiver, string $request, $method, $content)
    {
        if($this->debug) {
            $this->stdout(format($receiver->getLogin(), 'magenta', true).format(' <- ', 'yellow').format($method, 'grey').' "'.$request.'"  ');
            print_r($content);
            echo PHP_EOL;
        }

        parent::send(
            $receiver,
            json_encode(
                array(
                    'request' => $request,
                    'method' => $method,
                    'time' => time(),
                    'content' => $content,
                )
            )
        );
    }

    protected function process(WebSocketUserInterface $user, $input)
    {
        $input = json_decode($input);

        if($this->debug) {
            $this->stdout(format($user->getLogin(), 'magenta', true).format(' -> ', 'blue').format($input->method, 'grey').' "'.$input->request.'"  ');
            print_r(@$input->content);
            echo PHP_EOL;
        }

        foreach($this->observers as $observer)
            $observer->receive($user, $input);
    }

    public function getUsers() {
        return $this->users;
    }

    protected function connected(WebSocketUserInterface $user)
    {
        if($user->enable($user->requestedResource)) {
            if($this->debug)
                $this->stdout('[ '.format($user->getLogin(), 'magenta').' '.format('connected.', 'green').' ]'.PHP_EOL);

            foreach($this->observers as $observer)
                $observer->connected($user);
        } else
            $this->disconnect($user->socket);
    }

    protected function closed(WebSocketUserInterface $user)
    {
        if($this->debug)
            $this->stdout('[ '.format('Client', 'magenta').' '.format('disconnected.', 'grey').' ]'.PHP_EOL);

        if(!$this->shuttingDown && $user->exists())
            foreach($this->observers as $observer)
                $observer->closed($user);
    }

    protected function checkHost($hostName)
    {
        if(in_array($hostName, $this->allowedHosts))
            return true;
        else {
            $this->stderr(format('Disallowed host "'.$hostName.'". Disconnecting...', 'red', true));

            return false;
        }
    }

    protected function shutdown()
    {
        $this->shuttingDown = true;

        foreach ($this->users as $key => $user) {
            $this->disconnect($user->socket);

            $user = null;
            unset($this->users[$key]);
        }

        foreach($this->observers as $observer)
            $observer->shutdown();

        exit();
    }

    public function sig_handler($sig)
    {
//		$this->stdout('sig caught');

        switch($sig) {
            case SIGINT: case SIGTERM:
//			$this->shutdown();

            exit();
        }
    }

/*
    public function __destruct() {
        $this->stdout('Server closed.');

        foreach($this->observers as $key => $observer) {
            $observer = null;
            unset($this->observers[$key]);
        }
    }
*/
}
