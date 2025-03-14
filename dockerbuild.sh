docker stop webrtc-demo
docker rm webrtc-demo

docker build  --tag webrtc-demo:latest  .

docker run \
--name=webrtc-demo \
--net host \
-e "SIGNALING_ADDRESS=ws://101.201.247.187:9509" \
-d webrtc-demo:latest 


docker logs webrtc-demo