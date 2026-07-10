<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Share } from '../../stores/shares.types'
import type { User } from '../../Types'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import ShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
import NcModal from '../Base/modules/CustomNcModal.vue'
import QrModal from '../Base/modules/QrModal.vue'
import MarkDownDescription from '../Poll/MarkDownDescription.vue'
import UserSearch from '../User/UserSearch.vue'
import ShareItem from './ShareItem.vue'
import ShareItemAllUsers from './ShareItemAllUsers.vue'
import SharePublicAdd from './SharePublicAdd.vue'
import { usePollStore } from '../../stores/poll'
import { useSessionStore } from '../../stores/session'
import { useSharesStore } from '../../stores/shares'

const pollStore = usePollStore()
const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

const qrModal = ref(false)
const qrText = ref('')
const configBoxProps = {
	sharesList: {
		name: t('polls', 'Shares'),
	},
}

/**
 *
 * @param share
 */
function openQrModal(share: Share) {
	qrText.value = share.URL
	qrModal.value = true
}

async function addShare(user: User) {
	try {
		await sharesStore.add(user)
	} catch {
		showError(t('polls', 'Error while adding share'))
	}
}
</script>

<template>
	<ConfigBox v-bind="configBoxProps.sharesList">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch
			v-if="sessionStore.appPermissions.addShares"
			class="add-share"
			:aria-label="t('polls', 'Add shares')"
			:placeholder="t('polls', 'Type to add an individual share')"
			@userSelected="(user: User) => addShare(user)" />

		<!-- <template v-if="sharesStore.active.length"> -->
		<TransitionGroup tag="ul" name="list">
			<ShareItemAllUsers
				v-if="sessionStore.appPermissions.allAccess"
				tag="li" />

			<SharePublicAdd
				v-if="
					sessionStore.appPermissions.publicShares
					&& sessionStore.appPermissions.addShares
					&& sessionStore.appPermissions.addSharesExternal
				"
				tag="li" />

			<ShareItem
				v-for="share in sharesStore.active"
				:key="share.id"
				:share="share"
				tag="li"
				@showQrCode="openQrModal(share)" />
		</TransitionGroup>
		<!-- </template> -->

		<NcModal v-if="qrModal" size="small" @close="qrModal = false">
			<QrModal
				:name="pollStore.configuration.title"
				:description="pollStore.configuration.description"
				:encodeText="qrText"
				class="modal__content">
				<template #description>
					<MarkDownDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>
