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
	<ButtonDiv :title="t('polls', 'Add a public link')" icon="icon-add" @click="addShare({type: 'public', userId: '', emailAddress: ''})" />
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import ButtonDiv from '../Base/ButtonDiv'

export default {
	name: 'SharesPublicAdd',

	components: {
		ButtonDiv,
	},

	methods: {
		async addShare(payload) {
			try {
				await this.$store.dispatch('shares/add', {
					share: payload,
					type: payload.type,
					id: payload.id,
					emailAddress: payload.emailAddress,
				})
			} catch {
				showError(t('polls', 'Error adding share'))
			}
		},
	},
}
</script>
