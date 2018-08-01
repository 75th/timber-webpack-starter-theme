const { PATHS, HOST, PORT, THEMENAME } = require("./env.config");
const utils = require("./scripts/utils");
const webpack = require("webpack");
const path = require("path");
const WriteFilePlugin = require("write-file-webpack-plugin");
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const UglifyJsPlugin = require("uglifyjs-webpack-plugin")
const ENV = utils.getEnv();
const WATCH = global.watch || false;

module.exports = {
	entry: getEntry(),
	mode: ENV,

	output: {
		path: PATHS.compiled(),
		publicPath:
			ENV === "production"
				? "/"
				: `http://${HOST}:${PORT}/wp-content/themes/${THEMENAME}/`,
		filename: "js/[name].js",
		sourceMapFilename: "[file].map"
	},

	module: {
		rules: [
			{
				test: /\.js$/,
				loader: "babel-loader",
				exclude: /node_modules/,
				include: PATHS.src()
			},
			{
				test: require.resolve('jquery'),
				use: [
					{
						loader: 'expose-loader',
						options: 'jQuery'
					},
					{
						loader: 'expose-loader',
						options: '$'
					}
				]
			},
			{
				test: /\.scss$/,
				use: ExtractTextPlugin.extract({
					use: [
						{
							loader: 'css-loader',
							options: {
								url: false,
								sourceMap: ENV !== 'production'
							}
						},
						{
							loader: 'postcss-loader',
							options: {
								sourceMap: ENV !== 'production'
							}
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: ENV !== 'production'
							}
						}
					]
				})
			}
		]
	},

	devtool: ENV === "production" ? false : "source-map",

	plugins: getPlugins(ENV),

	target: "web",

	watch: WATCH
};

/*
	CONFIG ENV DEFINITIONS
 */

function getEntry() {
	const entry = {};
	entry.main = [PATHS.src("js", "THEME-NAME.js"), PATHS.src("css", "THEME-NAME.scss")];
	if (ENV === "development") entry.main.push("webpack-hot-middleware/client");
	return entry;
}

function getPlugins(env) {
	const plugins = [
		new webpack.DefinePlugin({ "process.env.NODE_ENV": JSON.stringify(env) }),
		new CopyWebpackPlugin([{ from: PATHS.src("images"), to: "images" }], {
			ignore: ["*.psd"]
		})
	];

	plugins.push(new ExtractTextPlugin({
		filename: 'css/style.css',
		allChunks: true
	}));

	switch (env) {
		case "production":
			plugins.push(
				new UglifyJsPlugin({})
			);
		break;

		case "development":
			plugins.push(new webpack.HotModuleReplacementPlugin());
			plugins.push(new webpack.NoEmitOnErrorsPlugin());
			plugins.push(new WriteFilePlugin());
		break;
	}

	return plugins;
}
