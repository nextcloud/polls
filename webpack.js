const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const ESLintPlugin = require('eslint-webpack-plugin')
const webpack = require('webpack')

module.exports = {
	entry: path.join(__dirname, 'src/js/', 'main.js'),
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: 'polls.js',
		chunkFilename: 'polls.[name].[contenthash].js',
		chunkLoadingGlobal: 'webpackJsonpOCAPolls',
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				options: {
					compilerOptions: {
						whitespace: 'condense',
					},
				},
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.css$/,
				use: [
					'vue-style-loader',
					{
						loader: 'css-loader',
						options: { esModule: false },
					},
				],
			},
			{
				test: /\.scss$/,
				use: [
					'vue-style-loader',
					{
						loader: 'css-loader',
						options: { esModule: false },
					},
					'sass-loader',
				],
			},
			{
				test: /\.(png|jpg|gif|svg)$/,
				loader: 'url-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
		],
	},
	plugins: [
		new VueLoaderPlugin(),
		new CleanWebpackPlugin(),
		// new webpack.DefinePlugin({
		// 	appName: JSON.stringify('polls'),
		// }),
		new ESLintPlugin({
			quiet: true,
			extensions: ['js', 'vue'],
		}),
	],
	resolve: {
		alias: {
			vue$: 'vue/dist/vue.esm.js',
			src: path.resolve(__dirname, 'src/js'),
		},
		extensions: ['*', '.js', '.vue', '.json'],
	},
}
