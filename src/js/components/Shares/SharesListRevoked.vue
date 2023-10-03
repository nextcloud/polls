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
	<ConfigBox v-if="revokedShares.length" :title="t('polls', 'Revoked shares')">
		<template #icon>
			<DeletedIcon />
		</template>
		<TransitionGroup :css="false" tag="div" class="shares-list">
			<ShareItem v-for="(share) in revokedShares"
				:key="share.id"
				:share="share" />
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions, mapState } from 'vuex'
import { ConfigBox } from '../Base/index.js'
import DeletedIcon from 'vue-material-design-icons/Delete.vue'
import ShareItem from './ShareItem.vue'

export default {
	name: 'SharesListRevoked',

	components: {
		DeletedIcon,
		ConfigBox,
		ShareItem,
	},

	computed: {
		...mapState({
			pollId: (state) => state.poll.id,
		}),

		...mapGetters({
			revokedShares: 'shares/revoked',
		}),
	},

	methods: {
		...mapActions({
			removeShare: 'shares/delete',
			inviteAll: 'shares/inviteAll',
		}),
	},
}
</script>
