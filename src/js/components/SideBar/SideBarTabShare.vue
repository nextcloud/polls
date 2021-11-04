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
		<ConfigBox v-if="isOwner || allowAllAccess" :title="t('polls', 'Access')" icon-class="icon-category-auth">
			<ConfigAdminAccess v-if="isOwner && adminAccess" @change="writePoll" />
			<ConfigAccess v-if="allowAllAccess" @change="writePoll" />
		</ConfigBox>
		<SharesEffective />
		<SharesUnsent />
		<ConfigPublicPollsEmail />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import ConfigAccess from '../Configuration/ConfigAccess'
import ConfigAdminAccess from '../Configuration/ConfigAdminAccess'
import ConfigBox from '../Base/ConfigBox'
import SharesEffective from '../Shares/SharesEffective'
import SharesUnsent from '../Shares/SharesUnsent'
import { writePoll } from '../../mixins/writePoll'
import ConfigPublicPollsEmail from '../Configuration/ConfigPublicPollsEmail'

export default {
	name: 'SideBarTabShare',

	components: {
		ConfigBox,
		ConfigAccess,
		ConfigAdminAccess,
		SharesEffective,
		SharesUnsent,
		ConfigPublicPollsEmail,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			isOwner: (state) => state.poll.acl.isOwner,
			allowAllAccess: (state) => state.poll.acl.allowAllAccess,
			adminAccess: (state) => state.poll.adminAccess,
		}),

	},

}
</script>

<style lang="scss">
	.shared-list {
		display: flex;
		flex-flow: column;
		justify-content: flex-start;
		padding-top: 8px;
		max-height: 450px;
		overflow: auto;

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
