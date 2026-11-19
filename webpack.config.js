const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const RtlCssPlugin = require( '@wordpress/scripts/plugins/rtlcss-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	// the plugin does not enqueue -rtl.css files, so they are not built
	plugins: defaultConfig.plugins.filter( ( plugin ) => ! ( plugin instanceof RtlCssPlugin ) ),
	entry: {
		front: path.resolve( process.cwd(), 'src', 'front.js' ),
        admin: path.resolve( process.cwd(), 'src', 'admin.js' ),
        useradmin: path.resolve( process.cwd(), 'src', 'useradmin.js' ),
		gutenberg: path.resolve( process.cwd(), 'src', 'gutenberg.js' ),
		frontStyle: path.resolve( process.cwd(), 'src', 'front.scss' ),
		adminStyle: path.resolve( process.cwd(), 'src', 'admin.scss' ),
		loginRegister: path.resolve( process.cwd(), 'src', 'loginRegister.js' ),
		loginRegisterStyle: path.resolve( process.cwd(), 'src', 'loginRegisterStyle.scss' ),
	},
};