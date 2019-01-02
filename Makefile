# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Dependencies:
# * make
# * which
# * npm
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive

app_name=$(notdir $(CURDIR))
build_dir=$(CURDIR)/build
build_tools_dir=$(build_dir)/tools
build_source_dir=$(build_dir)/source
appstore_build_dir=$(build_dir)/artifacts/appstore
sign_dir=$(build_dir)/sign
appstore_package_name=$(appstore_build_dir)/$(app_name)
nc_cert_dir=$(HOME)/.nextcloud/certificates
composer=$(shell which composer 2> /dev/null)

all: dev-setup lint build-js-production test

# Dev environment setup
dev-setup: clean clean-dev npm-init composer

npm-init:
	npm install

# Build js
build-js-production:
	npm run build

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (,$(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_dir)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_dir)
	php $(build_tools_dir)/composer.phar install --prefer-dist
	php $(build_tools_dir)/composer.phar update --prefer-dist
else
	composer install --prefer-dist
	composer update --prefer-dist
endif

# Lint
lint:
	npm run lint

lint-fix:
	npm run lint:fix

# Removes the appstore build and compiled js files
.PHONY: clean
clean:
	rm -rf $(build_dir)
	rm -f js/polls.js
	rm -f js/polls.js.map

clean-dev:
	rm -rf node_modules


# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore: dev-setup lint build-js-production composer
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude="ISSUE_TEMPLATE.md" \
	--exclude="*.log" \
	--exclude=".*" \
	--exclude="build" \
	--exclude="bower.json" \
	--exclude="composer.*" \
	--include="css/vendor" \
	--exclude="css/*.css" \
	--exclude="js/.*" \
	--exclude="js/*.log" \
	--exclude="js/bower.json" \
	--exclude="js/karma.*" \
	--exclude="js/node_modules" \
	--exclude="js/package.json" \
	--exclude="js/protractor.*" \
	--exclude="js/test" \
	--exclude="js/tests" \
	--include="js/vendor" \
	--exclude="karma.*" \
	--exclude="l10n/no-php" \
	--exclude="Makefile" \
	--exclude="node_modules" \
	--exclude="package*" \
	--exclude="phpunit*xml" \
	--exclude="protractor.*" \
	--exclude="screenshots" \
	--exclude="src" \
	--exclude="tests" \
	--exclude="vendor" \
	./ $(build_source_dir)/$(app_name)

	tar cvzf $(appstore_package_name).tar.gz \
	   --directory="$(build_source_dir)" $(app_name)

	@if [ -f $(nc_cert_dir)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(nc_cert_dir)/$(app_name).key $(appstore_build_dir)/$(app_name).tar.gz | openssl base64; \
	fi

.PHONY: test
test: composer
	$(CURDIR)/vendor/phpunit/phpunit/phpunit --coverage-clover clover.xml -c phpunit.xml
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml
