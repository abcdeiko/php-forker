<?php

class TProcess {
}	


$n = 10000;
$count = 0;
//$a = rand();
//$start = microtime(true);

//echo 'begin'.$a.' ';
for($i=0;$i<$n;$i++)
{
	$x = (float)(mt_rand() / mt_getrandmax());
	$y = (float)(mt_rand() / mt_getrandmax());

	if($x*$x+$y*$y < 1.0)
	{
		$count++;	
	}
}

$p = 4*$count/$n;



echo $p.' ';

//echo 'end'.$a.' ' ;
//$end = microtime(true);

//echo $end-$start. ' ';
?>
