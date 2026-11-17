<?php
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli('localhost','root','','local_infra_db');
if ($db->connect_error) { http_response_code(500); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Database connection failed']); exit; }
$db->set_charset('utf8mb4');