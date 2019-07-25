<template lang="html">
	<div v-if="loggedIn" class="notification">
		<input id="subscribe" v-model="subscribe" type="checkbox" class="checkbox" />
		<label for="subscribe">{{ t('polls', 'Receive notification email on activity') }}</label>
	</div>
</template>

<script>
	import { mapState } from 'vuex'
	export default {
		name: 'Notication',

		computed: {
			...mapState({
				notification: state => state.notification
			}),

			loggedIn() {
				return (!OC.currentUser !== '')
			},

			subscribe: {
				get() {
					return this.notification.subscribed
				},
				set(value) {
					this.$store.commit('setNotification', value)
				},
			},
		},
	}
</script>

<style lang="css" scoped>
</style>
