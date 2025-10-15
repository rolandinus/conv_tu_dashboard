
<template>
	<div id="tu-dashboard" class="">
		<div class="hero-image"
			 :style="{'background-image': 'url(' + background + ')'}">
			<div class="hero-text">
				<form @submit.prevent="search">
					<a href="#"><h2 class="welcomeText">Willkommen {{
							userName
						}}!</h2></a>
					<div class="search-term-container">
						<input type="text" id="term" name="term"
							   placeholder="Suche nach Dateien" v-model="term">
						<v-select id="select-search-tags" :options="systemTags"
								  v-model="selectedTags" class="style-chooser"
								  multiple
								  placeholder="Suche nach Tags"></v-select>
						<input type="text" id="meta-term" name="meta-term"
							   placeholder="Suche in Metadata"
							   v-model="metaTerm">
						<button @click="search" class="btn-search">
							Suchen
							<div @click.stop.prevent="search" class="arrow"
								 id="search-arrow"
								 :style="{'background-image':'url('+submitArrowUrl+')'}"></div>
						</button>
					</div>
				</form>
			</div>
		</div>
		<div id="main-content-container" style="padding: 15px; display: flex;">
			<div v-show="!isSearchFullwidth" style="padding: 15px; width: 100%; ">
				<h2>Favoriten</h2>
				<div id="app-content-favorites" class="viewcontainer hide-hidden-files has-comments">
					<FileList
						:items="favorites"
						state="ready"
						initialText="Initialisiere Favoriten..."
						loadingText="Lade Favoriten..."
						emptyText="Noch keine Favoriten vorhanden"
						:columns="['name', ]"
					/>
				</div>
			</div>

			<div style="padding: 15px; width: 100%; ">
				<div style="display: flex; align-items: center; justify-content: space-between;">
					<h2>Suchergebnis</h2>
					<div style="display: flex; gap: 8px;">
						<button
							v-if="enableGridView"
							@click="toggleGridView"
							class="view-toggle-btn"
							:class="{ active: isGridView }"
							:title="isGridView ? 'Listenansicht' : 'Rasteransicht'"
						>
							<span class="icon" v-html="isGridView ? formatListBulletedSquareIcon : viewGridIcon"></span>
						</button>
						<button
							@click="toggleSearchFullwidth"
							class="fullwidth-toggle-btn"
							:class="{ active: isSearchFullwidth }"
							:title="isSearchFullwidth ? 'Favoriten anzeigen' : 'Vollbild'"
						>
							<span class="icon" v-html="isSearchFullwidth ? fullscreenExitIcon : fullscreenIcon"></span>
						</button>
					</div>
				</div>
				<FileList
					:items="items"
					:state="searchState"
					initialText="Führen Sie eine Suche aus"
					loadingText="Suche läuft..."
					emptyText="Keine Dateien oder Ordner gefunden"
					:columns="['name', 'size', 'modified']"
					:class="{ 'grid-view': isGridView }"
				/>
			</div>

		</div>
	</div>
</template>

<script>
console.log('APP tu dash')
// import BruteForceItem from './components/BruteForceItem'
import vSelect from 'vue-select'
import * as ncfiles from "@nextcloud/files"
import { davGetClient, davRootPath, getFavoriteNodes } from '@nextcloud/files'
import * as ncrouter from '@nextcloud/router'
import { generateUrl, getAppRootUrl, generateFilePath} from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import ViewGridIcon from '@mdi/svg/svg/view-grid.svg?raw'
import FormatListBulletedSquareIcon from '@mdi/svg/svg/format-list-bulleted-square.svg?raw'
import FullscreenIcon from '@mdi/svg/svg/fullscreen.svg?raw'
import FullscreenExitIcon from '@mdi/svg/svg/fullscreen-exit.svg?raw'

import FileList from './components/FileList'



const APP_ID = 'tu_dashboard'
const appRootUrl = getAppRootUrl(APP_ID)

// Load the enable_gridview setting from initial state
const enableGridView = loadState('tu_dashboard', 'enable_gridview', false)
console.log('enable_gridview setting:', enableGridView)

