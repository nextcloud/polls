export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('options/confirm', { option: option })
		},
	},
}

export const removeOption = {
	methods: {
		removeOption(option) {
			this.$store.dispatch('options/delete', { option: option })
		},
	},
}
