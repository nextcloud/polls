module.exports = {
	root: true,
	env: {
		browser: true,
		es6: true,
		node: true,
		jest: true,
	},
	globals: {
		t: true,
		n: true,
		OC: true,
		OCA: true,
		Vue: true,
		VueRouter: true,
		moment: true,
	},
	parserOptions: {
		parser: 'babel-eslint',
		ecmaVersion: 6,
	},
	extends: [
		'plugin:@nextcloud/recommended',
		'eslint:recommended',
		'plugin:node/recommended',
		'plugin:vue/essential',
		'plugin:vue/recommended',
		'standard',
	],
	plugins: [
		'vue',
		'node',
	],
	rules: {
		'object-shorthand': 'error',
		'arrow-parens': 'error',
		'arrow-body-style': 'error',
		'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
		'no-negated-condition': 'error',
		'no-lonely-if': 'error',
		'newline-per-chained-call': ['error', { ignoreChainWithDepth: 4 }],
		'no-continue': 'error',
		'no-array-constructor': 'error',
		'@nextcloud/no-deprecations': 'warn',
		'@nextcloud/no-removed-apis': 'error',
		'node/no-missing-import': ['error', {
			allowModules: [],
			tryExtensions: ['.js', '.vue'],
		}],
		'vue/no-unused-properties': ['error', {
			groups: ['props'],
			deepData: false,
		}],
		'no-else-return': 'error',
		'comma-dangle': ['error', 'always-multiline'],
		// space before function ()
		'space-before-function-paren': ['error', 'never'],
		// curly braces always space
		'object-curly-spacing': ['error', 'always'],
		// stay consistent with array brackets
		'array-bracket-newline': ['error', 'consistent'],
		// 1tbs brace style
		'brace-style': 'error',
		// tabs only
		indent: ['error', 'tab'],
		'no-tabs': 0,
		'vue/html-indent': ['error', 'tab'],
		// only debug console
		'no-console': ['error', { allow: ['error', 'warn', 'debug'] }],
		// classes blocks
		'padded-blocks': ['error', { classes: 'always' }],
		// always have the operator in front
		'operator-linebreak': ['error', 'before'],
		// ternary on multiline
		'multiline-ternary': ['error', 'always-multiline'],
		// es6 import/export and require
		'node/no-unpublished-require': ['off'],
		'node/no-unsupported-features/es-syntax': ['off'],
		// kebab case components for vuejs
		'vue/component-name-in-template-casing': ['error', 'PascalCase', {
			registeredComponentsOnly: true,
			ignores: [],
		}],
		// space before self-closing elements
		'vue/html-closing-bracket-spacing': 'error',
		// code spacing with attributes
		// newline before closing bracket
		'vue/html-closing-bracket-newline': ['error', {
			singleline: 'never',
			multiline: 'never',
		}],
		'vue/max-attributes-per-line': [
			'error',
			{
				singleline: 3,
				multiline: {
					max: 3,
					allowFirstLine: true,
				},
			},
		],
	},
}
