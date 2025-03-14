<?php include __DIR__ . '/../config.php' ?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="description" content="webrt示例,一对一视频聊天-基于swoole实现">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
    <meta itemprop="description" content="swoole webrtc 视频聊天 demo">
    <meta itemprop="name" content="AppRTC">
    <meta name="mobile-web-app-capable" content="yes">
    <meta id="theme-color" name="theme-color" content="#1e1e1e">
    <title>webrt示例,一对一视频聊天-基于swoole实现</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #fff;
            color: #333;
            font-family: 'Roboto', 'Open Sans', 'Lucida Grande', sans-serif;
            height: 100%;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .videos {
            font-size: 0;
            height: 100%;
            pointer-events: none;
            position: absolute;
            transition: all 1s;
            width: 100%;
        }

        #localVideo {
            height: 100%;
            max-height: 100%;
            max-width: 100%;
            object-fit: cover;
            -moz-transform: scale(-1, 1);
            -ms-transform: scale(-1, 1);
            -o-transform: scale(-1, 1);
            -webkit-transform: scale(-1, 1);
            transform: scale(-1, 1);
            transition: opacity 1s;
            width: 100%;
        }

        #remoteVideo {
            display: block;
            height: 100%;
            max-height: 100%;
            max-width: 100%;
            object-fit: cover;
            position: absolute;
            -moz-transform: rotateY(180deg);
            -ms-transform: rotateY(180deg);
            -o-transform: rotateY(180deg);
            -webkit-transform: rotateY(180deg);
            transform: rotateY(180deg);
            transition: opacity 1s;
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="videos">
        <video id="localVideo" autoplay style="width:400px;height:300px; margin:10px; box-shadow: 4px 4px 4px 4px gray;"
            muted="true"></video>
        <video id="remoteVideo" autoplay
            style="width:400px;height:300px; margin:10px; box-shadow: 4px 4px 4px 4px gray;"></video>
        <!--    class="hidden"-->
    </div>

    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/adapter.js"></script>

    <script type="text/javascript">
        var WS_ADDRESS = '<?php echo $SIGNALING_ADDRESS; ?>';

        // 房间id
        var cid = getUrlParam('cid');
        if (cid == '' || cid == null) {
            cid = Math.random().toString(36).substr(2);
            location.href = '?cid=' + cid;
        }
        var answer = 0;

        // 基于订阅，把房间id作为主题
        var subject = 'private-video-room-' + cid;

        // 建立与websocket的连接
        var ws = new WebSocket(WS_ADDRESS);
        console.log(ws);
        ws.onopen = async function () {
            console.log('ws连接');
            subscribe(subject);
            // navigator.mediaDevices.getUserMedia({ audio: true, video: true })
            // .then(function (stream) {
            //     localVideo.srcObject = stream;
            //     localStream = stream;
            //     localVideo.addEventListener('loadedmetadata', function () {
            //         publish('client-call', null)
            //     });
            // }).catch(function (e) {
            //     alert(e);
            // });

            let stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
            localVideo.srcObject = stream;
            localStream = stream;
            localVideo.addEventListener('loadedmetadata', function () {
                publish('client-call', null)
            });
        };
        ws.onmessage = async function (e) {
            var package = JSON.parse(e.data);
            var data = package.data;

            console.log('🥝 recive:', package.event);

            switch (package.event) {
                case 'client-call':
                    cratePeerConnection(localStream);

                    let desc = await pc.createOffer({ offerToReceiveAudio: 1, offerToReceiveVideo: 1 });
                    await pc.setLocalDescription(desc);
                    publish('client-offer', pc.localDescription);
                    break;

                case 'client-answer':
                    await pc.setRemoteDescription(new RTCSessionDescription(data));
                    break;

                case 'client-offer':
                    cratePeerConnection(localStream);

                    await pc.setRemoteDescription(new RTCSessionDescription(data));
                    if (!answer) {
                        let desc = await pc.createAnswer();
                        await pc.setLocalDescription(desc);
                        publish('client-answer', pc.localDescription);
                        answer = true;
                    }
                    break;

                case 'client-candidate':
                    pc.addIceCandidate(new RTCIceCandidate(data), function () { }, function (e) { alert(e); });
                    break;
            }
        };

        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');

        navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
        const configuration = {
            iceServers: [
                // {
                //     urls: [
                //         'turn:business.swoole.com:3478?transport=udp',
                //         'turn:business.swoole.com:3478?transport=tcp'
                //     ],
                //     username: 'ceshi',
                //     credential: 'ceshi'
                // },
                {
                    urls: 'turn:101.201.247.187:3478',
                    username: 'turn',
                    credential: 'xfb@123'
                }]
        };
        var pc, localStream;

        function cratePeerConnection(localStream) {
            pc = new RTCPeerConnection(configuration);
            pc.onicecandidate = function (event) {
                if (event.candidate) {
                    publish('client-candidate', event.candidate);
                }
            };

            // console.log('⭕ addStream');
            // pc.addStream(localStream);
            console.log('⭕ addTrack');
            var tracks = localStream.getTracks();
            for (var i = 0; i < tracks.length; i++) {
                pc.addTrack(tracks[i], localStream);
            }

            pc.onaddstream = function (e) {
                remoteVideo.srcObject = e.stream;
                console.log('🟢 onaddstream');
            };

            pc.oniceconnectionstatechange = (event) => {
                console.log("😃 PC EVENT oniceconnectionstatechange: ", event.target.iceConnectionState);

                // new 初始状态，表示 ICE 代理尚未开始连接。
                // checking 表示 ICE 代理正在检查一个或多个候选对。 当调用 setRemoteDescription 或 addIceCandidate 后，通常会进入此状态。
                // connected 至少有一对候选成功连接，但可能不是最优的候选对。
                // completed 所有候选对都已检查完毕，并且至少有一对候选成功连接。
                // failed 所有候选对都检查失败，无法建立连接。
                // disconnected 至少有一条数据路径断开连接。
                // closed RTCPeerConnection 已关闭。
            };
        }

        function publish(event, data) {
            console.log("⚽ send:", event)
            ws.send(JSON.stringify({
                cmd: 'publish',
                subject: subject,
                event: event,
                data: data
            }));
        }

        function subscribe(subject) {
            ws.send(JSON.stringify({
                cmd: 'subscribe',
                subject: subject
            }));
        }

        function getUrlParam(name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
            var r = window.location.search.substr(1).match(reg);
            if (r != null) return unescape(r[2]);
            return null;
        }

    </script>
</body>

</html>