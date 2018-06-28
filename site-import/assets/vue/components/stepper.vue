<template>
	<div class="ti__stepper">
		<template v-for="(step, slug) in steps">
			<p :key="step.id" v-if="slug === currentStep">
				{{ step }}...
			</p>
		</template>
	</div>
</template>

<script>
	module.exports = {
		name: 'stepper',
		data: function () {
			return {
				steps: this.$store.state.strings.import_steps
			}
		},
		computed: {
			currentStep: function () {
				return this.$store.state.currentStep;
			}
		}
	}
</script>

<style scoped>
	.right__content.importing .ti__stepper p {
		color: #00b2f9;
		text-transform: uppercase;
		font-weight: 600;
	}
</style>