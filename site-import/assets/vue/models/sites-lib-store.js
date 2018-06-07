// jshint ignore: start

/* global themeisleSitesLibApi */
/* exported themeisleSitesLibApi */
import Vue from 'vue'
import Vuex from 'vuex'
import VueResource from 'vue-resource'

Vue.use(Vuex)
Vue.use(VueResource)

export default new Vuex.Store({
  state: {
    ajaxLoader: false,
    sitesData: null,
    strings: themeisleSitesLibApi.i18ln,
  },
  mutations: {
    setAjaxState (state, data) {
      state.ajaxLoader = data
    },
    saveSitesData (state, data) {
      state.sitesData = data
    },
  },
  actions: {
    initializeLibrary ({commit}, data) {
      commit('setAjaxState', true)
      if (!themeisleSitesLibApi.cachedSitesJSON) {
        console.log('Refetching sites.')
        Vue.http({
          url: themeisleSitesLibApi.sitesJSON,
          method: 'GET',
          headers: {'X-WP-Nonce': themeisleSitesLibApi.nonce},
          params: {'req': data.req},
          body: data.data,
          responseType: 'json',
        }).then(function (response) {
          if (response.status === 200) {
            commit('setAjaxState', false)
            commit('saveSitesData', response.body )
          }
          let json = response.body
          Vue.http({
            url: themeisleSitesLibApi.root + '/save_fetched',
            method: 'POST',
            headers: {'X-WP-Nonce': themeisleSitesLibApi.nonce},
            params: {
              'req': data.req,
              'data': json,
            },
          })
        })
      } else {
        console.log('Loading from cache.')
        commit('setAjaxState', false)
        commit('saveSitesData', themeisleSitesLibApi.cachedSitesJSON)
      }
    },
  }
})