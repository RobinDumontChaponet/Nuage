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
	        $this->stderr(Nuage\Core\format('RequestedRessource malformed.', 'red', true));
		    var_dump($requestedResource);

		    return false;
		}

        list($login, $sessionId) = $requestedResource;

        if(empty($login)) {
            $this->stderr(Nuage\Core\format('No user-login in requestedRessource.', 'red', true));

            return false;
        }

        if(empty($sessionId)) {
            $this->stderr(Nuage\Core\format('No UUSID in requestedRessource.', 'red', true));

            return false;
        }

        $this->user = UserDAO::getByLogin($login);

        if($this->user->getSessionId() != $sessionId) {
            $this->stderr(Nuage\Core\format('UUSID in requestedRessource not equal to stored UUSID.', 'red', true));

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
            'login' => $this->user->getLogin(),
        ];
    }
}
