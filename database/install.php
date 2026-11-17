<?php
$c = new mysqli('localhost','root','');
if ($c->connect_error) die("MySQL is not running: ".$c->connect_error."\n");
$c->multi_query(file_get_contents(__DIR__.'/schema.sql'));
do { if ($r = $c->store_result()) $r->free(); } while ($c->more_results() && $c->next_result());
if ($c->error) die("Schema error: ".$c->error."\n");
$c->select_db('local_infra_db');
$h = password_hash('Officer@123', PASSWORD_DEFAULT);
$s = $c->prepare("INSERT INTO staff (full_name,username,password_hash,email) VALUES ('Ward Officer','officer1',?,'officer@ward.local')");
$s->bind_param('s',$h); $s->execute();
echo "Database ready. Staff login: officer1 / Officer@123\n";