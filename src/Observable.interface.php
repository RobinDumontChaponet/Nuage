<?php

interface Observable
{
    public function subscribe(Observer $observer);

    public function unsubscribe(Observer $observer);

// 	function notify();
}
