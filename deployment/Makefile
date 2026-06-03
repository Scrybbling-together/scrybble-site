commit := `git rev-parse master`
container_name := laauurraaa/scrybble-server
container := ${container_name}:${commit}

check-branch:
	if [ -n "$$(git status --porcelain)" ]; then \
		echo "Branch has unstaged changes. Clean up your dirty room before building!"; \
		exit 1; \
	fi


release: build-app upload-app
	echo "Releasing ${container}"

build-app: check-branch
	docker build --target production -t ${container} --build-arg RELEASE_HASH=${commit} -f App.Dockerfile ..

upload-app: check-branch
	docker tag ${container} ${container_name}:latest
	docker push ${container}
	docker push ${container_name}:latest
