<template>
	<div class="wrap">
		<Loader v-if="isLoading"></Loader>
		<div v-else class="sites-library">
			<div v-for="site in sites">
				<SiteItem :site_data="site"></SiteItem>
			</div>
			<ImportModal v-if="modalShown"></ImportModal>
		</div>

	</div>
</template>

<script>
  import Loader from './loader.vue'
  import SiteItem from './site-item.vue'
  import ImportModal from './import-modal.vue'

  module.exports = {
    name: 'app',
    data () {
      return {
      }
    },
    computed: {
      isLoading: function () {
        return this.$store.state.ajaxLoader;
      },
      sites: function () {
        return this.$store.state.sitesData;
      },
      modalShown: function () {
        return this.$store.state.modalShown;
      }
    },
    components: {
      Loader,
      SiteItem,
      ImportModal
    },
  }
</script>

<style></style>