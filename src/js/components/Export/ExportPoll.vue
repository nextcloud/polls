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
	<Actions default-icon="icon-download">
		<ActionButton close-after-click @click="getExcelFile()">
			<template #icon>
				<ExcelIcon />
			</template>
			{{ t('polls', 'Download Excel spreadsheet') }}
		</ActionButton>

		<ActionButton close-after-click @click="getOdsFile()">
			<template #icon>
				<FileTableIcon />
			</template>
			{{ t('polls', 'Download Open Document spreadsheet') }}
		</ActionButton>

		<ActionButton close-after-click @click="getCsvFile()">
			<template #icon>
				<CsvIcon />
			</template>
			{{ t('polls', 'Download CSV file') }}
		</ActionButton>

		<ActionButton close-after-click @click="getHTMLFile()">
			<template #icon>
				<XmlIcon />
			</template>
			{{ t('polls', 'Download HTML file') }}
		</ActionButton>
	</Actions>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { saveAs } from 'file-saver'
import XLSX from 'xlsx'
import { Actions, ActionButton } from '@nextcloud/vue'
import ExcelIcon from 'vue-material-design-icons/MicrosoftExcel.vue'
import FileTableIcon from 'vue-material-design-icons/FileTableOutline.vue'
import CsvIcon from 'vue-material-design-icons/FileDelimited.vue'
import XmlIcon from 'vue-material-design-icons/Xml.vue'

export default {
	name: 'ExportPoll',
	components: {
		Actions,
		ActionButton,
		CsvIcon,
		ExcelIcon,
		FileTableIcon,
		XmlIcon,
	},

	data() {
		return {
			workBook: [],
		}
	},

	computed: {
		...mapGetters({
			participants: 'poll/participants',
			options: 'options/participants',
			getVote: 'votes/getVote',
			explodeDates: 'options/explodeDates',
		}),
		...mapState({
			poll: (state) => state.poll,
			options: (state) => state.options,
			votes: (state) => state.votes,
		}),
	},

	methods: {
		getCsvFile() {
			this.createWorkbook()
			this.addSheet()
			this.addRowsFromArray()
			this.downloadCsvFile()
		},

		getHTMLFile() {
			this.createWorkbook()
			this.addSheet()
			this.addRowsFromArray()
			this.downloadHTMLFile()
		},

		getOdsFile() {
			this.createWorkbook()
			this.addSheet()
			this.addRowsFromArray()
			this.downloadOdsFile()
		},

		getExcelFile() {
			this.createWorkbook()
			this.addSheet()
			this.addRowsFromArray()
			this.downloadExcelFile()
		},

		createWorkbook() {
			this.workBook = XLSX.utils.book_new()
		},
		addSheet() {
			this.workBook.SheetNames.push(this.poll.title)
		},
		addRowsFromArray() {
			const sheetData = [
				[this.poll.title],
				[this.poll.description],
			]

			if (this.poll.type === 'datePoll') {
				// const optionsLine = [...[''], ...this.options.list.map((item) => item.pollOptionText)]
				// sheetData.push(optionsLine)

				sheetData.push([...[t('polls', 'from')], ...this.options.list.map((option) => this.explodeDates(option).from.dateTime)])
				sheetData.push([...[t('polls', 'to')], ...this.options.list.map((option) => this.explodeDates(option).to.dateTime)])
				// sheetData.push(['to'].push(...this.options.list.map((option) => this.explodeDates(option).to.dateTime)))
			} else {
				sheetData.push([...[''], ...this.options.list.map((item) => item.pollOptionText)])
			}

			this.participants.forEach((participant) => {
				const votesLine = [participant.displayName]

				this.options.list.forEach((option, i) => {
					votesLine.push(t('polls', this.getVote({ userId: participant.userId, option }).voteAnswerSymbol))
				})

				sheetData.push(votesLine)
			})

			const ws = XLSX.utils.aoa_to_sheet(sheetData)
			this.workBook.Sheets[this.poll.title] = ws

		},
		downloadExcelFile() {
			const wbout = XLSX.write(this.workBook, { bookType: 'xlsx', type: 'binary' })
			saveAs(new Blob([this.s2ab(wbout)], { type: 'application/octet-stream' }), 'poll.xlsx')
		},

		downloadOdsFile() {
			const wbout = XLSX.write(this.workBook, { bookType: 'ods', type: 'binary' })
			saveAs(new Blob([this.s2ab(wbout)], { type: 'application/octet-stream' }), 'poll.ods')
		},

		downloadCsvFile() {
			const wbout = XLSX.write(this.workBook, { bookType: 'csv', type: 'binary' })
			saveAs(new Blob([this.s2ab(wbout)], { type: 'application/octet-stream' }), 'poll.csv')
		},

		downloadHTMLFile() {
			const wbout = XLSX.write(this.workBook, { bookType: 'html', type: 'binary' })
			saveAs(new Blob([this.s2ab(wbout)], { type: 'application/octet-stream' }), 'poll.html')
		},

		s2ab(s) {
			const buf = new ArrayBuffer(s.length) // convert s to arrayBuffer
			const view = new Uint8Array(buf) // create uint8array as viewer
			for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF // convert to octet
			return buf
		},
	},
}
</script>
