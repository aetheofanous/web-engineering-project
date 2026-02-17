<?php
header("Content-Type: application/json");

$data = [
    "message" => "API working",
    "status" => "success"
];

echo json_encode($data);
