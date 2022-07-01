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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hashstr = hash_file('sha512', $_FILES['upfile']['tmp_name']);
    $realpath = realpath(__DIR__) . '/files/r/' . $hashstr;
    $sympath = $rootdir . $_GET['path'] . '/' . $_FILES['upfile']['name'];
    move_uploaded_file($_FILES['upfile']['tmp_name'], $realpath);
    symlink($realpath, $sympath);
    echo json_encode(array('msg' => 'success'));
} else {
    $realpath = realpath(__DIR__) . '/files/r/' . $_GET['hash'];
    $sympath = $rootdir . $_GET['path'] . '/' . $_GET['name'];
    if (file_exists($realpath)) {
        echo json_encode(array('msg' => 'success'));
        symlink($realpath, $sympath);
    } else echo json_encode(array('msg' => 'pending'));
}
