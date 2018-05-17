<?php

namespace Nuage\Core;

abstract class Module implements Observer
{
    private $server;
    const MODULE_NAME = 'base_module';
    const REQUEST = 'base';

    private static function getRequest() {
        $cc = get_called_class();

        return $cc::REQUEST;
    }

    private static function getModuleName() {
        $cc = get_called_class();

        return $cc::MODULE_NAME;
    }

    public function __construct(Server $server) {
        $this->server = $server;

        $server->subscribe($this);
    }

    protected function stdout($message) {
        $this->server->stdout('Module '.$this->getModuleName().' : '.$message);
    }

    protected function stderr($message) {
        $this->server->stderr('Module '.$this->getModuleName().format(' : ', 'red', true).$message);
    }

    protected function post($receiver, $content) {
        $this->server->post($receiver, $this->getRequest(), $content);
    }

    protected function put($receiver, $content) {
        $this->server->put($receiver, $this->getRequest(), $content);
    }

    protected function patch($receiver, $content) {
        $this->server->patch($receiver, $this->getRequest(), $content);
    }

    protected function delete($receiver, $content) {
        $this->server->delete($receiver, $this->getRequest(), $content);
    }

    protected function postToAllOthers($sender, $content) {
        $this->server->postToAllOthers($sender, $this->getRequest(), $content);
    }

    protected function putToAllOthers($sender, $content) {
        $this->server->putToAllOthers($sender, $this->getRequest(), $content);
    }

    protected function patchToAllOthers($sender, $content) {
        $this->server->patchToAllOthers($sender, $this->getRequest(), $content);
    }

    protected function deleteToAllOthers($sender, $content) {
        $this->server->deleteToAllOthers($sender, $this->getRequest(), $content);
    }

    public function receive($user, $input) {
        if($input->request == $this->getRequest())
            $this->process($user, $input);
    }

    public function connected($client) {}

    public function closed($client) {}

    protected function getUsers() {
        return $this->server->getUsers();
    }

    public function shutdown() {}
}
