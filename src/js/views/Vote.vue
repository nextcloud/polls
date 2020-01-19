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
			<a v-if="!sideBarOpen && acl.allowEdit" href="#" class="icon icon-settings active"
				:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />
			<PollTitle />
			<PollInformation />
			<PollDescription />
			<button class="button btn primary" @click="tableMode = !tableMode">
				<span>{{ t('polls', 'Switch view') }}</span>
			</button>
			<VoteList v-show="!isLoading && !tableMode" />
			<VoteTable v-show="!isLoading && tableMode" />
			<Subscription />
			<ParticipantsList />
			<Comments />
		</div>

		<SideBar v-if="sideBarOpen && acl.allowEdit" @closeSideBar="toggleSideBar" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import Comments from '../components/Comments/Comments'
import Subscription from '../components/Subscription/Subscription'
import ParticipantsList from '../components/Base/ParticipantsList'
import PollDescription from '../components/Base/PollDescription'
import PollInformation from '../components/Base/PollInformation'
import PollTitle from '../components/Base/PollTitle'
import SideBar from '../components/SideBar/SideBar'
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
		Subscription,
		VoteTable,
		VoteList,
		Comments,
		SideBar
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			isLoading: false,
			initialTab: 'comments',
			newName: '',
			tableMode: true
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		})
	},

	watch: {
		$route() {
			this.loadPoll()
		},

		'poll.title': function() {
			document.title = t('polls', 'Polls') + ' - ' + this.poll.title
		},

		'poll.id': function() {
			this.$store.dispatch({ type: 'loadAcl', pollId: this.$route.params.id })
				.then(() => {
					this.$store.dispatch({ type: 'loadPoll', pollId: this.$route.params.id })
						.then(() => {
							if (this.acl.allowEdit && moment.unix(this.poll.created).diff() > -10000) {
								this.sideBarOpen = true
							}
							this.isLoading = false
						})
				})
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		loadPoll() {
			this.isLoading = false
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.$route.params.id })
				.then(() => {
				})
				.catch(() => {
					this.isLoading = false
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
