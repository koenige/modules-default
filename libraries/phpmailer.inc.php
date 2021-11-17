<?php

/**
 * default module
 * Use 'phpmailer'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


// remind that use is only possible globally, so add these prefixes

//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\SMTP;
//use PHPMailer\PHPMailer\Exception;

require_once $zz_setting['lib'].'/phpmailer/src/Exception.php';
require_once $zz_setting['lib'].'/phpmailer/src/PHPMailer.php';
require_once $zz_setting['lib'].'/phpmailer/src/SMTP.php';
