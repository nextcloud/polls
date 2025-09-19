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
import type { AvatarTypeIcon } from './UserAvatar.types.ts'
import AdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import ContactIcon from 'vue-material-design-icons/CardAccountDetailsOutline.vue'
import EmailIcon from 'vue-material-design-icons/EmailOutline.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import ContactGroupIcon from 'vue-material-design-icons/AccountGroupOutline.vue'
import GroupIcon from 'vue-material-design-icons/AccountMultipleOutline.vue'
import CircleIcon from 'vue-material-design-icons/GoogleCirclesExtended.vue'
import DeletedUserIcon from 'vue-material-design-icons/AccountOffOutline.vue'
import AnoymousIcon from 'vue-material-design-icons/Incognito.vue'
import PollGroupIcon from 'vue-material-design-icons/CodeBraces.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
const route = useRoute()

interface Props {
	mdIconSize?: number
	virtualUserType?: VirtualUserItemType
	typeIcon?: AvatarTypeIcon
	typeIconSize?: number
	user: User
}
const {
	mdIconSize = 20,
	virtualUserType,
	typeIcon = false,
	typeIconSize = 16,
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

const useIconSlot = computed(
	() =>
		(virtualUserType
			&& ['internalAccess', 'addPublicLink'].includes(virtualUserType))
		|| (user.type
			&& [
				'public',
				'contactGroup',
				'group',
				'circle',
				'deleted',
				'anonymous',
			].includes(user.type)),
)
</script>

<template>
	<NcAvatar
		v-bind="$attrs"
		:is-guest="computedIsGuest"
		:is-no-user="user.isNoUser"
		:display-name="user.displayName"
		:user="avatarUserId"
		class="user-item__avatar">
		<template v-if="useIconSlot" #icon>
			<LinkIcon v-if="user.type === 'public'" :size="mdIconSize" />
			<LinkIcon
				v-if="virtualUserType === 'addPublicLink'"
				:size="mdIconSize" />
			<AnoymousIcon v-if="user.type === 'anonymous'" :size="mdIconSize" />
			<OpenPollIcon
				v-if="virtualUserType === 'internalAccess'"
				:size="mdIconSize" />
			<ContactGroupIcon
				v-if="user.type === 'contactGroup'"
				:size="mdIconSize" />
			<GroupIcon v-if="user.type === 'group'" :size="mdIconSize" />
			<CircleIcon v-if="user.type === 'circle'" :size="mdIconSize" />
			<DeletedUserIcon v-if="user.type === 'deleted'" :size="mdIconSize" />
		</template>
	</NcAvatar>
	<AdminIcon
		v-if="typeIcon && user.type === 'admin'"
		:size="typeIconSize"
		class="type-icon" />
	<ContactIcon
		v-else-if="typeIcon && user.type === 'contact'"
		:size="typeIconSize"
		class="type-icon" />
	<EmailIcon
		v-else-if="typeIcon && user.type === 'email'"
		:size="typeIconSize"
		class="type-icon" />
	<ShareIcon
		v-else-if="typeIcon && user.type === 'external'"
		:size="typeIconSize"
		class="type-icon" />
	<PollGroupIcon
		v-else-if="typeIcon === 'pollGroupIcon'"
		:size="typeIconSize"
		class="type-icon" />
</template>
<style scoped lang="scss">
.type-icon {
	position: absolute;
	background-size: 16px;
	top: -6px;
	inset-inline-start: -6px;
}
</style>
