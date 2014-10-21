<?php

define("ROOT",dirname(__FILE__));

class ProcessManager {
	const RUN_COMMAND = "php";
	const TASK_FILENAME = "pi.php";
	const POOL_COUNT = 10;
	const FEOF_TIMEOUT = 500;

	private $pool = array();
	private $all_pipes = array();
	private $read_pipes = array();

	private function CheckProcess(){
		$running_process = 0;

		foreach($this->pool as $index => $proc){
			if($this->IsRunning($proc)){
				$running_process++;
			}else{
				$this->StopProccess($index);
			}
		}

		return $running_process;
	}

	private function StopProccess($index){
		foreach($this->all_pipes[$index] as $cur){
			fclose($cur);
		}

		proc_close($this->pool[$index]);

		unset($this->pool[$index]);
		unset($this->all_pipes[$index]);
		unset($this->read_pipes[$index]);
	}

	private	function IsRunning($proc){
		if(!is_resource($proc)){
			return false;
		}

		$status = proc_get_status($proc);

		return $status['running'];
	}

	public function Main(){
		$used_pool_count = $this->CheckProcess();

		if((self::POOL_COUNT - $used_pool_count) <= 0){
			throw new Exception("No free slots in the pool");	
		}

		if(!function_exists("proc_open")){
			throw new Exception("Undefined function proc_open");	
		}

		$descspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'a')
		);

		$this->proccess = array();
		$this->all_pipes = array();
		$this->read_pipes = array();

// run if(file_exist

		$cmd = self::RUN_COMMAND . " " . ROOT . "/" . self::TASK_FILENAME;

		// performance ????
		// stream_set_blocking
		
		for($i=0; $i<self::POOL_COUNT; $i++){
			// if(function_exist("proc_open"))
			$handle = proc_open($cmd, $descspec, $pipes);

			if(!is_resource($handle)){
				$this->__destruct();
				throw new Exception("Can't open new proccess");
				// что делать с уже открытыми процессами???				
		       	}

			$this->process[] = $handle;

			$this->all_pipes[] = $pipes;
			$this->read_pipes[] = $pipes[1];
		}

		$w = NULL;
		$e = NULL;
		
// начинаем слушать, что же нам приходит
		while(count($this->read_pipes)){
			//$stopped = true;
			$read = $this->read_pipes;

			if(stream_select($read, $w, $e, NULL) != false){		
				foreach($read as $r){
					$id = array_search($r, $this->read_pipes);
					$result = stream_get_contents($this->read_pipes[$id]);

					if($result != false){
						echo $result."<br/>";
					}

					if($this->safe_feof($r, $start) && (microtime(true) - $start) < self::FEOF_TIMEOUT){
						unset($this->read_pipes[$id]);
					}

				}
			}
		}
		// если пришел feof в потоке, то его можно убрать
		// если процессы уже завершились, но 
		// они послали в поток инфу, которую мы не прочитали
	}

	public function __destruct(){
		// wait for pid
		foreach($this->process as $index=>$handle){
			while($this->IsRunning($handle)){
			}

			fclose($this->all_pipes[$index][0]);
			fclose($this->all_pipes[$index][1]);
			fclose($this->all_pipes[$index][2]);
			unset($this->all_pipes[$index]);

			proc_close($handle);
			unset($this->process[$index]);
		}
	}

	//**
	//** safe feof
	private function safe_feof($fp, &$start){
		$start = microtime(true);

		return feof($fp);
	}


	protected function sendMessage($index, $Message){
		if(!$this->IsRunning($this->pool[$index])){
			// нужно ли исключение????
			return false;
		}
		// try to use json_encode

		$Message = json_encode($Message);


		fwrite($this->all_pipes[$index][0], $Message);
	}

	protected function sendBroadcastMessage($Message){
		foreach($this->pool as $index => $value){
			$this->sendMessage($index, $Message);
		}
	}

	private function isJson($Input){
		json_decode($Input);

		return (json_last_error() == JSON_ERROR_NONE);
	}

}


$Daemon = new ProcessManager();
$Daemon->Main();


?>
