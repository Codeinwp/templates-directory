// jshint ignore: start

/* global themeisleSitesLibApi */
/* exported themeisleSitesLibApi */
import Vue from 'vue'
import Vuex from 'vuex'
import VueResource from 'vue-resource'

Vue.use( Vuex )
Vue.use( VueResource )

export default new Vuex.Store( {
	state: {
		ajaxLoader: false,
		sitesData: null,
		previewOpen: false,
		importModalState: false,
		previewData: {},
		strings: themeisleSitesLibApi.i18ln
	},
	data: {
		promise: null
	},
	mutations: {
		setAjaxState( state, data ) {
			state.ajaxLoader = data;
		},
		saveSitesData( state, data ) {
			state.sitesData = data;
		},
		showPreview( state, data ) {
			state.previewOpen = data;
		},
		showImportModal( state, data ) {
			state.importModalState = data;
		},
		populatePreview( state, data ) {
			state.previewData = data;
		}
	},
	actions: {
		initializeLibrary( { commit }, data ) {
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
		},
		importSite( { commit }, data ) {
			commit( 'setAjaxState', true );
			console.log( data );
			Vue.http( {
				url: themeisleSitesLibApi.root + '/install_plugins',
				method: 'POST',
				headers: { 'X-WP-Nonce': themeisleSitesLibApi.nonce },
				params: {
					'req': data.req,
				},
				body: {
					'data': data.content,
				},
				responseType: 'json',
			} ).then( function () {
				console.log( 'plugins installed.' )
				return false;
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
					commit( 'setAjaxState', false )
					console.log( 'imported content.' )
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
						console.log( 'imported theme mods.' )
						commit( 'setAjaxState', false )
						console.log( response )
					} )
				} )
			} )
		},
	},
} )
