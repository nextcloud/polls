/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
	extends: [
		'@nextcloud',
		'prettier',
	],
	plugins: [
		'promise',
	],
	rules: {
		'arrow-body-style': 'error',
		'no-array-constructor': 'error',
		'no-continue': 'error',
		'no-else-return': ['error', { allowElseIf: false }],
		'no-lonely-if': 'error',
		'no-negated-condition': 'error',
		'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
		'prefer-template': 'error',
		'vue/no-v-model-argument': 'off',
		'vue/no-unused-properties': ['error', {
			groups: ['props', 'data', 'computed', 'methods'],
			deepData: true,
			ignorePublicMembers: true,
		}],
	},
}
