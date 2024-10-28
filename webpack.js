const webpackConfig = require('@nextcloud/webpack-vue-config')
//const ESLintPlugin = require('eslint-webpack-plugin')
//const StyleLintPlugin = require('stylelint-webpack-plugin')
const path = require('path')
const webpack = require('webpack')

webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'main.js') },
}


// Add DefinePlugin to make appId available globally
webpackConfig.plugins.push(
	new webpack.DefinePlugin({
		'process.platform': JSON.stringify(process.platform),
	})
)

webpackConfig.module.rules.push({
	test: /\.svg$/i,
	type: 'asset/source',
})

module.exports = webpackConfig
