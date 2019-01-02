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
	<div class="controls">
		<Breadcrumb :index-page="indexPage" :intitle="intitle" />
		<slot />
	</div>
</template>

<script>
import Breadcrumb from './_base-breadcrumb.vue'
export default {
	components: {
		Breadcrumb
	},
	props: {
		intitle: {
			type: String,
			default: undefined
		},
		indexPage: {
			type: String,
			default: undefined
		}
	}
}
</script>

<style lang="scss">
.controls {
    display: flex;
    border-bottom: 1px solid var(--color-border);
    position: fixed;
    background: var(--color-main-background);
    width: 100%;
    height: 45px;
    z-index: 1001;
    .button,
    button {
        flex-shrink: 0;
        height: 36px;
        padding: 7px 10px;
        border: 0;
        background: transparent;
        color: var(--color-text-lighter);
        &.primary {
            background: var(--color-primary);
            color: var(--color-primary-text);
        }
    }

    .breadcrumb {
        overflow: hidden;
        min-width: 35px;
        div.crumb:last-child {
            flex-shrink: 1;
            overflow: hidden;
            > span {
                flex-shrink: 1;
                text-overflow: ellipsis;
            }
        }
    }
}
</style>
