const path = require('path');

const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );

module.exports = {
	entry:[
		'whatwg-fetch',
		"babel-polyfill",
		'./components/index.js'
	],
	output: {
		path: path.resolve(__dirname, '..'),
		filename: 'importer.js',
	},
	module: {
		rules: [
			{
				test: /.js$/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['es2017', 'react', 'stage-0'],
						plugins: [
							['import', { libraryName: "antd", style: true }]
						]
					}
				},
				exclude: /node_modules/
			},
			{
				test: /\.css$/i,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: 'css-loader'
				} ),
			},
			{
				test: /\.(png|svg|jpg|gif)$/,
				use: [
					'file-loader'
				]
			},
			{
				test: /\.less$/,
				use: [
					{loader: "style-loader"},
					{loader: "css-loader"},
					{loader: "less-loader",
						options: {
							// modifyVars: themeVariables,
							noIeCompat: true,
							paths: [
								path.resolve(__dirname, "node_modules")
							]
							// root: path.resolve(__dirname, '../../')
						}
					}
				]
			}
		],
	},

	devtool: 'source-map',

	plugins: [
		new ExtractTextPlugin( {
			disable: false,
			filename: 'style.bundle.css',
			allChunks: true
		} )
	]
};
