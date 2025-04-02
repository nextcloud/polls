/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
	extends: ['@nextcloud/eslint-config/vue3', 'prettier'],
	plugins: ['promise'],
	rules: {
		'arrow-body-style': 'error',
		'jsdoc/require-jsdoc': [
			'error' | 'warn',
			{
				publicOnly: {
					ancestorsOnly: true,
				},
			},
		],
		'jsdoc/require-param-description': 'off',
		'no-array-constructor': 'error',
		'no-continue': 'error',
		'no-else-return': ['error', { allowElseIf: false }],
		'no-lonely-if': 'error',
		'no-negated-condition': 'error',
		'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
		'prefer-template': 'error',
		'vue/first-attribute-linebreak': [
			'error',
			{ multiline: 'below', singleline: 'ignore' },
		],
		'vue/no-v-model-argument': 'off',
		'vue/no-unused-properties': [
			'error',
			{
				groups: ['props', 'data', 'computed', 'methods'],
				deepData: true,
				ignorePublicMembers: true,
			},
		],
	},
}
