/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { recommended } from '@nextcloud/eslint-config'
import prettier from 'eslint-config-prettier'

export default [
	...recommended,
	{
		rules: {
			'arrow-body-style': 'error',
			'jsdoc/require-jsdoc': [
				'warn',
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
		},
	},
	{
		files: ['**/*.vue'],
		rules: {
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
	},
	prettier,
]
