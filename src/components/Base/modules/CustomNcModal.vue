<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcModal from '@nextcloud/vue/components/NcModal'

defineOptions({ inheritAttrs: false })
</script>

<template>
	<NcModal v-bind="$attrs" class="custom-nc-modal">
		<template v-for="(_, name) in $slots" #[name]="slotProps">
			<slot :name="name" v-bind="slotProps ?? {}" />
		</template>
	</NcModal>
</template>

<style lang="scss">
.modal-wrapper .modal-container {
	overflow: scroll !important;
}

.custom-nc-modal {
	.modal-container__content {
		padding: 3rem 1.6rem 1rem;
	}
	.modal__content {
		padding: 14px;
		color: var(--color-main-text);
	}
	.modal__buttons__spacer {
		flex: 1;
	}
	.modal__buttons {
		display: flex;
		gap: 8px;
		justify-content: flex-end;
		flex-wrap: wrap-reverse;
		align-items: center;
		margin-top: 1.5rem;

		.left {
			display: flex;
			flex: 1;
			gap: 8px;
		}

		.right {
			display: flex;
			flex: 0;
			justify-content: flex-end;
			gap: 8px;
		}

		.button {
			margin-inline: 10px 0;
		}
	}

	.modal__buttons__link {
		text-decoration: underline;
	}
}
</style>
