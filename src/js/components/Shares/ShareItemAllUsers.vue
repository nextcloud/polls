<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<UserItem v-bind="userItemProps">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcCheckboxRadioSwitch :checked.sync="pollAccess" type="switch" />
	</UserItem>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writePoll } from '../../mixins/writePoll.js'

export default {
	name: 'ShareItemAllUsers',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			access: (state) => state.poll.configuration.access,
		}),

		userItemProps() {
			return {
				label: t('polls', 'Internal access'),
				type: 'internalAccess',
				disabled: this.access === 'private',
				description: this.access === 'private' ? t('polls', 'This poll is private') : t('polls', 'This is an openly accessible poll'),
			}
		},

		pollAccess: {
			get() {
				return this.access === 'open'
			},
			set(value) {
				this.$store.commit('poll/setProperty', { access: value ? 'open' : 'private' })
				this.writePoll()
			},
		},
	},
}
</script>
