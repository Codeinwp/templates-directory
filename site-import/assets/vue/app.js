/*jshint esversion: 6 */

import Vue from 'vue';
import App from './components/main.vue';
import store from './models/sites-lib-store.js';


window.onload = function () {
  const siteslibrary = new Vue({
    el: '#ti-sites-library',
    store,
    components: {
	    App
    },
    created () {
      store.dispatch( 'initializeLibrary', { req: 'Init Sites Library', data: {} });
    }
  });
};
