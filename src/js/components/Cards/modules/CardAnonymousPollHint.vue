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
	<CardDiv :type="cardType">
		{{ t('polls', 'Although participant\'s names are hidden, this is not a real anonymous poll because they are not hidden from the owner.') }}
		{{ t('polls', 'Additionally the owner can remove the anonymous flag at any time, which will reveal the participant\'s names.') }}
	</CardDiv>
</template>

<script>
import { CardDiv } from '../../Base/index.js'

export default {
	name: 'CardAnonymousPollHint',

	components: {
		CardDiv,
	},

	data() {
		return {
			cardType: 'warning',
		}
	},

}
</script>
