<template>
	<div>
		<div v-if="state === 'initial'" class="color-grey">{{ initialText }}</div>
		<div v-else-if="state === 'loading'" class="color-grey">{{ loadingText }}</div>
		<div v-else-if="state === 'ready' && !items.length" class="color-grey">{{ emptyText }}</div>

		<table id="filestable" class="list-container" v-else-if="state === 'ready' && items.length">
			<thead>
			<tr>
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
			</tr>
			</thead>
			<tbody id="fileList">
			<tr v-for="(item, index) in items" :key="item.fileid || index">
				<td class="filename">
					<a class="name" :href="generateFileUrl(item)" target="_blank">
						<div class="thumbnail-wrapper">
							<div class="thumbnail">
								<img :src="generatePreviewUrl(item)">
							</div>
						</div>
						<span class="nametext">
                                <span class="innernametext">{{ item.displayname }}</span>
                            </span>
					</a>
				</td>
				<td class="size" v-if="columns.includes('size')">
                        <span class="nametext">
                            <span class="innernametext">{{ humanFileSize(item.size) }}</span>
                        </span>
				</td>
				<td class="modified" v-if="columns.includes('modified')">
                        <span class="nametext">
                            <span class="innernametext">{{ formatDate(item.mtime) }}</span>
                        </span>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

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
		generatePreviewUrl(item) {
			return generateUrl(`/core/preview?fileId=${item.fileid}&y=32&mimeFallback=true&a=1&a=1`)
		}
	}
}
</script>
