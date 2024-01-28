<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<div class="action toggle-sidebar">
		<NcButton type="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>

<script>
import { mapMutations } from 'vuex'
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'ActionSwitchSafeTable',

	components: {
		NcButton,
	},

	data() {
		return {
			caption: t('polls', 'Reveal them'),
		}
	},

	methods: {
		...mapMutations({
			switchSafeTable: 'poll/switchSafeTable',
		}),

		clickAction() {
			this.switchSafeTable()
		},
	},
}
</script>
