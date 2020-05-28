const merge = require('webpack-merge')
const common = require('./webpack.common.js')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = merge(common, {
	mode: 'development',
	devtool: 'source-map',
	devServer: {
		historyApiFallback: true,
		noInfo: true,
		overlay: true,
	},
	optimization: {
		minimizer: [
			new TerserPlugin({
				parallel: true,
				cache: true,
				extractComments: false,
				terserOptions: {
					ecma: 6,
				},
			}),
		],
	},
})
