/* jshint esversion: 6 */
/* global themeisleSitesLibApi, console */
import Vue from 'vue';
import VueResource from 'vue-resource';

Vue.use( VueResource );

const initialize = function ( { commit }, data ) {
	commit( 'setAjaxState', true );
	console.log( 'Fetching sites.' );
	Vue.http( {
		url: themeisleSitesLibApi.root + '/initialize_sites_library',
		method: 'GET',
		headers: { 'X-WP-Nonce': themeisleSitesLibApi.nonce },
		params: { 'req': data.req },
		body: data.data,
		responseType: 'json'
	} ).then( function ( response ) {
		if ( response.ok ) {
			commit( 'setAjaxState', false );
			commit( 'saveSitesData', response.body );
		}
	} );
};

const importSite = function ( { commit }, data ) {
	startImport( { commit }, data );
};

const startImport = function ( { commit }, data ) {
	commit( 'setImportingState', true );
	installPlugins( { commit }, data );
};

const doneImport = function ( { commit } ) {
	commit( 'setImportingState', false );
	console.log( 'Import Done' );
};

const installPlugins = function ( { commit }, data ) {
	Vue.http( {
		url: themeisleSitesLibApi.root + '/install_plugins',
		method: 'POST',
		headers: { 'X-WP-Nonce': themeisleSitesLibApi.nonce },
		params: {
			'req': data.req,
		},
		body: {
			'data': data.plugins,
		},
		responseType: 'json',
	} ).then( function ( response ) {
		if( response.ok ) {
			console.log( 'Installed Plugins.' );
			importContent( { commit }, data );
		} else {
			console.error( response );
		}
	} );
};

const importContent = function ( { commit }, data ) {
	Vue.http( {
		url: themeisleSitesLibApi.root + '/import_content',
		method: 'POST',
		headers: { 'X-WP-Nonce': themeisleSitesLibApi.nonce },
		params: {
			'req': data.req,
		},
		body: {
			'data': data.content,
		},
		responseType: 'json',
	} ).then( function ( response ) {
		if( response.ok ) {
			console.log( 'Imported Content.' );
			importThemeMods( { commit }, data );
		} else {
			console.error( response );
		}
	} );
};

const importThemeMods = function ( { commit }, data ) {
	Vue.http( {
		url: themeisleSitesLibApi.root + '/import_theme_mods',
		method: 'POST',
		headers: { 'X-WP-Nonce': themeisleSitesLibApi.nonce },
		params: {
			'req': data.req,
		},
		body: {
			'data': data.themeMods,
		},
		responseType: 'json',
	} ).then( function ( response ) {
		if( response.ok ) {
			doneImport( { commit } );
		} else {
			console.error( response );
		}
	} );
};

export default {
	initialize,
	importSite
};