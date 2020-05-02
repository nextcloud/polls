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
	<AppContent>
		<div id="emptycontent">
			<div id="emptycontent-icon" class="icon-search" />
			<h2>{{ t('polls', 'The poll does not exist') }}</h2>

			<p v-if="getCurrentUser" class="emptycontent-additional">
				{{ t('polls', 'Enter a poll or start a new one.') }}
			</p>
			<button v-else @click="gotoLogin()">
				{{ t('polls', 'Goto Nextcloud') }}
			</button>
		</div>
	</AppContent>
</template>

<script>
import { AppContent } from '@nextcloud/vue'

export default {
	name: 'NotFound',
	components: {
		AppContent,
	},

	methods: {
		gotoLogin() {
			window.location.replace(OC.generateUrl('/'))
		},
	},
}
</script>
