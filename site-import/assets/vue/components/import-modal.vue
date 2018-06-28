<template>
	<div class="import-modal__wrapper">
		<div class="modal__item" v-on-clickaway="closeModal">
			<div class="modal__header">
				<h4 class="title ellipsis">{{strings.import_btn}}: {{item.title}}</h4>
			</div>
			<hr>
			<div class="modal__content" v-bind:class="currentStep === 'done' ? 'import__done' : ''">
				<template v-if="currentStep !== 'done'">
					<div class="left__content" v-if="! importing">
						<img :src="item.screenshot" :alt="item.title" class="screenshot">
					</div>
					<div class="right__content" v-if="! importing">
						<p class="import__disclaimer"><strong>{{strings.note}}:</strong> {{strings.import_disclaimer}}
						</p>
						<p class="import__description">{{strings.import_description}}</p>
					</div>
					<div class="right__content importing" v-else>
						<Stepper>
						</Stepper>
						<Loader v-if="importing">
						</Loader>
					</div>
				</template>
				<h3 v-else>{{strings.import_done}}</h3>
			</div>
			<hr>
			<div class="modal__footer" v-if="! importing">
				<template v-if="currentStep !== 'done'">
					<button class="button button-secondary" v-on:click="closeModal">{{strings.cancel_btn}}</button>
					<button class="button button-primary" v-on:click="startImport">{{strings.import_btn}}</button>
				</template>
				<div v-else class="after__actions">
					<button class="button button-secondary" v-on:click="resetImport">{{strings.back}}</button>
					<button class="button button-primary" v-on:click="redirectToHome">{{strings.go_to_site}}</button>
				</div>
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
		data: function () {
			return {
				strings: this.$store.state.strings,
				homeUrl: this.$store.state.homeUrl
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
				return this.$store.state.currentStep
			}
		},
		methods: {
			closeModal: function () {
				if ( this.importing ) {
					return false
				}
				if ( this.currentStep === 'done' ) {
					return false
				}
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
			redirectToHome: function () {
				window.location.replace( this.homeUrl );
			},
			resetImport: function () {
				this.$store.commit( 'showImportModal', false );
				this.$store.commit( 'showPreview', false );
				this.$store.commit( 'populatePreview', {} );
				this.$store.commit( 'updateSteps', 'inactive' );
			}
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
		font-size: 17px;
		font-weight: 300;
		color: #444;
		margin: 20px;
		width: 100%;
	}

	.importing {
		width: 100%;
		text-align: center;
	}
</style>