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
$files = array_diff(scandir($dir), array('.', '..'));
foreach ($files as $f) {
    $cur = new stdClass;
    $cur->name = $f;
    $cur->type = is_dir($dir . '/' . $f) ? 'directory' : 'regular';
    $cur->size = filesize($dir . '/' . $f);
    $ret[] = $cur;
}
echo json_encode($ret);
