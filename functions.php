<?php
function error($msg) {
	echo $msg."\n";die();
}

function debug($var) {
	if(DEBUG) {
		if(is_string($var)) {
			echo $var."\n";
		} else {
			print_r($var);
		}
	}
}