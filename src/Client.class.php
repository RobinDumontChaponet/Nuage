<?php

require_once 'php-websockets/users.php';
// require_once 'Decorator.interface.php';


class Client extends \WebSocketUser//  implements \Decorator
{
    // Properties
    private $user;

    // constructor
    public function __construct($id, $socket) {
        parent::__construct($id, $socket);
    }

    public function enable($requestedResource) {
	    $requestedResource = explode('/', substr($requestedResource, 1));

        if(!is_array($requestedResource) || $requestedResource[0] == '') {
			echo Nuage\Core\format('RequestedRessource malformed.', 'red', true), PHP_EOL;
			var_dump($requestedResource);
			echo PHP_EOL;

		    return false;
		}

        list($id, $sessionHash) = $requestedResource;

        if(empty($id)) {
            echo Nuage\Core\format('No user-id in requestedRessource.', 'red', true), PHP_EOL;

            return false;
        }

        if(empty($sessionHash)) {
            echo Nuage\Core\format('No UUSID in requestedRessource.', 'red', true), PHP_EOL;

            return false;
        }

        $this->user = UserDAO::getById($id);

        if($this->user->getSessionHash() != $sessionHash) {
            echo Nuage\Core\format('UUSID in requestedRessource not equal to stored UUSID.', 'red', true), PHP_EOL;
            var_dump($this->user->getSessionHash(), $sessionHash);
            echo PHP_EOL;

            return false;
        }

        return true;
    }

    public function __call($name, $arguments) {
        if(method_exists($this->user, $name))
            return $this->user->$name($arguments);
    }

    public function exists() {
        return isset($this->user);
    }

    // Methods
    public function __toString() {
        return ''; // @TODO !
    }

    public function toArray() {
//		return array_intersect_key(get_object_vars($this->decorated), array_flip(array('login')));
        return [
            'id' => $this->user->getId(),
            'login' => $this->user->getLogin(),
        ];
    }
}
