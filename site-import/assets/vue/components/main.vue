<template>
	<div>
		<Loader v-if="isLoading" :loading-message="strings.loading"></Loader>
		<div v-else class="__sites">
		<div class="ti-sites-lib">
			<div v-for="site in sites">
				<SiteItem :site_data="site"></SiteItem>
			</div>
		</div>
		<div class="site-import__footer">
			<button class="button button-secondary" v-on:click="refreshSites">{{ strings.refresh }}</button>
		</div>
			<Preview v-if="previewOpen"></Preview>
		</div>
	</div>
</template>

<script>
	import Loader from './loader.vue'
	import SiteItem from './site-item.vue'
	import Preview from './preview.vue'

	module.exports = {
		name: 'app',
		data: function () {
			return {
				strings: this.$store.state.strings,
			}
		},
		computed: {
			isLoading: function () {
				return this.$store.state.ajaxLoader
			},
			sites: function () {
				return this.$store.state.sitesData
			},
			previewOpen: function () {
				return this.$store.state.previewOpen
			},
		},
		methods: {
			refreshSites() {
				this.$store.dispatch( 'bustCache', { req: 'Bust Cache', data: {} } );
			}
		},
		components: {
			Loader,
			SiteItem,
			Preview,
		},
	}
</script>

<style></style>