const client = davGetClient()
const favorites = await getFavoriteNodes(client)
console.log('FAVS', favorites)
console.log('FILES', ncfiles)
console.log('ROUTER', ncrouter)
export default {
	name: 'tu_dashboard',
	components: {
		'v-select': vSelect,
		FileList
	},
	data () {
		return {
			background: generateFilePath('tu_dashboard','img', 'conversory-tuuls-dam-header.jpg'),
			items: [],
			favorites,
			favoritesfolders: [],
			term: '',
			metaTerm: '',
			searching: false,
			searched: false,
			tags: '',
			selectedTags: [],
			systemTags: [],
			submitArrowUrl: appRootUrl + '/img/arrow.svg',
			userName: getCurrentUser().displayName,
			isSearchFullwidth: false,
			enableGridView,
			isGridView: false,
			viewGridIcon: ViewGridIcon,
			formatListBulletedSquareIcon: FormatListBulletedSquareIcon,
			fullscreenIcon: FullscreenIcon,
			fullscreenExitIcon: FullscreenExitIcon,

		};
	},
	computed: {
		searchState() {
			if (!this.searched) return 'initial'
			if (this.searching) return 'loading'
			return 'ready'
		},
		infoJSON: function () {
			return this.items.map(function (item) {
				return JSON.stringify(item.info);
			});
		},
		entryJSON: function () {
			return this.items.map(function (item) {
				return JSON.stringify(item);
			});
		},
	},
	mounted: function () {
		$.ajax({
			method: 'GET',
			url: generateUrl(`/apps/${APP_ID}/systemtags`)
		}).then(response => {
			this.systemTags = response
		})
	},
	methods: {
		/**
		 * @param int $bytes
		 *
		 * @return string
		 */
		/*
		public function humanReadable(int $bytes): string {
	  if ($bytes == 0) {
		return '0.00 B';
	  }

	  $s = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
	  $e = floor(log($bytes, 1024));

	  return round($bytes / pow(1024, $e), 2) . ' ' . $s[$e];
	}*/
		humanFileSize (size) {
			if (size === 0) {
				return '0.00 B';
			}
			let i = Math.floor(Math.log(size) / Math.log(1024));
			if (i < 1) {
				return '< 1 KB'
			}
			return (size / Math.pow(1024, i)).toFixed(i - 1) * 1 + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
		},
		search () {
			let searchTerm = this.term.toLowerCase()
			let metaTags = this.metaTerm.toLowerCase().split(' ')
			// remove empty strings
			metaTags = metaTags.filter(tag => tag !== '')
			console.log('TAGS', metaTags.join(","))
			console.log('SEARCH TERM', searchTerm)
			if (!RegExp(/\S/).test(searchTerm)) {
				searchTerm = "*  ";
			}
			if (searchTerm.length >= 3) {	// min 3 chars

				this.searched = true
				this.searching = true
				console.log('METATAGS', metaTags, metaTags.length)
				$.ajax({
					method: 'GET',
					url: generateUrl('/apps/fulltextsearch/v1/search'),
					data: {
						request: JSON.stringify({
								providers: 'all',
								search: searchTerm,
								page: 1,
								metatags: metaTags,
								options: {
									"files_local": "0",
									"files_extension": "",
									"tags": this.selectedTags.map(tag=>tag.toLowerCase()),
									"metatags": metaTags,
								},
								size: 100
							}
						)
					}
				}).then(response => {
					console.log('SEARCH RESPONSE', response)
					this.searching = false
					if (response.result[0].documents) {
						// Transform the documents to match favorites structure while preserving original data
						this.items = response.result[0].documents.map(item => ({
							...item,
							displayname: item.info.file,
							size: item.info.size,
							mtime: item.info.mtime,
							fileid: item.id
						}));
						console.log('ITEMS', this.items)
					} else {
						this.items = [];
					}
				});
			}
		},
		toggleSearchFullwidth() {
			this.isSearchFullwidth = !this.isSearchFullwidth;
		},
		toggleGridView() {
			this.isGridView = !this.isGridView;
		}
	}
}
</script>

<style>
.btn-search {
	color: white;
	background: var(--color-primary);
	border: 1px solid var(--color-primary);
	width: 30px;
	height: 40px;
	margin: 0;
	position: relative;
}

.btn-search:active {
	background: var(--color-primary) !important;
	border: 1px solid var(--color-primary) !important;
}

.btn-search .arrow {
	position: absolute;
	left: 60%;
	top: 50%;
	width: 50%;
	height: 50%;
	background-repeat: no-repeat;
	background-size: contain;
	transform: translate(-50%, -50%);
}

.search-term-container {
	display: flex;
	flex-wrap: wrap;
	max-width: 350px;
}

.style-chooser.v-select {
	max-width: 600px;
	min-width: 300px;
	width: 100%;
	margin: 0;
	margin-right: 5px;
	text-transform: none;
}

@media screen and (max-width: 767px) {
	.style-chooser.v-select {
		max-width: calc(100% - 40px);
	}
}

#term {
	min-width: 300px;
	height: 40px;
	margin: 0 5px 0 0;
}

@media screen and (max-width: 767px) {
	#term {
		margin-bottom: 5px;
	}
}

.style-chooser .vs__search {
	border: none !important;
}

