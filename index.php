<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $conn = new mysqli('localhost', 'root', 'root123', 'cloud_drive');
    if ($conn->connect_error)
        die('Fail to connect to database: ' . $conn->connect_error);
    if ($data['code'] == null) {
        $res = $conn->query(sprintf(
            'select `userid`, `passwd` from `user` where `username` = "%s"',
            $data['username']
        ));
        if ($res->num_rows == 0)
            echo json_encode(array('mE' => 'User not found', 'mC' => '用户不存在'));
        else {
            $row = $res->fetch_assoc();
            if ($row['passwd'] == $data['passwd']) {
                echo json_encode(array('mE' => 'Success', 'mC' => '成功'));
                $_SESSION['username'] = $data['username'];
            } else
                echo json_encode(array('mE' => 'Wrong password', 'mC' => '密码错误'));
        }
    } else {
        $res = $conn->query(sprintf(
            'select username from `user` where `username` = "%s"',
            $data['username']
        ));
        if ($res->num_rows != 0)
            echo json_encode(array('mE' => 'User exists', 'mC' => '用户已存在'));
        else {
            $res = $conn->query(sprintf(
                'select count(*), maxnum from `invcode` natural join `user` where `invcode` = "%s"',
                $data['code']
            ));
            $row = $res->fetch_assoc();
            if ($row['count(*)'] < $row['maxnum']) {
                $conn->query(sprintf(
                    'insert into `user`(`username`, `passwd`, `invcode`) values("%s", "%s", "%s")',
                    $data['username'],
                    $data['passwd'],
                    $data['code']
                ));
                $userid = $conn->query('select `userid` from `last`')->fetch_assoc()['userid'];
                mkdir(__DIR__ . '/files/' . $userid);
                echo json_encode(array('mE' => 'Registration success', 'mC' => '注册成功'));
            } else
                echo json_encode(array('mE' => 'Invalid code', 'mC' => '邀请码无效'));
        }
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <title>欢迎</title>
    <link rel="stylesheet" href="/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>

<body>
    <div align="center">
        <div id="index-form" style="width: 500px">
            <div style="margin: 30px">
                <h1>欢迎！立即登录。</h1>
                <h1>Welcome! Login now.</h1>
            </div>
            <hr style="width: 400px">
            <form action="javascript:" method="post" style="margin: 20px">
                <table>
                    <tr height="50">
                        <td><label for="username">用户名</label></td>
                        <td style="text-align: right"><label for="username">Username</label></td>
                        <td width="220" style="text-align: right"><input id="username" name="username" type="text" placeholder="用户名  username"></td>
                    </tr>
                    <tr height="50">
                        <td><label for="password">密码</label></td>
                        <td style="text-align: right"><label for="username">Password</label></td>
                        <td style="text-align: right"><input id="passwd" name="passwd" type="password" placeholder="密码 password"></td>
                    </tr>
                    <tr height="50">
                        <td><label for="password">邀请码</label></td>
                        <td style="text-align: right"><label for="username">Code</label></td>
                        <td style="text-align: right"><input id="code" name="code" type="password" placeholder="注册时填写  fill at registration"></td>
                    </tr>
                    <tr style="padding: 0; margin: 0">
                        <td colspan="3" style="padding: 0; margin: 0">
                            <table style="width: 100%; padding: 0; margin: 0">
                                <tr style="padding: 0; margin: 0">
                                    <td id="msgC" style="text-align: left; padding: 0; margin: 0"></td>
                                    <td id="msgE" style="text-align: right; padding: 0; margin: 0"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr height="60">
                        <td colspan="3" style="text-align: center"><button style="margin: 0">进入 Enter</button></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <hr style="margin-top: 50px">
    <div align="center">
        <h1>使用说明 How to use</h1>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', (event) => {
            const formData = new FormData(event.target);
            const data = {};
            formData.forEach((value, key) => (data[key] = value));
            data['passwd'] = CryptoJS.SHA256(data['passwd']).toString(CryptoJS.enc.Hex);
            fetch('/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(msg => {
                    if (msg['mE'] == 'Success')
                        window.location.href = `/home`;
                    else {
                        document.querySelector('#msgC').innerHTML = msg['mC'];
                        document.querySelector('#msgE').innerHTML = msg['mE'];
                        var invalidInput;
                        if (msg['mE'] == 'User not found' || msg['mE'] == 'User exists')
                            invalidInput = document.querySelector('#username');
                        else if (msg['mE'] == 'Wrong password')
                            invalidInput = document.querySelector('#passwd');
                        else if (msg['mE'] == 'Invalid code')
                            invalidInput = document.querySelector('#code');
                        else if (msg['mE'] == 'Registration success')
                            document.querySelector("#code").value = '';
                        invalidInput.classList.add('invalid-input');
                        invalidInput.addEventListener('input', (ev) => {
                            ev.target.classList.remove('invalid-input');
                        });
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        }, false);
    </script>
</body>

</html>