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
$userid = $res->fetch_assoc()['userid'];
?>
<!DOCTYPE html>
<html language='zh'>

<head>
    <title>文件</title>
    <link rel="stylesheet" href="/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>

<body style="padding: 0; margin: 0;">
    <div id="rmenu" class='hide'>
        <button onclick="download();" class="rbtn" id="download">下载 Download</button>
        <button onclick="copy();" class="rbtn" id="copy">复制 Copy</button>
        <button onclick="move();" class="rbtn" id="move">移动 Move</button>
        <button onclick="rename();" class="rbtn" id="rename">重命名 Rename</button>
        <button onclick="remove();" class="rbtn" id="remove">删除 Remove</button>
    </div>
    <table id="navibar">
        <tr>
            <td style="width: 0;">
                <button onclick="upview()" id="upview">向上</button>
            </td>
            <td style="width: 0;">
                <button onclick="getfiles()" id="upview">刷新</button>
            </td>
            <td style="width: 0;">
                <button onclick="paste()" id="paste" style="display: none">粘贴</button>
            </td>
            <td>
                <center style="color: white;" id="caption"></center>
            </td>
            <td style="width: 0;">
                <span id="progress" style="display: none; color: white;">已上传 <span></span>%</span>
            </td>
            <td style="width: 0;">
                <button onclick="upload()" id="upload">上传</button>
            </td>
            <td style="width: 0;">
                <button onclick="mkdir()" id="newfolder">新建文件夹</button>
            </td>
        </tr>
    </table>
    <div id="files"></div>
    <input type="file" name="upfile" id="upfile" style="display: none;">
    <script>
        var curdir = '';
        var captiondef;
        var hours = (new Date()).getHours();
        var rtarget = null;
        var selpath, selname;
        var cppath = null;
        var mvpath = null;
        const elementupfile = document.querySelector('#upfile');

        async function getfiles() {
            var filelist;
            var res = await fetch('/getfiles.php?' + new URLSearchParams({
                'path': curdir
            }));
            var files = await res.json();
            if (files)
                files.sort((a, b) => {
                    if (a.type == b.type)
                        return a.name < b.name ? -1 : 1;
                    return a.type < b.type ? -1 : 1;
                });
            var elementfiles = document.querySelector('#files');
            elementfiles.innerHTML = '';
            if (curdir == '')
                document.querySelector('#caption').innerHTML = captiondef;
            else
                document.querySelector('#caption').innerHTML = curdir.substr(1).split('/').join(' > ');
            if (!files) return;
            for (var i = 0; i < files.length; i++) {
                var svg = document.createElement('div');
                svg.appendChild(files[i].type == 'regular' ? newfilesvg() : newfoldersvg());
                svg.setAttribute('style', 'width: 100px; height: 100px;');
                var txt = document.createElement('div');
                txt.innerHTML = files[i].name;
                txt.setAttribute('style', 'text-align: center;');
                txt.classList.add('filename');
                txt.style.width = svg.style.width;
                var btn = document.createElement('div');
                btn.setAttribute('style', 'float: left');
                btn.classList.add('filebtn');
                btn.id = String(i);
                btn.appendChild(svg);
                btn.appendChild(txt);
                elementfiles.appendChild(btn);
                btn.onclick = (event) => {
                    var idx = Number(event.currentTarget.id);
                    if (files[idx].type == 'regular')
                        window.location = '/files/' + '<?php echo $userid; ?>' + curdir + '/' + files[idx].name;
                    else {
                        curdir += '/' + files[idx].name;
                        getfiles(curdir);
                    }
                };
                btn.oncontextmenu = (event) => {
                    rtarget = event.currentTarget;
                    var rmenu = document.querySelector('#rmenu');
                    rmenu.style.left = event.clientX + 'px';
                    rmenu.style.top = event.clientY + 'px';
                    rmenu.className = 'show';
                    selname = files[Number(rtarget.id)].name
                    selpath = curdir + '/' + selname;
                    return false;
                };
            };
        }

        function upview() {
            var curdirsplit = curdir.split('/');
            if (curdirsplit.length > 1)
                curdir = curdirsplit.slice(0, curdirsplit.length - 1).join('/');
            getfiles();
        }

        function mkdir() {
            var name = prompt("请输入文件夹名称");
            if (name)
                fetch('/mkdir.php?' + new URLSearchParams({
                    'path': curdir + '/' + name
                })).then((res) => res.json())
                .then((msg) => getfiles());
        }

        function upload() {
            elementupfile.click();
        }

        function paste() {
            fetch((cppath ? '/copy.php?' : '/move.php?') + new URLSearchParams({
                    'path': cppath ? cppath : mvpath,
                    'newpath': curdir + '/' + (cppath ? cpname : mvname)
                })).then((res) => res.json())
                .then((msg) => getfiles());
            cppath = mvpath = null;
            document.querySelector('#paste').style.display = 'none';
        }

        function download() {
            document.querySelector('#rmenu').className = 'hide';
            window.open('/files/' + '<?php echo $userid; ?>' + selpath).focus();
        }

        function copy() {
            document.querySelector('#rmenu').className = 'hide';
            mvpath = null;
            cppath = selpath;
            cpname = selname;
            document.querySelector('#paste').style.display = '';
        }

        function move() {
            document.querySelector('#rmenu').className = 'hide';
            cppath = null;
            mvpath = selpath;
            mvname = selname;
            document.querySelector('#paste').style.display = '';
        }

        function remove() {
            document.querySelector('#rmenu').className = 'hide';
            fetch('/remove.php?' + new URLSearchParams({
                    'path': selpath
                })).then((res) => res.json())
                .then((msg) => getfiles());
        }

        function rename() {
            document.querySelector('#rmenu').className = 'hide';
            var name = prompt("请输入文件夹名称");
            if (name)
                fetch('/move.php?' + new URLSearchParams({
                    'path': selpath,
                    'newpath': curdir + '/' + name
                })).then((res) => res.json())
                .then((msg) => getfiles());
        }

        function newfoldersvg() {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('version', '1');
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            svg.setAttribute('viewBox', '0 0 48 48');
            svg.setAttribute('enable-background', 'new 0 0 48 48');
            var p1 = document.createElementNS('http://www.w3.org/2000/svg', 'path'),
                p2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p1.setAttribute('fill', '#FFA000');
            p1.setAttribute('d', 'M40,12H22l-4-4H8c-2.2,0-4,1.8-4,4v8h40v-4C44,13.8,42.2,12,40,12z');
            p2.setAttribute('fill', '#FFCA28');
            p2.setAttribute('d', 'M40,12H8c-2.2,0-4,1.8-4,4v20c0,2.2,1.8,4,4,4h32c2.2,0,4-1.8,4-4V16C44,13.8,42.2,12,40,12z');
            svg.appendChild(p1);
            svg.appendChild(p2);
            return svg;
        }

        function newfilesvg() {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('version', '1');
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('enable-background', 'new 0 0 100 100');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('fill', '#000000');
            p.setAttribute('d', 'M79.4,32.3C79.4,32.3,79.4,32.2,79.4,32.3c0-0.2-0.1-0.3-0.2-0.4c0,0,0,0,0,0L57.2,10c0,0,0,0,0,0l0,0c0,0,0,0,0,0  c-0.1-0.1-0.2-0.1-0.3-0.1c0,0,0,0-0.1,0c0,0-0.1,0-0.1,0H25.1c-2.3,0-4.1,1.8-4.1,4.1v71.7c0,2.3,1.8,4.1,4.1,4.1h50.2  c2.2,0,4.1-1.8,4.1-4.1L79.4,32.3C79.4,32.3,79.4,32.3,79.4,32.3z M77.3,31.7H57.4V11.9L77.3,31.7z M78.2,85.6  c0,1.6-1.3,2.9-2.9,2.9H25.1c-1.6,0-2.9-1.3-2.9-2.9V13.9c0-1.6,1.3-2.9,2.9-2.9h31.1v21.3c0,0.3,0.3,0.6,0.6,0.6h21.4V85.6z   M67.2,66.4c0,0.3-0.3,0.6-0.6,0.6H33.4c-0.3,0-0.6-0.3-0.6-0.6s0.3-0.6,0.6-0.6h33.2C66.9,65.7,67.2,66,67.2,66.4z M67.2,77.4  c0,0.3-0.3,0.6-0.6,0.6H33.4c-0.3,0-0.6-0.3-0.6-0.6c0-0.3,0.3-0.6,0.6-0.6h33.2C66.9,76.8,67.2,77.1,67.2,77.4z M67.2,55.5  c0,0.3-0.3,0.6-0.6,0.6H33.4c-0.3,0-0.6-0.3-0.6-0.6c0-0.3,0.3-0.6,0.6-0.6h33.2C66.9,54.8,67.2,55.1,67.2,55.5z M33.4,43.9h33.2  c0.3,0,0.6,0.3,0.6,0.6c0,0.3-0.3,0.6-0.6,0.6H33.4c-0.3,0-0.6-0.3-0.6-0.6C32.8,44.2,33.1,43.9,33.4,43.9z M32.8,33.6  c0-0.3,0.3-0.6,0.6-0.6h11.5c0.3,0,0.6,0.3,0.6,0.6s-0.3,0.6-0.6,0.6H33.4C33.1,34.3,32.8,34,32.8,33.6z M32.8,22.7  c0-0.3,0.3-0.6,0.6-0.6h11.5c0.3,0,0.6,0.3,0.6,0.6c0,0.3-0.3,0.6-0.6,0.6H33.4C33.1,23.4,32.8,23.1,32.8,22.7z');
            svg.appendChild(p);
            return svg;
        }

        if (hours >= 0 && hours <= 10) {
            captiondef = `早上好，`;
        } else if (hours > 10 && hours <= 14) {
            captiondef = `中午好，`;
        } else if (hours > 14 && hours <= 18) {
            captiondef = `下午好，`;
        } else if (hours > 18 && hours <= 24) {
            captiondef = `晚上好，`;
        }
        captiondef += '<?php echo $_SESSION['username']; ?>';
        window.onmousedown = (event) => {
            var rmenu = document.querySelector('#rmenu');
            var rect = rmenu.getBoundingClientRect();
            if (!(event.clientX > rect.left && event.clientX < rect.right &&
                    event.clientY > rect.top && event.clientY < rect.bottom))
                rmenu.className = 'hide';
        }
        elementupfile.addEventListener('click', (event) => {
            event.stopPropagation();
        }, false);
        elementupfile.addEventListener('change', () => {
            var formdata = new FormData();
            formdata.append('upfile', elementupfile.files[0]);
            var reader = new FileReader();
            reader.addEventListener('load', (event) => {
                var wa = CryptoJS.lib.WordArray.create(event.target.result);
                const sha512 = CryptoJS.SHA512(wa).toString(CryptoJS.enc.Hex);
                fetch('/upload.php?' + new URLSearchParams({
                    'path': curdir,
                    'hash': sha512,
                    'name': elementupfile.files[0].name
                })).then(res => res.json()).then(msg => {
                    var p = document.querySelector('#progress');
                    if (msg.msg == 'pending') {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', (event) => {
                            p.style.display = '';
                            p.firstElementChild.innerHTML = String(Math.round(event.loaded / event.total * 100));
                        }, false);
                        xhr.addEventListener('load', () => {
                            setTimeout(() => {
                                p.style.display = 'none';
                            }, 2000);
                            getfiles();
                        }, false);
                        xhr.open('POST', '/upload.php?' + new URLSearchParams({
                            'path': curdir
                        }));
                        xhr.send(formdata);
                    } else {
                        p.style.display = '';
                        p.firstElementChild.innerHTML = '（秒传）' + '100';
                        setTimeout(() => {
                            p.style.display = 'none';
                        }, 2000);
                        getfiles();
                    }
                });
            });
            reader.readAsArrayBuffer(elementupfile.files[0]);
        });
        getfiles(curdir);
    </script>
</body>

</html>