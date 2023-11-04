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
	<ConfigBox :name="t('polls', 'Shares')">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch class="add-share" />
		<ShareItemAllUsers v-if="allowAllAccess" />
		<SharePublicAdd v-if="allowPublicShares" />

		<div v-if="activeShares.length" class="shares-list shared">
			<TransitionGroup :css="false" tag="div">
				<ShareItem v-for="(share) in activeShares"
					:key="share.id"
					:share="share"
					@show-qr-code="openQrModal(share)" />
			</TransitionGroup>
		</div>

		<NcModal v-if="qrModal" size="small" @close="qrModal=false">
			<QrModal :name="pollTitle"
				:description="pollDescription"
				:encode-text="qrText"
				class="modal__content">
				<template #description>
					<MarkUpDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { NcModal } from '@nextcloud/vue'
import { ConfigBox, QrModal } from '../Base/index.js'
import ShareItem from './ShareItem.vue'
import UserSearch from '../User/UserSearch.vue'
import SharePublicAdd from './SharePublicAdd.vue'
import ShareItemAllUsers from './ShareItemAllUsers.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import MarkUpDescription from '../Poll/MarkUpDescription.vue'

export default {
	name: 'SharesList',

	components: {
		ShareIcon,
		UserSearch,
		ConfigBox,
		SharePublicAdd,
		ShareItemAllUsers,
		ShareItem,
		QrModal,
		NcModal,
		MarkUpDescription,
	},

	data() {
		return {
			qrModal: false,
			qrText: '',
		}
	},

	computed: {
		...mapState({
			allowAllAccess: (state) => state.poll.acl.allowAllAccess,
			allowPublicShares: (state) => state.poll.acl.allowPublicShares,
			pollAccess: (state) => state.poll.access,
			pollTitle: (state) => state.poll.title,
			pollDescription: (state) => state.poll.description,
		}),
		...mapGetters({
			activeShares: 'shares/active',
		}),
	},

	methods: {
		openQrModal(share) {
			this.qrText = share.URL
			this.qrModal = true
		},
	},
}
</script>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}

</style>
