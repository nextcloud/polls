<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import AdminIcon from 'vue-material-design-icons/ShieldCrown.vue'
import LinkIcon from 'vue-material-design-icons/LinkVariant.vue'
import ContactIcon from 'vue-material-design-icons/CardAccountDetails.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import ContactGroupIcon from 'vue-material-design-icons/AccountGroupOutline.vue'
import GroupIcon from 'vue-material-design-icons/AccountMultiple.vue'
import CircleIcon from 'vue-material-design-icons/GoogleCirclesExtended.vue'
import DeletedUserIcon from 'vue-material-design-icons/AccountOff.vue'
import AnoymousIcon from 'vue-material-design-icons/Incognito.vue'
import PollGroupIcon from 'vue-material-design-icons/CodeBraces.vue'

import { User, UserType, VirtualUserItemType } from '../../Types/index.ts'

defineOptions({
	inheritAttrs: true,
})

interface Props {
	disabled?: boolean
	deletedState?: boolean
	lockedState?: boolean
	hideNames?: boolean
	showEmail?: boolean
	disableMenu?: boolean
	disableTooltip?: boolean
	resolveInfo?: boolean
	description?: string
	label?: string
	type?: UserType | VirtualUserItemType
	user?: User
	showTypeIcon?: boolean
	iconSize?: number
	mdIconSize?: number
	typeIconSize?: number
	hideStatus?: boolean
	condensed?: boolean
	delegatedFromGroup?: boolean
}

const {
	disabled = false,
	deletedState = false,
	lockedState = false,
	hideNames = false,
	showEmail = false,
	disableMenu = true,
	disableTooltip = false,
	resolveInfo = false,
	description,
	label = '',
	type = '',
	user = {
		id: '',
		displayName: '',
		emailAddress: '',
		isNoUser: true,
		isAdmin: false,
		type: '',
		subName: null,
		subtitle: null,
		desc: null,
		organisation: null,
		languageCode: '',
		localeCode: null,
		timeZone: null,
		categories: null,
	},
	showTypeIcon = false,
	iconSize = 32,
	mdIconSize = 20,
	typeIconSize = 16,
	hideStatus = false,
	condensed = false,
	delegatedFromGroup = false,
} = defineProps<Props>()

const route = useRoute()

const isGuestComputed = computed(() => route.name === 'publicVote' || user.isNoUser)
const avatarProps = computed(() => ({
	user: avatarUserId.value,
	showUserStatus: showUserStatusComputed.value,
	isGuest: isGuestComputed.value,
	displayName: labelComputed.value,
	size: iconSize,
	disableTooltip,
	disableMenu,
	isNoUser: user.isNoUser,
}))

const useIconSlot = computed(() =>
	[
		'internalAccess',
		'addPublicLink',
		'public',
		'contactGroup',
		'group',
		'circle',
		'deleted',
		'anonymous',
	].includes(typeComputed.value),
)

const typeComputed = computed<UserType | VirtualUserItemType>(
	() => user.type ?? type,
)
const descriptionComputed = computed(() => {
	if (condensed) {
		return ''
	}
	if (delegatedFromGroup) {
		return t('polls', 'Poll group access')
	}
	if (deletedState) {
		return t('polls', '(deleted)')
	}
	if (lockedState) {
		return t('polls', '(locked)')
	}
	if (description !== '') {
		return description
	}
	if (typeComputed.value === 'public') {
		return publicShareDescription
	}
	if (typeComputed.value === 'deleted') {
		return t('polls', 'The participant got removed from this poll')
	}
	if (typeComputed.value === 'admin') {
		return t('polls', 'Administrative rights granted')
	}
	if (typeComputed.value === 'anonymous') {
		return t('polls', 'Anonymized participant')
	}
	return emailAddressComputed
})
const labelComputed = computed(() => {
	if (label !== '') {
		return label
	}
	if (typeComputed.value === 'public') {
		return publicShareLabel.value
	}
	if (typeComputed.value === 'deleted') {
		return t('polls', 'Deleted participant')
	}
	return user.displayName ?? user.id
})

const avatarUserId = computed(() => {
	if (isGuestComputed.value) {
		return user.displayName
	}
	return user.id
})

const publicShareDescription = computed(() => {
	if (label === '') {
		return t('polls', 'Token: {token}', { token: user.id })
	}
	return t('polls', 'Public link: {token}', { token: user.id })
})

