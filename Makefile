all: phar docker cleanup

phar:
	./make-phar.php

docker:
	docker build -t marisa50/simple:latest .
	docker login
	docker push marisa50/simple:latest

cleanup:
	rm simple.phar.gz simple.phar
