
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );
// const postcssPresetEnv = require( 'postcss-preset-env' );
// const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
// const IgnoreEmitPlugin = require( 'ignore-emit-webpack-plugin' );

//const production = process.env.NODE_ENV === '';

module.exports = {
	...defaultConfig,
	entry: {
		front: path.resolve( process.cwd(), 'src', 'front.js' ),
        admin: path.resolve( process.cwd(), 'src', 'admin.js' ),
        useradmin: path.resolve( process.cwd(), 'src', 'useradmin.js' ),
		gutenberg: path.resolve( process.cwd(), 'src', 'gutenberg.js' ),
		frontStyle: path.resolve( process.cwd(), 'src', 'front.scss' ),
		adminStyle: path.resolve( process.cwd(), 'src', 'admin.scss' ),
	},
	// optimization: {
	// 	...defaultConfig.optimization,
	// 	splitChunks: {
	// 		cacheGroups: {
	// 			front: {
	// 				name: 'editor',
	// 				test: /editor\.(sc|sa|c)ss$/,
	// 				chunks: 'all',
	// 				enforce: true,
	// 			},
	// 			frontStyle: {
	// 				name: 'frontStyle',
	// 				test: /style\.(sc|sa|c)ss$/,
	// 				chunks: 'all',
	// 				enforce: true,
	// 			},
	// 			default: false,
	// 		},
	// 	},
	// },
	// module: {
	// 	...defaultConfig.module,
	// 	rules: [
	// 		...defaultConfig.module.rules,
	// 		{
	// 			test: /\.(sc|sa|c)ss$/,
	// 			exclude: /node_modules/,
	// 			use: [
	// 				{
	// 					loader: MiniCssExtractPlugin.loader,
	// 				},
	// 				{
	// 					loader: 'css-loader',
	// 					options: {
	// 						sourceMap: ! production,
	// 					},
	// 				},
	// 				{
	// 					loader: 'postcss-loader',
	// 					options: {
	// 						ident: 'postcss',
	// 						plugins: () => [
	// 							postcssPresetEnv( {
	// 								stage: 3,
	// 								features: {
	// 									'custom-media-queries': {
	// 										preserve: false,
	// 									},
	// 									'custom-properties': {
	// 										preserve: true,
	// 									},
	// 									'nesting-rules': true,
	// 								},
	// 							} ),
	// 						],
	// 					},
	// 				},
	// 				{
	// 					loader: 'sass-loader',
	// 					options: {
	// 						sourceMap: ! production,
	// 					},
	// 				},
	// 			],
	// 		},
	// 	],
	// },
	// plugins: [
	// 	...defaultConfig.plugins,
	// 	new MiniCssExtractPlugin( {
	// 		filename: '[name].css',
	// 	} ),
	// 	new IgnoreEmitPlugin( [ 'editor.js', 'style.js' ] ),
	// ],
};