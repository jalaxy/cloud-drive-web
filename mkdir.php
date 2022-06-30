<?php
session_start();
if ($_SESSION['username'] == null) {
    header('Location: /');
    exit();
}
$conn = new mysqli('localhost', 'root', 'root123', 'cloud_drive');
$res = $conn->query(sprintf(
    'select `userid` from `user` where `username` = "%s"',
    $_SESSION['username']
));
if ($res->num_rows == 0) {
    header('Location: /');
    exit();
}
header('Content-Type: application/json');
$userid = $res->fetch_assoc()['userid'];
$rootdir = __DIR__ . '/files/' . $userid;
$dir = $rootdir . $_GET['path'];
mkdir($dir, 0755);
echo json_encode(array('msg' => 'success'));
