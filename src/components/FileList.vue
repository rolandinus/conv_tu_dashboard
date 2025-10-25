<template>
	<div>
		<div v-if="state === 'initial'" class="color-grey">{{ initialText }}</div>
		<div v-else-if="state === 'loading'" class="color-grey">{{ loadingText }}</div>
		<div v-else-if="state === 'ready' && !items.length" class="color-grey">{{ emptyText }}</div>

		<table class="filestable list-container" v-else-if="state === 'ready' && items.length">
			<thead>
			<tr>
				<th id="headerName" class="column-thumbnail">
					<div id="headerName-container">
						<span class="columntitle thumbnail"></span>
					</div>
				</th>
				<th id="headerName" class="column-name">
					<div id="headerName-container">
						<span class="columntitle name">Name</span>
					</div>
				</th>
				<th v-if="columns.includes('size')">
					<div>
						<span class="columntitle size">Größe</span>
					</div>
				</th>
				<th v-if="columns.includes('modified')">
					<div>
						<span class="columntitle modified">Zuletzt geändert</span>
					</div>
				</th>
				<th v-if="columns.includes('beschreibung')">
					<div>
						<span class="columntitle beschreibung">Beschreibung</span>
					</div>
				</th>
				<th v-if="columns.includes('tags')">
					<div>
						<span class="columntitle tags">Tags</span>
					</div>
				</th>
				<th v-if="columns.includes('info')">
					<div>
						<span class="columntitle info">Informationen</span>
					</div>
				</th>
			</tr>
			</thead>
			<tbody class=".fileList">
			<tr v-for="(item, index) in items" :key="item.fileid || index">
				<td class="col-thumbnail">
					<a class="name thumbnail-link" :href="generateFileUrl(item)" target="_blank">
						<div class="thumbnail-wrapper">
							<div class="thumbnail" v-html="generateThumbnail(item)">
							</div>
						</div>
						<div class="thumbnail-overlay">
							<div class="overlay-content">
								<div class="overlay-field">
									<span class="overlay-label">Name:</span>
									<span class="overlay-value">{{ item.displayname }}</span>
								</div>
								<div v-if="columns.includes('size')" class="overlay-field">
									<span class="overlay-label">Größe:</span>
									<span class="overlay-value">{{ humanFileSize(item.size) }}</span>
								</div>
								<div v-if="columns.includes('modified')" class="overlay-field">
									<span class="overlay-label">Zuletzt geändert:</span>
									<span class="overlay-value">{{ formatDate(item.mtime) }}</span>
								</div>
								<div v-if="columns.includes('beschreibung')" class="overlay-field">
									<span class="overlay-label">Beschreibung:</span>
									<span class="overlay-value">{{ formatDescription(item.info) }}</span>
								</div>
								<div v-if="columns.includes('tags')" class="overlay-field">
									<span class="overlay-label">Tags:</span>
									<span class="overlay-value">
											<span v-if="!item.info || !item.info.systemtags || !Array.isArray(item.info.systemtags) || item.info.systemtags.length === 0">-</span>
											<span v-else>{{ item.info.systemtags.join(', ') }}</span>
										</span>
								</div>
								<div v-if="columns.includes('info')" class="overlay-field">
									<span class="overlay-label">Informationen:</span>
									<span class="overlay-value">{{ formatInfo(item.info) }}</span>
								</div>
							</div>
						</div>
					</a>
				</td>

				<td class="filename file-info">
					<a class="name" :href="generateFileUrl(item)" target="_blank">
						<span class="nametext">
                                <span class="innernametext">{{ item.displayname }}</span>
                            </span>
					</a>
				</td>
				<td class="size file-info" v-if="columns.includes('size')">
                        <span class="nametext">
                            <span class="innernametext">{{ humanFileSize(item.size) }}</span>
                        </span>
				</td>
				<td class="modified file-info" v-if="columns.includes('modified')">
                        <span class="nametext">
                            <span class="innernametext">{{ formatDate(item.mtime) }}</span>
                        </span>
				</td>
				<td class="beschreibung file-info" v-if="columns.includes('beschreibung')">
                        <span class="nametext">
                            <span class="innernametext">{{ formatDescription(item.info) }}</span>
                        </span>
				</td>
				<td class="tags file-info" v-if="columns.includes('tags')">
                        <span class="nametext">
                            <span v-if="!item.info || !item.info.systemtags || !Array.isArray(item.info.systemtags) || item.info.systemtags.length === 0" class="innernametext">-</span>
                            <span v-else>
							<span v-for="tag in item.info.systemtags" :key="tag" class="system-tag">{{ tag }}</span>
							</span>
                        </span>
				</td>
				<td class="info file-info" v-if="columns.includes('info')">
                        <span class="nametext">
                            <span class="innernametext info-text">{{ formatInfo(item.info) }}</span>
                        </span>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

