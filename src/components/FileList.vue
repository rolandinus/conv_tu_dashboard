<template>
	<div class="file-list" :class="{ 'file-list--grid': grid }">
		<NcEmptyContent
			v-if="state === 'initial'"
			:name="initialText"
		/>
		<div v-else-if="state === 'loading'" class="file-list__state">
			<NcLoadingIcon size="44" />
			<p>{{ loadingText }}</p>
		</div>
		<NcEmptyContent
			v-else-if="!items.length"
			:name="emptyText"
		/>
		<table v-else-if="!grid" class="file-table" role="table">
			<thead>
				<tr>
					<th scope="col" class="file-table__head file-table__head--name">Name</th>
					<th
						v-if="hasColumn('size')"
						scope="col"
						class="file-table__head file-table__head--size"
					>Größe</th>
					<th
						v-if="hasColumn('modified')"
						scope="col"
						class="file-table__head"
					>Zuletzt geändert</th>
					<th
						v-if="hasColumn('beschreibung')"
						scope="col"
						class="file-table__head"
					>Beschreibung</th>
					<th
						v-if="hasColumn('tags')"
						scope="col"
						class="file-table__head"
					>Tags</th>
					<th
						v-if="hasColumn('info')"
						scope="col"
						class="file-table__head"
					>Informationen</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="(item, index) in items"
					:key="item.fileid || index"
					class="file-table__row"
				>
					<td class="file-table__cell file-table__cell--name">
						<a
							class="file-table__link"
							:href="fileHref(item)"
							@click.prevent="openItem(item, $event)"
						>
							<span class="file-table__thumb" aria-hidden="true">
								<img v-if="thumbnailUrl(item)" :src="thumbnailUrl(item)" alt="" />
								<NcIconSvgWrapper v-else :svg="isFolder(item) ? folderSvg : fileSvg" />
							</span>
							<span class="file-table__title">{{ item.displayname }}</span>
						</a>
					</td>
					<td
						v-if="hasColumn('size')"
						class="file-table__cell file-table__cell--numeric"
					>
						{{ humanFileSize(item.size) }}
					</td>
					<td
						v-if="hasColumn('modified')"
						class="file-table__cell"
					>
						{{ formatDate(item.mtime) || '-' }}
					</td>
					<td
						v-if="hasColumn('beschreibung')"
						class="file-table__cell"
					>
						{{ formatDescription(item.info) || '-' }}
					</td>
					<td
						v-if="hasColumn('tags')"
						class="file-table__cell"
					>
						{{ formatTags(item.info) }}
					</td>
					<td
						v-if="hasColumn('info')"
						class="file-table__cell"
					>
						{{ formatInfo(item.info) }}
					</td>
				</tr>
			</tbody>
		</table>
		<div v-else class="file-list__grid" role="list">
			<article
				v-for="(item, index) in items"
				:key="item.fileid || index"
				class="file-card"
				role="listitem"
			>
				<button
					type="button"
					class="file-card__button"
					:aria-label="item.displayname"
					@click="openItem(item, $event)"
				>
					<div class="file-card__thumb" aria-hidden="true">
						<img v-if="thumbnailUrl(item)" :src="thumbnailUrl(item)" alt="" />
						<NcIconSvgWrapper v-else :svg="isFolder(item) ? folderSvg : fileSvg" />
					</div>
					<div class="file-card__body">
						<h4 class="file-card__name">{{ item.displayname }}</h4>
						<dl class="file-card__meta" aria-label="Dateiinformationen">
							<div
								v-for="meta in buildMetaEntries(item)"
								:key="meta.key"
								class="file-card__meta-entry"
							>
								<dt class="file-card__meta-label">{{ meta.label }}</dt>
								<dd class="file-card__meta-value">{{ meta.value }}</dd>
							</div>
						</dl>
						<p
							v-if="hasColumn('beschreibung') && formatDescription(item.info)"
							class="file-card__description"
						>
							{{ formatDescription(item.info) }}
						</p>
					</div>
				</button>
			</article>
		</div>
	</div>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { generateUrl } from '@nextcloud/router'

import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import FileDocumentOutlineSvg from '@mdi/svg/svg/file-document-outline.svg?raw'

