<?php
require_once dirname(__FILE__).'/vendor/autoload.php';
session_start();

const STATUS_EMPTY  = 1;
const STATUS_LIVE   = 2;
const STATUS_DEATH  = 3;
const STATUS_BURNED = 4;

function init() {

	$cells = array();
	foreach (range(0, 50) as $row_num) {
		$tmp = array();
		foreach (range(0, 60) as $col_num) {
			$tmp[] = mt_rand(0, 1) ? STATUS_LIVE : STATUS_EMPTY;
		}
		$cells[] = $tmp;
	}

	return $cells;
}

function forget($cells) {
	foreach($cells as $row_num => $cols) {
		foreach($cols as $col_num => $col) {
			if ($cells[$row_num][$col_num] != STATUS_LIVE) {
				$cells[$row_num][$col_num] = STATUS_EMPTY;
			}
		}
	}
	return $cells;
}

function burn($cells, $fire_row_num, $fire_col_num) {

	if (!is_numeric($fire_row_num) || !is_numeric($fire_col_num)) {
		return $cells;
	}

	foreach(range($fire_row_num - 1, $fire_row_num + 1) as $surrounding_row_num) {
		if ($surrounding_row_num < 0 || $surrounding_row_num >= count($cells)) continue;

		foreach(range($fire_col_num - 1, $fire_col_num + 1) as $surrounding_col_num) {
			if ($surrounding_col_num < 0 || $surrounding_col_num >= count($cells[$surrounding_row_num])) continue;

			// 野焼きが出来るのは上下左右セルのみ
			if ($surrounding_row_num != $fire_row_num && $surrounding_col_num != $fire_col_num) {
				continue;
			}

			$cells[$surrounding_row_num][$surrounding_col_num] = STATUS_BURNED;
		}
	}

	return $cells;
}

function pass($cells) {

	foreach($cells as $row_num => $cols) {
		foreach($cols as $col_num => $col) {
			if ($cells[$row_num][$col_num] == STATUS_LIVE) {
				$cells[$row_num][$col_num] = get_next_status($cells, $row_num, $col_num);
			}
		}
	}

	return $cells;
}

function get_next_status($cells, $row_num, $col_num) {

	$partners = 0;
	foreach(range($row_num - 1, $row_num + 1) as $surrounding_row_num) {
		if ($surrounding_row_num < 0 || $surrounding_row_num >= count($cells)) continue;

		foreach(range($col_num - 1, $col_num + 1) as $surrounding_col_num) {
			if ($surrounding_col_num < 0 || $surrounding_col_num >= count($cells[$surrounding_row_num])) continue;

			// 同一セルは飛ばす
			if ($surrounding_row_num == $row_num && $surrounding_col_num == $col_num) continue;

			// 周囲八方に生存セルがいれば生存
			if ($cells[$surrounding_row_num][$surrounding_col_num] == STATUS_LIVE) {
				$partners++;
			}
		}
	}

	if ($partners == 2 || $partners == 3) {
		return STATUS_LIVE;
	}

	return STATUS_DEATH;
}


$cells = array();

if ($_GET['reset']) {
	$cells = init();
} else {
	$cells = $_SESSION['cells'];

	// 全ては忘却される
	$cells = forget($cells);

	// 指定された上下左右セルを焼く
	list($fire_row_num, $fire_col_num) = explode(',', $_GET['fire']);
	$cells = burn($cells, $fire_row_num, $fire_col_num);

	// 時は経つ
	$cells = pass($cells);
}

$_SESSION['cells'] = $cells;

$loader = new Twig_Loader_Filesystem(dirname(__FILE__));
$twig = new Twig_Environment($loader);
$template = $twig->loadTemplate('index.twig');
echo $template->render(array('cells' => $cells));

