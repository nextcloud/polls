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
	<div>
		<ConfigBox v-if="!isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />
		<SharesEffective />
		<ConfigBox :title="t('polls', 'Add Shares')" icon-class="icon-add">
			<UserSearch />
		</ConfigBox>
		<SharesPublic />
		<SharesUnsent />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import UserSearch from '../User/UserSearch'
import SharesEffective from '../Shares/SharesEffective'
import SharesPublic from '../Shares/SharesPublic'
import SharesUnsent from '../Shares/SharesUnsent'

export default {
	name: 'SideBarTabShare',

	components: {
		ConfigBox,
		UserSearch,
		SharesPublic,
		SharesEffective,
		SharesUnsent,
	},

	computed: {
		...mapState({
			isOwner: state => state.poll.acl.isOwner,
		}),
	},

	methods: {
		removeShare(share) {
			this.$store.dispatch('shares/delete', { share })
		},
	},
}
</script>

<style lang="scss">
	.shared-list {
		display: flex;
		flex-wrap: wrap;
		flex-direction: column;
		justify-content: flex-start;
		padding-top: 8px;

		> li {
			display: flex;
			align-items: stretch;
			margin: 4px 0;
		}
	}

	.share-item {
		display: flex;
		flex: 1;
		align-items: center;
		max-width: 100%;
	}

	.share-item__description {
		flex: 1;
		min-width: 50px;
		color: var(--color-text-maxcontrast);
		padding-left: 8px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
</style>