export default {
	name: 'FileList',
	components: {
		NcEmptyContent,
		NcLoadingIcon,
		NcIconSvgWrapper,
	},
	props: {
		items: {
			type: Array,
			required: true,
		},
		state: {
			type: String,
			required: true,
			validator: value => ['initial', 'loading', 'ready'].includes(value),
		},
		initialText: {
			type: String,
			required: true,
		},
		loadingText: {
			type: String,
			required: true,
		},
		emptyText: {
			type: String,
			required: true,
		},
		columns: {
			type: Array,
			default: () => ['name'],
			validator: value => value.includes('name'),
		},
		grid: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		folderSvg() {
			return FolderSvg
		},
		fileSvg() {
			return FileDocumentOutlineSvg
		},
		showDescription() {
			return this.columns.includes('beschreibung')
		},
	},
	methods: {
		hasColumn(column) {
			return this.columns.includes(column)
		},
		isFolder(item) {
			return item.type === 'folder'
		},
		fileHref(item) {
			if (!item.fileid) {
				return '#'
			}
			return generateUrl(`/f/${item.fileid}`)
		},
		thumbnailUrl(item) {
			if (!item.fileid || this.isFolder(item)) {
				return ''
			}
			return generateUrl(`/core/preview?fileId=${item.fileid}&x=140&y=140&mimeFallback=true&a=1`)
		},
		humanFileSize(size) {
			const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']
			if (size === 0) {
				return '0.00 B'
			}
			if (!size || size < 1024) {
				return '< 1 KB'
			}
			const exponent = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1)
			const precision = Math.max(exponent - 1, 0)
			const value = Number((size / Math.pow(1024, exponent)).toFixed(precision))
			return `${value} ${units[exponent]}`
		},
		formatDate(timestamp) {
			if (!timestamp) {
				return ''
			}
			const value = timestamp < 10000000000 ? timestamp * 1000 : timestamp
			return new Date(value).toLocaleDateString()
		},
		formatTags(info) {
			if (!info || !Array.isArray(info.systemtags) || !info.systemtags.length) {
				return '-'
			}
			return info.systemtags.join(', ')
		},
		formatInfo(info) {
			if (!info) {
				return '-'
			}
			const parts = []
			if (info.copyright) {
				parts.push(`Copyright: ${info.copyright}`)
			}
			if (info.imagewidth && info.imageheight) {
				parts.push(`Auflösung: ${info.imagewidth} × ${info.imageheight}`)
			}
			if (info.xresolution && info.yresolution) {
				const dpi = info.xresolution === info.yresolution ? info.xresolution : `${info.xresolution} × ${info.yresolution}`
				parts.push(`DPI: ${dpi}`)
			}
			if (!parts.length) {
				return '-'
			}
			return parts.join(' · ')
		},
		formatDescription(info) {
			if (!info || !info.description) {
				return ''
			}
			const description = info.description.trim()
			if (description.length > 200) {
				return `${description.substring(0, 200)}…`
			}
			return description
		},
		buildMetaEntries(item) {
			const entries = []
			if (this.hasColumn('size')) {
				const sizeValue = item.size !== undefined && item.size !== null ? this.humanFileSize(item.size) : '-'
				entries.push({
					key: 'size',
					label: 'Größe',
					value: sizeValue,
				})
			}
			if (this.hasColumn('modified')) {
				const modifiedValue = this.formatDate(item.mtime) || '-'
				entries.push({
					key: 'modified',
					label: 'Zuletzt geändert',
					value: modifiedValue,
				})
			}
			if (this.hasColumn('tags')) {
				entries.push({
					key: 'tags',
					label: 'Tags',
					value: this.formatTags(item.info),
				})
			}
			if (this.hasColumn('info')) {
				entries.push({
					key: 'info',
					label: 'Informationen',
					value: this.formatInfo(item.info),
				})
			}
			return entries
		},
		openItem(item, event) {
			if (event) {
				event.preventDefault()
				event.stopPropagation()
			}

			if (!item.fileid) {
				return
			}

			const path = `/f/${item.fileid}`
			const router = typeof window !== 'undefined' && window.OCP && window.OCP.Files && window.OCP.Files.Router

			if (router && typeof router.goTo === 'function') {
				router.goTo(path).catch(() => {
					window.location.href = this.fileHref(item)
				})
			} else {
				window.location.href = this.fileHref(item)
			}
		},
	},
}
</script>

