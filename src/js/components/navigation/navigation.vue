<template lang="html">
	<app-navigation>
		<app-navigation-new :text="t('polls', 'New')" />
		<app-content-details
			v-for="(poll) in pollList"
			:key="poll.id">
			<router-link
				:to="{name: 'vote', params: {id: poll.id}}"
				class="thumbnail"
				:class="eventIcon(poll.event.type)">
				<div class="name">
					{{ poll.event.title }}
				</div>
				<div class="description">
					{{ poll.event.description }}
				</div>
			</router-link>
		</app-content-details>
	</app-navigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppContentDetails } from 'nextcloud-vue'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppContentDetails
	},

	computed: {
		pollList() {
			return this.$store.state.polls.list
		}
	},

	created() {
		this.refreshPolls()
	},

	methods: {
		eventIcon(type) {
			if (type === '0') {
				return 'datePoll'
			} else {
				return 'textPoll'
			}
		},

		refreshPolls() {
			this.loading = true

			this.$store
				.dispatch('loadPolls')
				.then(response => {
					this.loading = false
				})
				.catch(error => {
					this.loading = false
					console.error('refresh poll: ', error.response)
					OC.Notification.showTemporary(t('polls', 'Error loading polls"', 1, event.title, { type: 'error' }))
				})

		}
	}
}
</script>

<style lang="scss" scoped>

.name,
.description {
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}
.description {
	opacity: 0.5;
}

.app-content-details {
	padding: 4px 0 4px 0;
}

a.active, a:hover {
	box-shadow: var(--color-primary-element) 4px 0px inset;
	opacity: 1;
}

.thumbnail {
	background-size: 16px 16px;
	background-position: 14px center;
	background-repeat: no-repeat;
	display: block;
	justify-content: space-between;
	// line-height: 44px;
	min-height: 44px;
	padding: 0 12px 0 44px;
	overflow: hidden;
	box-sizing: border-box;
	white-space: nowrap;
	text-overflow: ellipsis;
	color: var(--color-main-text);
	opacity: 0.57;
	// flex: 1 1 0px;
	// z-index: 100;

	&.datePoll {
		background-image: var(--icon-calendar-000);
		// mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
		// -webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
		// mask-size: 16px;
	}
	&.textPoll {
		background-image: var(--icon-organization-000);
		// mask-image: var(--icon-organization-000) no-repeat 50% 50%;
		// -webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
		// mask-size: 16px;
	}
	&.expired {
		background-color: var(--color-background-darker);
	}
}

</style>
