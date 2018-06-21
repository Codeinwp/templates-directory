<template>
	<div class="import-modal__wrapper">
		<div class="modal" v-on-clickaway="closeModal">
			<div class="modal__header">
				<h4 class="title ellipsis">{{this.$store.state.strings.import_btn}}: {{item.title}}</h4>
			</div>
			<hr>
			<div class="modal__content">
				<Loader v-if="importing"></Loader>
				<div v-else>
					<img :src="item.screenshot" :alt="item.title">
				</div>
			</div>
			<hr>
			<div class="modal__footer" v-if="! importing">
				<button class="button button-secondary" v-on:click="closeModal">
					{{this.$store.state.strings.cancel_btn}}
				</button>
				<button class="button button-primary" v-on:click="startImport">
					{{this.$store.state.strings.import_btn}}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
	import { directive as onClickaway } from 'vue-clickaway'
	import Loader from './loader.vue'
	export default {
		name: 'import-modal',
		computed: {
			item: function () {
				return this.$store.state.previewData
			},
			importing: function () {
				return this.$store.state.importing
			}
		},
		methods: {
			closeModal: function () {
				this.$store.commit( 'showImportModal', false )
			},
			startImport: function () {
				this.$store.dispatch( 'importSite', {
					req: 'Import Site',
					plugins: this.item.recommended_plugins,
					content: this.item.content_file,
					themeMods: {
						'theme_mods': this.item.theme_mods,
						'source_url': this.item.demo_url,
						'front_page': this.item.front_page
					}
				} )
			},
		},
		directives: {
			onClickaway,
		},
		components: {
			Loader
		}
	}
</script>

<style scoped>
	.modal__header .title {
		margin: 0;
	}

	.modal__header {
		padding: 10px;
	}

	.modal__content {
		padding: 10px;
	}

	.modal__footer {
		padding: 10px;
		text-align: right;
	}
</style>