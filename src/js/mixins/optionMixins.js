export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('poll/options/confirm', { option: option })
		},
	},
}

export const removeOption = {
	methods: {
		removeOption(option) {
			this.$store.dispatch('poll/options/delete', { option: option })
		},
	},
}
