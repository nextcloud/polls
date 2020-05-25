export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('updateOptionAsync', { option: { ...option, confirmed: !option.confirmed } })
		},
	},
}

export const removeOption = {
	removeOption(option) {
		this.$store.dispatch('removeOptionAsync', {
			option: option,
		})
	},
}
