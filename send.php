<?php
// Sends Brief builder enquiries from heenaaswani.in to the inbox below.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$TO   = 'hello@heenaaswani.in';
$FROM = 'hello@heenaaswani.in';

function out($ok, $err = '') { echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $err]); exit; }
function clean($k, $max = 500) {
    $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
    $v = str_replace(["\r", "\0"], '', $v);
    return mb_substr($v, 0, $max);
}
function oneline($s) { return trim(preg_replace('/[\r\n\t]+/', ' ', $s)); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); out(false, 'Method not allowed.'); }
if (clean('website') !== '') out(true); // spam trap: pretend success

// light rate limit: 1 enquiry per 30 seconds per visitor IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
$lock = sys_get_temp_dir() . '/hb_' . md5($ip);
if (is_file($lock) && time() - filemtime($lock) < 30) out(false, 'Please wait a few seconds before sending again.');

$name  = oneline(clean('name', 100));
$email = oneline(clean('email', 150));
$phone = oneline(clean('phone', 30));
$brand = oneline(clean('brand', 120));
$types = oneline(clean('types', 200));
$city  = oneline(clean('city', 80));
$date  = oneline(clean('date', 20));
$note  = clean('note', 3000);
$msg   = clean('message', 4000);

if ($name === '') out(false, 'Please add your name.');
if ($email === '' && $phone === '') out(false, 'Please add an email or a phone number.');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, 'That email does not look right.');
if ($phone !== '' && strlen(preg_replace('/\D/', '', $phone)) < 10) out(false, 'Please enter a full phone number.');

$subject = 'New shoot enquiry: ' . ($brand !== '' ? $brand : $name) . ($types !== '' ? " ($types)" : '');
$body  = "New enquiry from heenaaswani.in\n\n";
$body .= "Name:   $name\n";
$body .= "Brand:  " . ($brand ?: '-') . "\n";
$body .= "Email:  " . ($email ?: '-') . "\n";
$body .= "Phone:  " . ($phone ?: '-') . "\n";
$body .= "Shoot:  " . ($types ?: '-') . "\n";
$body .= "City:   " . ($city ?: '-') . "\n";
$body .= "Date:   " . ($date ?: '-') . "\n\n";
if ($note !== '') $body .= "Notes:\n$note\n\n";
$body .= "Message preview:\n$msg\n\n---\nSent " . date('Y-m-d H:i') . " from IP $ip\n";

$headers  = "From: Heena Aswani Website <$FROM>\r\n";
if ($email !== '') $headers .= 'Reply-To: ' . oneline($name) . " <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$sent = @mail($TO, $encSubject, $body, $headers, '-f' . $FROM);
if (!$sent) out(false, 'Could not send right now. Please copy the message and send it on Instagram.');
@touch($lock);
out(true);