<style>
.file-list {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
}

.file-list__state {
	display: inline-flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	color: var(--color-text-maxcontrast);
}

.file-list__state p {
	margin: 0;
}

.file-table {
	width: 100%;
	border-collapse: collapse;
	border-spacing: 0;
	font-size: 0.95rem;
	table-layout: fixed;
}

.file-table__head {
	text-align: left;
	padding: 12px 16px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	background-color: var(--color-background-hover);
}

.file-table__head--name {
	width: 40%;
}

.file-table__head--size {
	width: 10%;
	text-align: right;
}

.file-table__row {
	border-bottom: 1px solid var(--color-border);
	background: var(--color-background-default);
	min-height: 160px;
}

.file-table__row:hover {
	background-color: var(--color-background-hover);
}

.file-table__cell {
	padding: 14px 16px;
	vertical-align: middle;
	color: var(--color-main-text);
}

.file-table__cell--name {
	padding-left: 20px;
}

.file-table__cell--numeric {
	text-align: right;
}

.file-table__link {
	display: inline-flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 1.5);
	color: inherit;
	text-decoration: none;
	width: 100%;
}

.file-table__link:hover .file-table__title {
	text-decoration: underline;
}

.file-table__thumb {
	min-width: 140px;
	max-width: 140px;
	max-height: 140px;
	width: 140px;
	height: auto;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	border-radius: var(--border-radius);
	background-color: var(--color-background-dark);
	overflow: hidden;
}

.file-table__thumb img {
	width: auto;
	height: auto;
	max-width: 100%;
	max-height: 140px;
}

.file-table__title {
	font-weight: 600;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 420px;
	display: inline-block;
}

.file-list__grid {
	display: grid;
	gap: calc(var(--default-grid-baseline) * 2);
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.file-card {
	background-color: var(--color-background-default);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: var(--box-shadow-level-1);
	transition: transform var(--animation-quick) ease, border-color var(--animation-quick) ease;
}

.file-card__button {
	all: unset;
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 1.5);
	padding: calc(var(--default-grid-baseline) * 2);
	width: 100%;
	height: 100%;
	cursor: pointer;
	border-radius: inherit;
}

.file-card__button:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
}

.file-card:hover,
.file-card:focus-within {
	transform: translateY(-2px);
	border-color: var(--color-primary-element);
}

.file-card:hover .file-card__meta,
.file-card:focus-within .file-card__meta,
.file-card:hover .file-card__description,
.file-card:focus-within .file-card__description {
	opacity: 1;
	max-height: 240px;
}

.file-card__thumb {
	width: 100%;
	max-height: 260px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: var(--border-radius);
	background-color: var(--color-background-dark);
	overflow: hidden;
}

.file-card__thumb img {
	width: auto;
	height: auto;
	max-width: 100%;
	max-height: 260px;
}

.file-card__body {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 1.25);
}

.file-card__name {
	margin: 0;
	font-size: 1rem;
	font-weight: 600;
}

.file-card__meta {
	display: grid;
	gap: calc(var(--default-grid-baseline) * 0.75);
	margin: 0;
	opacity: 0;
	max-height: 0;
	overflow: hidden;
	transition: opacity var(--animation-quick) ease, max-height var(--animation-quick) ease;
}

.file-card__meta-entry {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: calc(var(--default-grid-baseline) * 0.5);
}

.file-card__meta-label {
	margin: 0;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.file-card__meta-value {
	margin: 0;
	color: var(--color-main-text);
	word-break: break-word;
}

.file-card__description {
	margin: 0;
	color: var(--color-text-maxcontrast);
	opacity: 0;
	max-height: 0;
	overflow: hidden;
	transition: opacity var(--animation-quick) ease, max-height var(--animation-quick) ease;
}

.file-list__state + .file-table {
	margin-top: calc(var(--default-grid-baseline) * 2);
}

@media (max-width: 768px) {
	.file-table__head,
	.file-table__cell {
		padding: 12px;
	}

	.file-table__title {
		max-width: 180px;
	}

	.file-list__grid {
		grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	}
}
</style>
