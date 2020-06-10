export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('poll/options/update', { option: { ...option, confirmed: !option.confirmed } })
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
