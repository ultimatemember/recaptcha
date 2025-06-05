/*
  Ultimate Member - reCAPTCHA dependencies
*/

const { src, dest, parallel } = require( 'gulp' );
const sass        = require( 'gulp-sass' )( require( 'sass' ) );
const uglify      = require( 'gulp-uglify' );
const cleanCSS    = require( 'gulp-clean-css' );
const rename      = require( 'gulp-rename' );
const gulp = require("gulp");

function defaultTask( done ) {
	// sass.compiler = require( 'node-sass' );

	// -------- v2 assets ---------
	src(['assets/js/*.js','!assets/js/*.min.js'])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/js/' ) );

	// full CSS files
	src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/css/' ) );
	// min CSS files
	src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/' ) );

	// -------- v3 assets ---------
	src(['assets/js/v3/*.js','!assets/js/v3/*.min.js'])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/js/v3/' ) );

	// full CSS files
	src(['assets/css/v3/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/css/v3/' ) );
	// min CSS files
	src(['assets/css/v3/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/v3/' ) );

	done();
}
exports.default = defaultTask;
