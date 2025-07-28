/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Comment } from '../../stores/comments.types'

function groupComments(inputArray: Comment[]) {
	const idToElement: { [key: number]: Comment } = inputArray.reduce(
		(idToCommentMap, item) => {
			idToCommentMap[item.id] = item
			return idToCommentMap
		},
		{} as { [key: number]: Comment },
	)

	const resultArray = inputArray
		.filter((comment: Comment) => comment.parent === 0)
		.sort((a, b) => b.timestamp - a.timestamp)
		.map((parentItem: Comment) => {
			const comments = getComments(parentItem.id)

			const sortedComments = comments.sort((a, b) => {
				const commentA = idToElement[a.id]
				const commentB = idToElement[b.id]

				// Verify elementA and elementB are defined
				if (commentA && commentB) {
					// compare timestamps
					if (commentA.timestamp !== commentB.timestamp) {
						return commentB.timestamp - commentA.timestamp
					}

					// sort by id, if timestamps are identical
					return commentB.id - commentA.id
				}

				// otherwise sort by id
				return b.id - a.id
			})

			return {
				...parentItem,
				comments: sortedComments,
			}
		})

	/**
	 * Get comments by parent ID
	 * @param parentId - Parent comment ID
	 * @return Array of child comments
	 */
	function getComments(parentId: number): Comment[] {
		const comments: Comment[] = []
		const stack: number[] = [parentId]

		while (stack.length > 0) {
			const currentId = stack.pop()
			if (currentId !== undefined) {
				const currentElement = idToElement[currentId]
				if (currentElement) {
					comments.push({ ...currentElement })
					const childIds = inputArray
						.filter((item) => item.parent === currentId)
						.map((item) => item.id)

					stack.push(...childIds)
				}
			}
		}

		return comments
	}

	return resultArray
}

export { groupComments }