export default {
	name: 'FileList',
	props: {
		items: {
			type: Array,
			required: true
		},
		state: {
			type: String,
			required: true,
			validator: value => ['initial', 'loading', 'ready'].includes(value)
		},
		initialText: {
			type: String,
			required: true
		},
		loadingText: {
			type: String,
			required: true
		},
		emptyText: {
			type: String,
			required: true
		},
		columns: {
			type: Array,
			default: () => ['name'],
			validator: value => value.includes('name')
		}
	},
	methods: {
		humanFileSize(size) {
			if (size === 0) {
				return '0.00 B';
			}
			let i = Math.floor(Math.log(size) / Math.log(1024));
			if (i < 1) {
				return '< 1 KB'
			}
			return (size / Math.pow(1024, i)).toFixed(i - 1) * 1 + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
		},
		formatDate(timestamp) {
			// check if timestamp is in milliseconds
			if (timestamp < 10000000000) {
				timestamp *= 1000
			}
			return new Date(timestamp ).toLocaleDateString()
		},
		generateFileUrl(item) {
			return generateUrl(`/f/${item.fileid}`)
		},
		generateThumbnail(item) {
			if (item.type === 'folder') {
				return FolderSvg
			}
			return '<img src='+
				generateUrl(`/core/preview?fileId=${item.fileid}&x=140&y=140&mimeFallback=true&a=1`)+'/>'
		},
		formatInfo(info) {
			if (!info) {
				return ''
			}
			const parts = []

			// Add copyright if available
			if (info.copyright) {
				parts.push(`Copyright: ${info.copyright}`)
			}

			// Add resolution (width x height) if both are available
			if (info.imagewidth && info.imageheight) {
				parts.push(`Auflösung: ${info.imagewidth} x ${info.imageheight}`)
			}

			// Add DPI if both x and y resolution are available
			if (info.xresolution && info.yresolution) {
				let dpi = info.xresolution==info.yresolution ? `${info.xresolution}` : `${info.xresolution} x ${info.yresolution}`
				parts.push(`DPI: ${info.xresolution} x ${dpi}`)
			}

			return parts.length > 0 ? parts.join('\n') : '-'
		},
		formatDescription(info) {
			if (!info || !info.description) {
				return '-'
			}
			const description = info.description
			// Restrict to 200 characters
			if (description.length > 200) {
				return description.substring(0, 200) + '...'
			}
			return description
		}
	}
}
</script>
<style>
.thumbnail svg {
	fill: var(--color-primary-element);
}
.list-container .nametext {
	padding-left: 0.5em;
}
.info-text {
	white-space: pre-line;
}
.beschreibung {
	max-width: 300px;
}
.beschreibung .nametext {
	white-space: normal;
	word-wrap: break-word;
	overflow-wrap: break-word;
}
.tags {
	max-width: 300px;
}
.tags .nametext {
	white-space: normal;
	word-wrap: break-word;
	overflow-wrap: anywhere;
}
.thumbnail {
	max-width: 300px;
}
</style>
