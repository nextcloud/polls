const merge = require('webpack-merge')
const common = require('./webpack.common.js')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = merge(common, {
	mode: 'production',
	optimization: {
		minimizer: [
			new TerserPlugin({
				parallel: true,
				cache: true,
				terserOptions: {
					ecma: 6,
				},
			}),
		],
	},
})
