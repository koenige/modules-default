<?php

/**
 * default module
 * use tFPDF with zzwrap error logging
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (file_exists($zz_setting['lib'].'/tfpdf/src/tFPDF.php'))
	require_once $zz_setting['lib'].'/tfpdf/src/tFPDF.php';
elseif (file_exists($zz_setting['lib'].'/tfpdf/src/tfpdf.php'))
	require_once $zz_setting['lib'].'/tfpdf/src/tfpdf.php';
elseif (file_exists($zz_setting['lib'].'/tfpdf/tFPDF.php'))
	require_once $zz_setting['lib'].'/tfpdf/tFPDF.php';
else
	require_once $zz_setting['lib'].'/tfpdf/tfpdf.php';

define('FPDF_FONTPATH', $zz_setting['custom'].'/tfpdf');
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT);

class zzTFPDF extends tFPDF {
	function Error($msg) {
		// Fatal error
		wrap_error(sprintf('FPDF error: %s', $msg), E_USER_ERROR);
	}
}
