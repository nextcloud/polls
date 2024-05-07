const stylelintConfig = require('@nextcloud/stylelint-config')

// const overrides = {
// 	ignoreFiles: ['**/*.js', '**/*.gif', '**/*.svg'],
// 	rules: {
// 		'rule-empty-line-before': [
// 			'always-multi-line',
// 			{
// 				ignore: ['after-comment', 'inside-block'],
// 			},
// 		],
// 	},
// }

module.exports = { ...stylelintConfig, /* overrides */}
