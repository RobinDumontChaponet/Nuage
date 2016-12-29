<?php

namespace Nuage\Modules;

class Chat extends \Nuage\Lib\Module {
	const REQUEST = 'message';

	public function process($user, $input) {
		if($input->method == 'get')
			$this->put($user, [
				'content' => 'test'
			]);
		if($input->method == 'post')
			$this->postToAllOthers($user, [
				'sender' => $user->getLogin(),
				'content' => $input->content
			]);
	}
}
