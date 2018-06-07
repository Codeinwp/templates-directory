<template>
	<div class="site-box">
		{{site_data}}

		<div class="header">
		<img :src="site_data.screenshot" alt="">
		</div>
		<div class="footer">
			<h4>{{site_data.title}}</h4>
			<button v-on:click="this.setupModal"  >{{this.$store.state.strings.preview_btn}}</button>
		</div>
	</div>
</template>

<script>
  export default {
    name: 'site-item',
    props: {
      site_data: {
        default: {},
        type: Object,
      },
    },
    data () {
      return {
      }
    },
    methods: {
      setupModal: function() {
        console.log('click');
      }
    }
  }
</script>

<style scoped>

</style>