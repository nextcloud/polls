const path = require('path')
const webpack = require('webpack')
const { VueLoaderPlugin } = require('vue-loader')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')

const appName = process.env.npm_package_name
const appVersion = process.env.npm_package_version
const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'

module.exports = {
	mode: buildMode,
	devtool: isDev ? 'eval' : false,
	entry: {
		polls: path.join(__dirname, 'src/js/', 'main.js'),
		userSettings: path.join(__dirname, 'src/js/', 'userSettings.js'),
		adminSettings: path.join(__dirname, 'src/js/', 'adminSettings.js'),
	},
	output: {
		clean: true,
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: '[name].js',
		chunkFilename: `${appName}.[name].[contenthash].js`,
		chunkLoadingGlobal: 'webpackJsonpOCAPolls',
		devtoolNamespace: appName,
		devtoolModuleFilenameTemplate(info) {
			const rootDir = process.cwd()
			const rel = path.relative(rootDir, info.absoluteResourcePath)
			return `webpack:///${appName}/${rel}`
		},
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
				type: 'asset/inline',
			},
		],
	},

	optimization: {
		chunkIds: 'named',
		splitChunks: {
			automaticNameDelimiter: '-',
		},
		minimize: !isDev,
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					output: {
						comments: false,
					},
				},
				extractComments: true,
			}),
		],
	},

	plugins: [
		new VueLoaderPlugin(),
		new webpack.DefinePlugin({
			appName: JSON.stringify('polls'),
		}),
		new ESLintPlugin({
			quiet: true,
			extensions: ['js', 'vue'],
		}),
		new StyleLintPlugin({
			files: 'src/**/*.{css,scss,vue}',
			failOnError: !isDev,
		}),
		new webpack.DefinePlugin({ appName: JSON.stringify(appName) }),
		new webpack.DefinePlugin({ appVersion: JSON.stringify(appVersion) }),
	],
	resolve: {
		alias: {
			vue$: 'vue/dist/vue.esm.js',
			src: path.resolve(__dirname, 'src/js'),
		},
		extensions: ['*', '.js', '.vue', '.json'],
	},
}
