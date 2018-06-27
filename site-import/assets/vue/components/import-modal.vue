<template>
	<div class="import-modal__wrapper">
		<div class="modal" v-on-clickaway="closeModal">
			<div class="modal__header">
				<h4 class="title ellipsis">{{strings.import_btn}}: {{item.title}}</h4>
			</div>
			<hr>
			<div class="modal__content">
				<template v-if="currentStep !== 'done'">
					<div class="left__content" v-if="! importing">
						<img :src="item.screenshot" :alt="item.title" class="screenshot">
					</div>
					<div class="right__content" v-if="! importing"><p>{{strings.import_description}}</p></div>
					<div class="right__content importing" v-else>
						<Stepper>
						</Stepper>
						<Loader v-if="importing">
						</Loader>
					</div>
				</template>
				<template v-else>
					<h3>{{strings.import_done}}</h3>
				</template>
			</div>
			<hr>
			<div class="modal__footer" v-if="! importing && currentStep !== 'done'">
				<button class="button button-secondary" v-on:click="closeModal">
					{{strings.cancel_btn}}
				</button>
				<button class="button button-primary" v-on:click="startImport">
					{{strings.import_btn}}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
	import { directive as onClickaway } from 'vue-clickaway'
	import Stepper from './stepper.vue'
	import Loader from './loader.vue'

	export default {
		name: 'import-modal',
		data: function() {
			return {
				strings: this.$store.state.strings
			}
		},
		computed: {
			item: function () {
				return this.$store.state.previewData
			},
			importing: function () {
				return this.$store.state.importing
			},
			currentStep: function () {
				return this.$store.state.currentStep;
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
					content: {
						'content_file': this.item.content_file,
						'front_page': this.item.front_page
					},
					themeMods: {
						'theme_mods': this.item.theme_mods,
						'source_url': this.item.demo_url
					},
					widgets: this.item.widgets
				} )
			},
		},
		directives: {
			onClickaway,
		},
		components: {
			Stepper,
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

	h3 {
		text-align: center;
		font-size: 17px;
		font-weight: 500;
		margin: 20px;
		width: 100%;
	}
	.importing {
		width: 100%;
		text-align: center;
	}
</style>