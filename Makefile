docker:
	./make-phar.php
	docker build -t marisa50/simple:latest .
	docker login
	docker push marisa50/simple:latest
	rm simple.phar.gz simple.phar
