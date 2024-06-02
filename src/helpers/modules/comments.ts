/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const groupComments = (inputArray) => {
	const idToElement = inputArray.reduce((acc, item) => {
		acc[item.id] = item
		return acc
	}, {})

	const resultArray = inputArray
		.filter((item) => item.parent === 0)
		.sort((a, b) => b.timestamp - a.timestamp)
		.map((parentItem) => {
			const comments = getComments(parentItem.id)

			const sortedComments = comments.sort((a, b) => {
				const elementA = idToElement[a.id]
				const elementB = idToElement[b.id]

				// Verify elementA and elementB are defined
				if (elementA && elementB) {
					// compare timestamps
					if (elementA.timestamp !== elementB.timestamp) {
						return elementB.timestamp - elementA.timestamp
					}

					// sort by id, if timestamps are identical
					return elementB.id - elementA.id
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
	 *
	 * @param {object} parentId parent comment
	 */
	function getComments(parentId) {
		const comments = []
		const stack = [parentId]

		while (stack.length > 0) {
			const currentId = stack.pop()
			const currentElement = idToElement[currentId]
			comments.push({
				id: currentElement.id,
				comment: currentElement.comment,
				deleted: currentElement.deleted,
			})

			const childIds = inputArray
				.filter((item) => item.parent === currentId)
				.map((item) => item.id)

			stack.push(...childIds)
		}

		return comments
	}

	return resultArray
}

export { groupComments }
