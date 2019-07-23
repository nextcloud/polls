<template lang="html">
	<div>
		<button class="icon-history" @click="shiftDatesDlg()"> {{ t('polls', 'Shift dates') }} </button>

		<modal-dialog>
			<div>
				<div class="selectUnit">
					<input v-model="move.step">
					<Multiselect v-model="move.unit" :options="move.units" />
				</div>
			</div>
		</modal-dialog>
	</div>
</template>

<script>
	import { Multiselect } from 'nextcloud-vue'
	import { mapMutations } from 'vuex'

	export default {
		name: 'ShiftDates',
		components: {
			Multiselect,
		},

		data() {
			return {
				move: {
					step: 1,
					unit: 'week',
					units: ['minute', 'hour', 'day', 'week', 'month', 'year'],
				},
			}
		},

		methods: {
			...mapMutations(['shiftDates']),

			shiftDatesDlg() {
				const params = {
					title: t('polls', 'Shift all date options'),
					text: t('polls', 'Shift all dates for '),
					buttonHideText: t('polls', 'Cancel'),
					buttonConfirmText: t('polls', 'Apply'),
					onConfirm: () => {
						this.shiftDates(this.move)
					},
				}
				this.$modal.show(params)
			},
		},
	}
</script>

<style lang="scss" scoped>
	.selectUnit {
		display: flex;
		align-items: center;
		flex-wrap: nowrap;
		> label {
			padding-right: 4px;
		}
	}
</style>
