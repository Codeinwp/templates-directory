<template>
	<div>
		<Loader v-if="isLoading"></Loader>

		<div v-else class="ti-sites-lib">
			<div v-for="site in sites">
				<SiteItem :site_data="site"></SiteItem>
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
    components: {
      Loader,
      SiteItem,
      Preview,
    },
  }
</script>

<style></style>