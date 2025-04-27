commit := `git rev-parse master`
container_name := laauurraaa/scrybble-server
container := ${container_name}:${commit}

release: build-app upload-app
	echo "Releasing ${container}"

build-app:
	docker build -t ${container} --build-arg RELEASE_HASH=${commit} -f App.Dockerfile ..

upload-app:
	docker push ${container}
	docker tag ${container} ${container_name}:latest
	docker push ${container_name}:latest
