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
	<div>
		<div class="newCommentRow comment new-comment">
			<div class="authorRow user-cell flex-row">
				<div class="avatar has-tooltip" title="" style="height: 32px; width: 32px;"
					data-original-title="dartcafe"
				>
					<img src="/index.php/avatar/dartcafe/32?v=0" alt="" width="32"
						height="32"
					>
				</div>
				<div class="author">
					Dartcafe
				</div>
			</div>

			<form class="newCommentForm flex-row" name="send_comment" action="/index.php/apps/polls/insert/comment"
				method="POST"
			>
				<input type="hidden" name="pollId" value="1">
				<input type="hidden" name="userId" value="dartcafe">
				<div id="commentBox" name="commentBox" class="message"
					data-placeholder="Neuer Kommentar ..." contenteditable="true"
				/>
				<input id="submit_send_comment" class="submitComment icon-confirm" value=""
					type="submit"
				>
				<span class="icon-loading-small" style="float:right;" />
			</form>
		</div>
	</div>
</template>

<script>

export default {
	name: 'CommentsList',

	components: {
	},
}
