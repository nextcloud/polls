/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')

webpackConfig.entry = {
	main: path.join(__dirname, 'src/js/', 'main.js'),
	userSettings: path.join(__dirname, 'src/js/', 'userSettings.js'),
	adminSettings: path.join(__dirname, 'src/js/', 'adminSettings.js'),
	dashboard: path.join(__dirname, 'src/js/', 'dashboard.js'),
}

webpackConfig.output = {
	...webpackConfig.output,
	clean: true,
}

webpackRules.RULE_VUE = {
	...webpackRules.RULE_VUE,
	options: {
		compilerOptions: {
			whitespace: 'condense',
		},
	},
}

webpackConfig.module.rules = Object.values(webpackRules)

module.exports = webpackConfig
