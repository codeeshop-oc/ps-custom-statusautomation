copy-index-htaccess-file:
	./scripts/copy-index-htaccess-file.sh "/home/anant/git/prestashop-php-cs-biolerplate"
	print "---------Done---------"

git-push:
	./scripts/git-push.sh "/home/anant/git/prestashop-php-cs-biolerplate"
	print "---------Done---------"

scratch-install-prestashop-copy-index-htaccess:
	./scripts/scratch-install-prestashop.sh "/home/anant/git/prestashop-php-cs-biolerplate"
	print "---------Done---------"