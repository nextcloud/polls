<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="action change-view">
		<NcButton :title="caption"
			:aria-label="caption"
			type="tertiary"
			@click="clickAction()">
			<template #icon>
				<ListViewIcon v-if="viewMode === 'table-view'" />
				<TableViewIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue' // view-sequential-outline
import TableViewIcon from 'vue-material-design-icons/Table.vue' // view-comfy-outline

export default {
	name: 'ActionChangeView',

	components: {
		ListViewIcon,
		TableViewIcon,
		NcButton,
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
			manualViewDatePoll: (state) => state.settings.manualViewDatePoll,
			manualViewTextPoll: (state) => state.settings.manualViewTextPoll,
		}),

		...mapGetters({
			viewMode: 'poll/viewMode',
			getNextViewMode: 'poll/getNextViewMode',
		}),

		caption() {
			if (this.viewMode === 'table-view') {
				return t('polls', 'Switch to list view')
			}
			return t('polls', 'Switch to table view')
		},

	},

	methods: {
		changeView() {
			if (this.pollType === 'datePoll') {
				this.$store.commit('settings/setViewDatePoll', this.manualViewDatePoll ? '' : this.getNextViewMode)
			} else if (this.pollType === 'textPoll') {
				this.$store.commit('settings/setViewTextPoll', this.manualViewTextPoll ? '' : this.getNextViewMode)
			}
		},

		clickAction() {
			emit('polls:transitions:off', 500)
			this.changeView()
		},
	},
}
</script>
