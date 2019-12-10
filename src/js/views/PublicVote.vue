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
		<div v-if="event.id > 0" class="main-container">
			<a v-if="!sideBarOpen" href="#" class="icon icon-settings active"
				:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />

			<VoteHeader />
			<VoteHeaderPublic />
			<VoteTable />
			<Notification />
		</div>

		<SideBar v-if="sideBarOpen" @closeSideBar="toggleSideBar" />
		<LoadingOverlay v-if="loading" />
	</AppContent>
</template>

<script>
import VoteHeader from '../components/VoteTable/VoteHeader'
import VoteHeaderPublic from '../components/VoteTable/VoteHeaderPublic'
import VoteTable from '../components/VoteTable/VoteTable'
import Notification from '../components/Notification/Notification'
import SideBar from '../components/SideBar/SideBar'
import { mapState } from 'vuex'

export default {
	name: 'Vote',
	components: {
		Notification,
		VoteHeader,
		VoteHeaderPublic,
		VoteTable,
		SideBar
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			initialTab: 'comments'
		}
	},

	computed: {
		...mapState({
			event: state => state.event,
			acl: state => state.acl
		}),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.event.title
		}

	},

	watch: {
		'$route'(to, from) {
			this.loadPoll()
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		loadPoll() {
			this.loading = false
			this.$store.dispatch('loadEvent', { token: this.$route.params.token })
				.then((response) => {
					this.$store.dispatch('loadPoll', { token: this.$route.params.token })
						.then(() => {
							this.loading = false
						})
				})
				.catch((error) => {
					console.error(error)
					this.loading = false
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
		margin: 0;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
		overflow-x: scroll;
		h1, h2, h3, h4 {
			margin-left: 24px;
		}
	}

	.icon.icon-settings.active {
		display: block;
		width: 44px;
		height: 44px;
		right: 0;
		position: absolute;
	}

</style>
