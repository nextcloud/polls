# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive

app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
build_source_directory=$(CURDIR)/build/source
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
nc_cert_directory=$(HOME)/.nextcloud/certificates
composer=$(shell which composer 2> /dev/null)

all: composer

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (,$(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
	php $(build_tools_directory)/composer.phar update --prefer-dist
else
	composer install --prefer-dist
	composer update --prefer-dist
endif

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory)
	rm -rf $(build_source_directory)
	mkdir -p $(appstore_build_directory)
	mkdir -p $(build_source_directory)

	rsync -a \
	--exclude="build" \
	--exclude="tests" \
	--exclude="Makefile" \
	--exclude="*.log" \
	--exclude="phpunit*xml" \
	--exclude="composer.*" \
	--exclude="js-src" \
	--exclude="js/node_modules" \
	--exclude="js/tests" \
	--exclude="js/test" \
	--exclude="js/*.log" \
	--exclude="js/package.json" \
	--exclude="js/bower.json" \
	--exclude="js/karma.*" \
	--exclude="js/protractor.*" \
	--exclude="node_modules" \
	--exclude="package.json" \
	--exclude="bower.json" \
	--exclude="karma.*" \
	--exclude="protractor.*" \
	--exclude=".*" \
	--exclude="js/.*" \
	--exclude="l10n/no-php" \
	./ $(build_source_directory)/$(app_name)

	tar cvzf $(appstore_package_name).tar.gz --directory="$(build_source_directory)" $(app_name)

	@if [ -f $(nc_cert_directory)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(nc_cert_directory)/$(app_name).key $(appstore_build_directory)/$(app_name).tar.gz | openssl base64; \
	fi

.PHONY: test
test: composer
	$(CURDIR)/vendor/phpunit/phpunit/phpunit --coverage-clover clover.xml -c phpunit.xml
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml
