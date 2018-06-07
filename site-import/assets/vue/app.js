import Vue from 'vue';

import App from './components/main.vue';

window.onload = function () {
  var siteslibrary = new Vue({
    el: '#ti-sites-library',
    components: {
	    App
    },
    created () {}
  });
};
