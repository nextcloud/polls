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
	type = UserType.None,
	user = {
		id: '',
		displayName: '',
		emailAddress: '',
		isNoUser: true,
		isAdmin: false,
		type: UserType.None,
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
		VirtualUserItemType.InternalAccess,
		VirtualUserItemType.AddPublicLink,
		UserType.Public,
		UserType.ContactGroup,
		UserType.Group,
		UserType.Circle,
		VirtualUserItemType.Deleted,
		VirtualUserItemType.Anonymous,
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
	if (typeComputed.value === UserType.Public) {
		return publicShareDescription
	}
	if (typeComputed.value === VirtualUserItemType.Deleted) {
		return t('polls', 'The participant got removed from this poll')
	}
	if (typeComputed.value === UserType.Admin) {
		return t('polls', 'Administrative rights granted')
	}
	if (typeComputed.value === VirtualUserItemType.Anonymous) {
		return t('polls', 'Anonymized participant')
	}
	return emailAddressComputed
})
const labelComputed = computed(() => {
	if (label !== '') {
		return label
	}
	if (typeComputed.value === UserType.Public) {
		return publicShareLabel.value
	}
	if (typeComputed.value === VirtualUserItemType.Deleted) {
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
		&& (typeComputed.value === UserType.ContactGroup
			|| typeComputed.value === UserType.Circle)
	) {
		return t('polls', 'Resolve this group first!')
	}

	if (
		showEmail
		&& user.emailAddress !== user.displayName
		&& (typeComputed.value === UserType.External
			|| typeComputed.value === UserType.Email)
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
</script>

<template>
	<div
		:class="[
			'user-item',
			typeComputed,
			{
				disabled,
				condensed: condensed,
			},
		]">
		<div class="avatar-wrapper">
			<NcAvatar
				v-bind="avatarProps"
				class="user-item__avatar"
				@click="showMenu()">
				<template v-if="useIconSlot" #icon>
					<LinkIcon
						v-if="typeComputed === UserType.Public"
						:size="mdIconSize" />
					<LinkIcon
						v-if="typeComputed === VirtualUserItemType.AddPublicLink"
						:size="mdIconSize" />
					<AnoymousIcon
						v-if="typeComputed === VirtualUserItemType.Anonymous"
						:size="mdIconSize" />
					<LinkIcon
						v-if="typeComputed === VirtualUserItemType.InternalAccess"
						:size="mdIconSize" />
					<ContactGroupIcon
						v-if="typeComputed === UserType.ContactGroup"
						:size="mdIconSize" />
					<GroupIcon
						v-if="typeComputed === UserType.Group"
						:size="mdIconSize" />
					<CircleIcon
						v-if="typeComputed === UserType.Circle"
						:size="mdIconSize" />
					<DeletedUserIcon
						v-if="typeComputed === VirtualUserItemType.Deleted"
						:size="mdIconSize" />
				</template>
			</NcAvatar>

			<div v-if="$slots.menu" class="hover-menu">
				<slot name="menu" />
			</div>

			<AdminIcon
				v-if="showTypeIcon && typeComputed === UserType.Admin"
				:size="typeIconSize"
				class="type-icon" />
			<ContactIcon
				v-else-if="showTypeIcon && typeComputed === UserType.Contact"
				:size="typeIconSize"
				class="type-icon" />
			<EmailIcon
				v-else-if="showTypeIcon && typeComputed === UserType.Email"
				:size="typeIconSize"
				class="type-icon" />
			<ShareIcon
				v-else-if="showTypeIcon && typeComputed === UserType.External"
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
