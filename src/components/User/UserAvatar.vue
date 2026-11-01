<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { createDefault } from '../../Types'
import type { User, VirtualUserItemType } from '../../Types'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import ContactGroupIcon from 'vue-material-design-icons/AccountGroupOutline.vue'
import GroupIcon from 'vue-material-design-icons/AccountMultipleOutline.vue'
import CircleIcon from 'vue-material-design-icons/GoogleCirclesExtended.vue'
import DeletedUserIcon from 'vue-material-design-icons/AccountOffOutline.vue'
import AnoymousIcon from 'vue-material-design-icons/Incognito.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
const route = useRoute()

interface Props {
	mdIconSize?: number
	virtualUserType?: VirtualUserItemType
	user: User
}
const {
	mdIconSize = 20,
	virtualUserType,
	user = createDefault<User>(),
} = defineProps<Props>()

const computedIsGuest = computed(
	() => route.name === 'publicVote' || user.isNoUser !== false,
)

const avatarUserId = computed(() => {
	if (virtualUserType || user.isNoUser) {
		return user.displayName
	}
	return user.id
})

const iconTypeComponent = computed(() => {
	if (virtualUserType) {
		switch (virtualUserType) {
			case 'addPublicLink':
				return LinkIcon
			case 'internalAccess':
				return OpenPollIcon
			default:
				return null
		}
	} else {
		switch (user.type) {
			case 'public':
				return LinkIcon
			case 'anonymous':
				return AnoymousIcon
			case 'contactGroup':
				return ContactGroupIcon
			case 'group':
				return GroupIcon
			case 'circle':
				return CircleIcon
			case 'deleted':
				return DeletedUserIcon
			default:
				return null
		}
	}
})
</script>

<template>
	<NcAvatar
		v-bind="$attrs"
		:is-guest="computedIsGuest"
		:is-no-user="user.isNoUser"
		:display-name="user.displayName"
		:user="avatarUserId"
		class="user-item__avatar">
		<template v-if="iconTypeComponent" #icon>
			<component :is="iconTypeComponent" :size="mdIconSize" />
		</template>
	</NcAvatar>
</template>
