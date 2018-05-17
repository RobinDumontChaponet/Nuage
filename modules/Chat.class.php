<?php

namespace Nuage\Modules;

class Chat extends \Nuage\Core\Module
{
    const REQUEST = 'message';

    public function process($user, $input) {
        if('get' == $input->method)
            $this->put($user, [
                'content' => 'test',
            ]);
        if('post' == $input->method)
            $this->postToAllOthers($user, [
                'sender' => $user->getLogin(),
                'content' => $input->content,
            ]);
    }
}
