<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
	<AppContent>
		<div v-if="poll.id > 0" class="main-container">
			<PollTitle />
			<PollInformation />
			<PollDescription />
			<VoteHeaderPublic />
			<button class="button btn primary" @click="tableMode = !tableMode">
				<span>{{ t('polls', 'Switch view') }}</span>
			</button>
			<VoteList v-show="!isLoading && !tableMode" />
			<VoteTable v-show="!isLoading && tableMode" />
			<ParticipantsList />
			<Comments />
		</div>

		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import Comments from '../components/Comments/Comments'
import ParticipantsList from '../components/Base/ParticipantsList'
import PollDescription from '../components/Base/PollDescription'
import PollInformation from '../components/Base/PollInformation'
import PollTitle from '../components/Base/PollTitle'
import VoteHeaderPublic from '../components/VoteTable/VoteHeaderPublic'
import VoteList from '../components/VoteTable/VoteList'
import VoteTable from '../components/VoteTable/VoteTable'
import { mapState } from 'vuex'

export default {
	name: 'Vote',
	components: {
		ParticipantsList,
		PollInformation,
		PollTitle,
		PollDescription,
		VoteHeaderPublic,
		Comments,
		VoteTable,
		VoteList
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			isLoading: false,
			initialTab: 'comments',
			tableMode: true
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll
		}),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		}

	},

	watch: {
		$route() {
			this.loadPoll()
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		loadPoll() {
			this.isLoading = false
			this.$store.dispatch('loadPollMain', { token: this.$route.params.token })
				.then(() => {
					this.$store.dispatch({ type: 'loadAcl', token: this.$route.params.token })
						.then(() => {
							this.$store.dispatch('loadPoll', { token: this.$route.params.token })
								.then(() => {
									this.isLoading = false
								})
						})
						.catch((error) => {
							console.error(error)
							this.isLoading = false
						})
				})
		},

		toggleSideBar() {
			this.sideBarOpen = !this.sideBarOpen
		}

	}
}
</script>

<style lang="scss" scoped>
.main-container {
	flex: 1;
	padding: 0 24px;
	margin: 0;
	flex-direction: column;
	flex-wrap: nowrap;
	overflow-x: scroll;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
	right: 0;
	position: absolute;
}

</style>
