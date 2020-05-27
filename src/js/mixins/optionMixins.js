export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('options/updateSingle', { option: { ...option, confirmed: !option.confirmed } })
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
