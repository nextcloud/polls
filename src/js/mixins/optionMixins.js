export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('options/confirm', { option })
		},
	},
}

export const removeOption = {
	methods: {
		removeOption(option) {
			this.$store.dispatch('options/delete', { option })
		},
	},
}
