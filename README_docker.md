# oracle-tf
### [source and config](https://github.com/hitrov/oci-arm-host-capacity)

* build Dockerfile
  * $ docker login
  * $ docker buildx build --platform linux/arm64,linux/amd64 -t DOCKER/IMAGENAME:IMAGETAG -o type=registry .


* Build local whith DockerfilePerformance, edit DockerfilePerformance to first image
  * $ docker login
  * Fisrt and Second___: docker buildx build --platform linux/arm64,linux/amd64 -f DockerfilePerformance -t DOCKER/IMAGENAME:IMAGETAG -o type=image .  
  * Thirt______________: docker buildx build --platform linux/arm64,linux/amd64 -f DockerfilePerformance -t DOCKER/IMAGENAME:IMAGETAG -o type=registry . 

* run
  * $ docker create --name machine DOCKER/IMAGENAME:IMAGETAG
  * $ docker cp your-private-file.pem machine:/app/oci-arm-host-capacity 
  * $ docker cp .env machine:/app/oci-arm-host-capacity 
  * $ docker start machine


* Github action - .github/workflows/docker-image.yml
  * add secret - github.com > project > settings > Secrets > Actions
    * DOCKER_NAMESPACE
    * DOCKER_PASSWORD
      * create a token on user settings
    * DOCKER_REPOSITORY
    * DOCKER_USERNAME  
