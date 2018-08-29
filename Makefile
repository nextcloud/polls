# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * sass: for building css files for ownCloud

app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
build_source_directory=$(CURDIR)/build/source
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
marketplace_build_directory=$(CURDIR)/build/artifacts/marketplace
marketplace_package_name=$(marketplace_build_directory)/$(app_name)
nc_cert_directory=$(HOME)/.nextcloud/certificates
oc_cert_directory=$(HOME)/.owncloud/certificates
composer=$(shell which composer 2> /dev/null)
sass=$(shell which sass 2> /dev/null)

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
	rm -rf ./.sass-cache
	rm -rf ./build

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory)
	rm -rf $(build_source_directory)
	mkdir -p $(appstore_build_directory)
	mkdir -p $(build_source_directory)

	rsync -a \
	--include="js/vendor" \
	--exclude="*.log" \
	--exclude=".*" \
	--exclude="bower.json" \
	--exclude="composer.*" \
	--exclude="ISSUE_TEMPLATE.md" \
	--exclude="karma.*" \
	--exclude="Makefile" \
	--exclude="package.json" \
	--exclude="phpunit*xml" \
	--exclude="protractor.*" \
	--exclude="build" \
	--exclude="css/*.css" \
	--exclude="js/node_modules" \
	--exclude="js/tests" \
	--exclude="js/test" \
	--exclude="js/*.log" \
	--exclude="js/package.json" \
	--exclude="js/bower.json" \
	--exclude="js/karma.*" \
	--exclude="js/protractor.*" \
	--exclude="js/.*" \
	--exclude="l10n/no-php" \
	--exclude="node_modules" \
	--exclude="oc-css" \
	--exclude="src" \
	--exclude="tests" \
	--exclude="vendor" \
	./ $(build_source_directory)/$(app_name)

	tar cvzf $(appstore_package_name).tar.gz --directory="$(build_source_directory)" $(app_name)

	@if [ -f $(nc_cert_directory)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(nc_cert_directory)/$(app_name).key $(appstore_build_directory)/$(app_name).tar.gz | openssl base64; \
	fi

# Builds the source package for the marketplace, ignores php and js tests
.PHONY: marketplace
marketplace:
ifeq (,$(sass))
	@echo "No sass command available, please install it and rerun"
else
	sass --no-source-map ./src/css-oc:css
	rm -rf $(marketplace_build_directory)
	rm -rf $(build_source_directory)
	mkdir -p $(marketplace_build_directory)
	mkdir -p $(build_source_directory)

	rsync -a \
	--include="js/vendor" \
	--exclude="*.log" \
	--exclude=".*" \
	--exclude="bower.json" \
	--exclude="composer.*" \
	--exclude="ISSUE_TEMPLATE.md" \
	--exclude="karma.*" \
	--exclude="Makefile" \
	--exclude="package.json" \
	--exclude="phpunit*xml" \
	--exclude="protractor.*" \
	--exclude="build" \
	--exclude="css/*.scss" \
	--exclude="js/node_modules" \
	--exclude="js/tests" \
	--exclude="js/test" \
	--exclude="js/*.log" \
	--exclude="js/package.json" \
	--exclude="js/bower.json" \
	--exclude="js/karma.*" \
	--exclude="js/protractor.*" \
	--exclude="js/.*" \
	--exclude="l10n/no-php" \
	--exclude="node_modules" \
	--exclude="oc-css" \
	--exclude="src" \
	--exclude="tests" \
	--exclude="vendor" \
	./ $(build_source_directory)/$(app_name)

	# We need to replace Nc screenshot urls with the oC ones
	sed -i -E "s~(<screenshot>)([^<]*).(png|jpg|jpeg)(</screenshot>)~\1\2-oc.\3\4~" $(build_source_directory)/$(app_name)/appinfo/info.xml

	tar cvzf $(marketplace_package_name).tar.gz --directory="$(build_source_directory)" $(app_name)

	@if [ -f $(oc_cert_directory)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(oc_cert_directory)/$(app_name).key $(marketplace_build_directory)/$(app_name).tar.gz | openssl base64; \
	fi
endif

.PHONY: test
test: composer
	$(CURDIR)/vendor/phpunit/phpunit/phpunit --coverage-clover clover.xml -c phpunit.xml
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml
