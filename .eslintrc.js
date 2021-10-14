module.exports = {
	extends: [
		'plugin:promise/recommended',
		'plugin:vue/essential',
		'@nextcloud',
	],
	plugins: [
		'promise',
	],
	rules: {
		'arrow-parens': 'error',
		'arrow-body-style': 'error',
		'brace-style': 'error',
		'prefer-template': 'error',
		'newline-per-chained-call': ['error', { ignoreChainWithDepth: 4 }],
		'no-array-constructor': 'error',
		'no-continue': 'error',
		'no-else-return': ['error', { allowElseIf: false }],
		'no-lonely-if': 'error',
		'no-negated-condition': 'error',
		'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
		'object-curly-spacing': ['error', 'always'],
	},
}
