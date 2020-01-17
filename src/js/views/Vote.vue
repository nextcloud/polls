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
			<div class="header-actions">
				<button class="button btn primary" @click="tableMode = !tableMode">
					<span>{{ t('polls', 'Switch view') }}</span>
				</button>
				<a href="#" class="icon icon-settings active"
					:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />
			</div>
			<PollTitle />
			<PollInformation />
			<PollDescription />
			<VoteList v-show="!isLoading && !tableMode && options.list.length" />
			<VoteTable v-show="!isLoading && tableMode && options.list.length" />
			<div v-if="!options.list.length" class="emptycontent">
				<div class="icon-toggle-filelist" />
				<p> {{ t('polls', 'There are no vote options, add some.') }}</p>
			</div>

			<Subscription />
			<div class="additional">
				<ParticipantsList v-if="acl.allowSeeUsernames" />
				<!-- <Comments /> -->
			</div>
		</div>

		<SideBar v-if="sideBarOpen" @closeSideBar="toggleSideBar" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
// import Comments from '../components/Comments/Comments'
import { AppContent } from '@nextcloud/vue'
import Subscription from '../components/Subscription/Subscription'
import ParticipantsList from '../components/Base/ParticipantsList'
import PollDescription from '../components/Base/PollDescription'
import PollInformation from '../components/Base/PollInformation'
import PollTitle from '../components/Base/PollTitle'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import SideBar from '../components/SideBar/SideBar'
import VoteList from '../components/VoteTable/VoteList'
import VoteTable from '../components/VoteTable/VoteTable'
import { mapState } from 'vuex'

export default {
	name: 'Vote',
	components: {
		AppContent,
		Subscription,
		ParticipantsList,
		PollDescription,
		PollInformation,
		PollTitle,
		LoadingOverlay,
		// Comments,
		SideBar,
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
			newName: '',
			tableMode: true
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl,
			options: state => state.options
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
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.$route.params.id })
				.then(() => {
					this.$store.dispatch({ type: 'loadPoll', pollId: this.$route.params.id })
						.then(() => {
							if (this.acl.allowEdit && moment.unix(this.poll.created).diff() > -10000) {
								this.sideBarOpen = true
							}
							this.isLoading = false
						})
				})
				.catch((error) => {
					console.error(error)
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
#emptycontent, .emptycontent {
	margin: 44px 0;
}

.additional {
	display: flex;
	flex-wrap: wrap;
	.participants {
		flex: 1;
	}
	.comments {
		flex: 3;
	}
}

.main-container {
	position: relative;
	flex: 1;
	padding: 8px 24px;
	margin: 0;
	flex-direction: column;
	flex-wrap: nowrap;
	overflow-x: scroll;
}

.header-actions {
	right: 0;
	position: absolute;
	display: flex;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>