.style-chooser .vs__dropdown-toggle {
	min-height: 40px;
	padding-bottom: 0;
}

.style-chooser .vs__actions {
	display: none;
}

.style-chooser .vs__selected {
	border: none;
	max-height: 32px;
	color: #707070;
	background: #EDEDED;
}

.style-chooser .vs__selected-options {
	min-height: 36px;
}

.style-chooser .vs__dropdown-option--highlight {
	color: #707070;
	background: #EDEDED;
}

.style-chooser .vs__dropdown-option--selected {
	color: #EDEDED;
	background: #707070;
}

.style-chooser .vs__dropdown-menu {
	top: calc(100% - 6px);
}

.style-chooser input.vs__search::placeholder {
	text-transform: none !important;
	font-variant: normal !important;
	font-variant-caps: normal !important;
	font-size: 18px !important;
	color: rgb(111, 111, 111) !important;
}

.style-chooser input.vs__search {
	padding-top: 6px !important;
	padding-bottom: 5px !important;
}

.style-chooser button.vs__deselect {
	display: flex;
	align-items: center;
}

#search-arrow {
	cursor: pointer;
}


.app-tu_dashboard .search-term-container input#meta-term {
	min-width: 300px;
	height: 40px;
	margin: 0 5px 0 0;
	font-size: 18px !important;
	outline: none;
	border: none;
}

.app-tu_dashboard .search-term-container button.btn-search {
	border: 1px solid var(--color-uni-accent-color, #245B78);
	background: var(--color-uni-accent-color, #245B78);
	border-radius: 0;
	font-weight: 400;
	padding: 6px 28px;
	color: white;
	font-size: 18px !important;
	display: flex;
	align-items: center;
	justify-content: center;
}

.app-tu_dashboard .search-term-container > * {
	width: 100%;
	margin-bottom: 10px !important;
	max-width: unset;
}

.app-tu_dashboard .search-term-container button.btn-search .arrow {
	width: 30px;
	height: 24px;
	background-size: contain;
	z-index: 1000;
	display: block;
	background-repeat: no-repeat;
	background-position: center right;
}

.app-tu_dashboard #filestable #fileList tr td {
	border-bottom: none;
}

.app-tu_dashboard #filestable #fileList tr td:not(:first-of-type) {
	padding-left: 1em;
}

.app-tu_dashboard #filestable #fileList .thumbnail {
	display: grid;
	place-items: center;
}

.app-tu_dashboard #filestable #fileList .thumbnail img {
	width: 100%;
	max-height: 64px;
	object-fit: contain;
}

.fullwidth-toggle-btn {
	background: transparent;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
	padding: 6px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 34px;
	height: 34px;
	transition: background-color 0.2s ease;
}

.fullwidth-toggle-btn:hover {
	background: var(--color-background-hover);
}

.fullwidth-toggle-btn.active {
	background: var(--color-primary-element);
	border-color: var(--color-primary-element);
}

.fullwidth-toggle-btn .icon {
	width: 16px;
	height: 16px;
	opacity: 0.7;
}

.fullwidth-toggle-btn:hover .icon {
	opacity: 1;
}

.view-toggle-btn {
	background: transparent;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
	padding: 6px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 34px;
	height: 34px;
	transition: background-color 0.2s ease;
}

.view-toggle-btn:hover {
	background: var(--color-background-hover);
}

.view-toggle-btn.active {
	background: var(--color-primary-element);
	border-color: var(--color-primary-element);
}


.view-toggle-btn .icon {
	width: 16px;
	height: 16px;
	opacity: 0.7;
}

.view-toggle-btn:hover .icon {
	opacity: 1;
}

/* Grid view styles */
.grid-view .filestable tbody {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 16px;
	padding: 16px 0;
}

.grid-view table.filestable {
	display: block;
}

.grid-view .filestable tbody tr {
	display: flex;
	flex-direction: column;
	align-items: center;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	padding: 16px;
	height: auto;
	min-height: 200px;
	border-bottom: none;
	text-align: center;
}

.grid-view .filestable tbody tr td {
	border-bottom: none;
	padding: 4px 0;
	width: 100%;
	display: flex;
	justify-content: center;
}

.grid-view .filestable tbody tr td.filename {
	order: 2;
	flex-direction: column;
}

.grid-view .filestable tbody tr td.size {
	order: 3;
	font-size: 0.9em;
	color: var(--color-text-maxcontrast);
}

.grid-view .filestable tbody tr td.modified {
	order: 4;
	font-size: 0.9em;
	color: var(--color-text-maxcontrast);
}

.grid-view .filestable tbody tr .thumbnail-wrapper {
	order: 1;
	margin-bottom: 8px;
}

</style>
