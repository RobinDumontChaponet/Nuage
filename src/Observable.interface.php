<?php

interface Observable {
	function subscribe(Observer $observer);
	function unsubscribe(Observer $observer);
// 	function notify();
}