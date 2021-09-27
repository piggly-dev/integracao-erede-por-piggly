const paths = require('./paths');
const { VueLoaderPlugin } = require("vue-loader");

module.exports = {
	// Where webpack looks to start building the bundle
	entry: {
		settings: [ paths.src + '/js/admin/main.ts' ]
	},

	// Where webpack outputs the assets and bundles
	output: {
		path: paths.build,
		filename: 'pgly-erede-por-piggly.[name].js',
		publicPath: '/',
	},

	// Customize the webpack build process
	plugins: [
		new VueLoaderPlugin()
	],

	// Determine how modules within the project are treated
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: "vue-loader"
			},
			{
				test: /\.tsx?$/,
				loader: 'ts-loader',
				options: { appendTsSuffixTo: [/\.vue$/] },
				exclude: /node_modules/
			}
		],
	},

	resolve: {
		alias: {
			// this isn't technically needed, since the default `vue` entry for bundlers
			// is a simple `export * from '@vue/runtime-dom`. However having this
			// extra re-export somehow causes webpack to always invalidate the module
			// on the first HMR update and causes the page to reload.
			vue: "@vue/runtime-dom",
			"@": paths.src + '/js/admin',
		},
		extensions: ['.tsx', '.ts', '.js', '.vue', '.json']
	},
};