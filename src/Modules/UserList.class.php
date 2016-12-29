<?php

namespace Nuage\Modules;

class UserList extends \Nuage\Lib\Module
{
    const REQUEST = 'user-list';

    public function process($user, $input) {
        if($input->method == 'get')
            $this->put($user, array_values(array_map(function ($item) {
                return $item->toArray();
            }, $this->getUsers())));
        if($input->method == 'post')
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
