<?php

class vote extends Thread {
	public $running = false;

	function __construct(){
		$this->running = true;
	}

	function run(){
		while($this->running){
			echo "thread is ok\r\n";
			sleep(mt_rand(1,5));
		}
	}
}


$vt = new vote();

$vt->start();

while(true){
	echo "Other program part :)\r\n";
	sleep(mt_rand(1,5));
}

?>
