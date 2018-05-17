<?php

namespace Nuage\Modules;

class UserList extends \Nuage\Core\Module
{
    const REQUEST = 'user-list';

    public function process($user, $input) {
        if('get' == $input->method)
            $this->put($user, array_values(array_map(function ($item) {
                return $item->toArray();
            }, $this->getUsers())));
        if('post' == $input->method)
            $this->postToAllOthers($user, [
                'sender' => $user->getLogin(),
                'content' => $input->content,
            ]);
    }

    public function connected($client) {
        $this->postToAllOthers($client, array($client->toArray()));
    }

    public function closed($client) {
        $this->deleteToAllOthers($client, array($client->toArray()));
    }
}
