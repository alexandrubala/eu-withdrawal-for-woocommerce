/**
 * Webpack configuration for the withdrawal-button block.
 *
 * @package EUWithdrawal
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(
			process.cwd(),
			'blocks/withdrawal-button/index.js'
		),
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'blocks/withdrawal-button/build' ),
	},
};
