const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'pica-pay-blockeditor': './admin/js/pica-pay-blockeditor.js'
	},
	output: {
		path: path.join(__dirname, 'build'),
		filename: '[name].js'
	}
}
