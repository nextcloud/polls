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

<template lang="html">
	<div class="combo-title">
		<div class="combo-title__title">
			{{ title }}
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'ComboTitle',

	computed: {
		...mapState({
			title: (state) => state.combo.title,
		}),
	},
}

</script>

<style lang="scss">
	.combo-title {
		.combo-title__sub {
			opacity: 0.7;
			line-height: 1.2em;
			font-size: 1em;
			button {
				margin-left: 8px;
			}

			[class^='icon-'], [class*=' icon-'] {
				padding-left: 21px;
				background-position: left center;
			}
			.closed {
				color: var(--color-error);
				font-weight: 700;
			}
			.closing {
				color: var(--color-warning);
				font-weight: 700;
			}
			.open {
				font-weight: 700;
			}
			.archived {
				color: var(--color-error);
				font-weight: 700;
			}
			.created {
				color: var(--color-text-light);
			}
		}

		.combo-title__title {
			font-weight: bold;
			font-size: 1.3em;
			line-height: 1.5em;
		}
	}
</style>
