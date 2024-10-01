# SPDX-FileCopyrightText: 2016 Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

# Dependencies:
# * make
# * which
# * npm
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive

app_name=polls

project_dir=.
build_dir=$(project_dir)/build
build_tools_dir=$(build_dir)/tools
build_source_dir=$(build_dir)/source
appstore_build_dir=$(build_dir)/artifacts/appstore
appstore_package_name=$(appstore_build_dir)/$(app_name)
nc_cert_dir=$(HOME)/.nextcloud/certificates
composer=$(shell which composer 2> /dev/null)
version=$(shell node -p -e "require('./package.json').version")

# all steps for an appstore release
appstore: setup-build build-js-production package

# install deps for release package
setup-build: setup-build-composer npm-init

# install deps for ci (tests and analysis)
setup-dev: setup-dev-composer npm-init

# install composer deps for ci (tests and analysis)
setup-build-composer: composer
	composer install --no-dev -o

# install composer deps for release package
setup-dev-composer: composer
	composer install -o

# install node deps
npm-init:
	npm ci

# remove build dir
clean:
	rm -rf $(build_dir)

# remove deps
clean-dev: clean
	rm -rf node_modules
	rm -rf ./vendor

# lint js, css and php
lint:
	npm run lint
	npm run stylelint
	composer run cs:check

# lint fix js, css and php
lint-fix:
	npm run lint:fix
	npm run stylelint:fix
	composer run cs:fix

cs:
	composer run cs:check

cs-fix:
	composer run cs:fix

# build vue app
build-js-production:
	npm run build

# install composer, if not installed
.PHONY: composer
composer:
ifeq (,$(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_dir)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_dir)
	php $(build_tools_dir)/composer.phar install --prefer-dist
	php $(build_tools_dir)/composer.phar update --prefer-dist
endif

# Builds the source package for the appstore
# signs, if certificate is present
package: clean
	mkdir -p $(build_source_dir)
	mkdir -p $(appstore_build_dir)
	rsync -zarh $(project_dir)/ --files-from="$(project_dir)/sync_list.txt" --exclude="vendor/bin" $(build_source_dir)/$(app_name)
	tar -czf $(appstore_package_name).tar.gz --directory="$(build_source_dir)" $(app_name)
	@if [ -f $(nc_cert_dir)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(nc_cert_dir)/$(app_name).key $(appstore_build_dir)/$(app_name).tar.gz | openssl base64; \
	fi


.PHONY: test
test: composer setup-dev-composer
	phpunit --coverage-clover clover.xml -c tests/phpunit.xml
	phpunit -c phpunit.integration.xml