const publicShareLabel = computed(() => {
	if (label === '') {
		return t('polls', 'Public link')
	}
	return label
})

const emailAddressComputed = computed(() => {
	if (
		resolveInfo
		&& (typeComputed.value === 'contactGroup' || typeComputed.value === 'circle')
	) {
		return t('polls', 'Resolve this group first!')
	}

	if (
		showEmail
		&& user.emailAddress !== user.displayName
		&& (typeComputed.value === 'external' || typeComputed.value === 'email')
	) {
		return user.emailAddress
	}

	return ''
})
const showUserStatusComputed = computed(
	() => hideStatus && Boolean(getCurrentUser()),
)

/**
 *
 */
function showMenu() {
	// TODO: implement
	return true
}
const componentClass = computed(() => [
	'user-item',
	typeComputed,
	{
		disabled,
		condensed,
	},
])
</script>

<template>
	<div :class="componentClass">
		<div class="avatar-wrapper">
			<NcAvatar
				v-bind="avatarProps"
				class="user-item__avatar"
				@click="showMenu()">
				<template v-if="useIconSlot" #icon>
					<LinkIcon v-if="typeComputed === 'public'" :size="mdIconSize" />
					<LinkIcon
						v-if="typeComputed === 'addPublicLink'"
						:size="mdIconSize" />
					<AnoymousIcon
						v-if="typeComputed === 'anonymous'"
						:size="mdIconSize" />
					<LinkIcon
						v-if="typeComputed === 'internalAccess'"
						:size="mdIconSize" />
					<ContactGroupIcon
						v-if="typeComputed === 'contactGroup'"
						:size="mdIconSize" />
					<GroupIcon v-if="typeComputed === 'group'" :size="mdIconSize" />
					<CircleIcon
						v-if="typeComputed === 'circle'"
						:size="mdIconSize" />
					<DeletedUserIcon
						v-if="typeComputed === 'deleted'"
						:size="mdIconSize" />
				</template>
			</NcAvatar>

			<div v-if="$slots.menu" class="hover-menu">
				<slot name="menu" />
			</div>

			<AdminIcon
				v-if="showTypeIcon && typeComputed === 'admin'"
				:size="typeIconSize"
				class="type-icon" />
			<ContactIcon
				v-else-if="showTypeIcon && typeComputed === 'contact'"
				:size="typeIconSize"
				class="type-icon" />
			<EmailIcon
				v-else-if="showTypeIcon && typeComputed === 'email'"
				:size="typeIconSize"
				class="type-icon" />
			<ShareIcon
				v-else-if="showTypeIcon && typeComputed === 'external'"
				:size="typeIconSize"
				class="type-icon" />
			<PollGroupIcon
				v-else-if="showTypeIcon && delegatedFromGroup"
				:size="typeIconSize"
				class="type-icon" />
		</div>

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name">
				{{ labelComputed }}
			</div>
			<div class="description">
				{{ descriptionComputed }}
			</div>
		</div>

		<slot />
	</div>
</template>

<style lang="scss">
.avatar-wrapper {
	position: relative;
	display: flex;
	.type-icon {
		position: absolute;
		background-size: 16px;
		top: -6px;
		inset-inline-start: -6px;
	}
}

.user-item {
	position: relative;
	display: flex;
	align-items: center;
	justify-content: space-around;
	padding: 4px;
	max-width: 100%;
	&.disabled {
		opacity: 0.6;
	}
}

.hover-menu {
	position: absolute;
	top: 0;
	inset-inline-start: 0;
	opacity: 0;
	&:hover {
		opacity: 1;
	}
}

.user-item__avatar .material-design-icon {
	background-color: var(--color-primary-element);
	border-radius: 50%;
	color: var(--color-primary-element-text);
}

.user-item__name {
	flex: 1;
	min-width: 50px;
	padding-inline-start: 8px;
	white-space: nowrap;
	> div {
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.description {
		color: var(--color-text-maxcontrast);
		font-size: 0.7em;
	}
}

.condensed {
	&.user-item {
		flex-direction: column;
		justify-content: center;
		max-width: 70px;
	}
	.user-item__name {
		font-size: 0.7em;
		text-align: center;
		width: 70px;
		max-width: 70px;
		padding: 0 4px;
	}
}
</style>
