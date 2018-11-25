<?php

/**
 * default module
 * use tFPDF with zzwrap error logging
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (file_exists($zz_setting['lib'].'/tfpdf/src/tFPDF.php')) {
	require_once $zz_setting['lib'].'/tfpdf/src/tFPDF.php';
} else {
	require_once $zz_setting['lib'].'/tfpdf/tFPDF.php';
}

define('FPDF_FONTPATH', $zz_setting['custom'].'/tfpdf');

class zzTFPDF extends tFPDF {
	function Error($msg) {
		// Fatal error
		wrap_error(sprintf('FPDF error: %s', $msg), E_USER_ERROR);
	}
}
