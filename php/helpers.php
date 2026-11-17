<?php
ini_set('display_errors','0');
session_start();
header('Content-Type: application/json');
require __DIR__.'/db_connect.php';
function out($d,$code=200){ http_response_code($code); echo json_encode($d); exit; }
function need_staff(){ if (empty($_SESSION['staff_id'])) out(['success'=>false,'message'=>'Login required'],401); }
function clean($v){ return trim((string)$v); }