<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ConfigBox :name="t('polls', 'Shares')">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch v-if="appPermissions.shareCreate" class="add-share" />
		<ShareItemAllUsers v-if="appPermissions.allAccess" />
		<SharePublicAdd v-if="appPermissions.publicShares && appPermissions.shareCreate && appPermissions.shareCreateExternal" />

		<div v-if="activeShares.length" class="shares-list shared">
			<TransitionGroup is="div"
				name="list"
				:css="false">
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
			appPermissions: (state) => state.acl.appPermissions,
			pollTitle: (state) => state.poll.configuration.title,
			pollDescription: (state) => state.poll.configuration.description,
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
