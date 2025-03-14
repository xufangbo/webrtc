docker stop webrtc-demo
docker rm webrtc-demo

docker build  --tag webrtc-demo:latest  .

docker run \
--name=webrtc-demo \
--net host \
-d webrtc-demo:latest 


docker logs webrtc-demo