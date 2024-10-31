
<template>
	<div id="tu-dashboard" class="">
		<div class="hero-image"
			 :style="{'background-image': 'url(' + background + ')'}">
			<div class="hero-text">
				<form @submit.prevent="search">
					<a href="#"><h3 class="welcomeText">Willkommen {{
							userName
						}}!</h3></a>
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
			<div style="padding: 15px; width: 100%; ">
				<h2>Favoriten</h2>
				<div id="app-content-favorites" class="viewcontainer hide-hidden-files has-comments">
					<FileList
						:items="favorites"
						state="ready"
						initialText="Initialisiere Favoriten..."
						loadingText="Lade Favoriten..."
						emptyText="Noch keine Favoriten vorhanden"
						:columns="['name', 'size', 'modified']"
					/>
				</div>
			</div>

			<div style="padding: 15px; width: 100%; ">
				<h2>Suchergebnis</h2>
				<FileList
					:items="items"
					:state="searchState"
					initialText="Führen Sie eine Suche aus"
					loadingText="Suche läuft..."
					emptyText="Keine Dateien oder Ordner gefunden"
					:columns="['name', 'size', 'modified']"
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
import { generateUrl, getAppRootUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

import FileList from './components/FileList'



const APP_ID = 'tu_dashboard'
const appRootUrl = getAppRootUrl(APP_ID)

const client = davGetClient()
const favorites = await getFavoriteNodes(client)
console.log('FAVS', favorites)
console.log('FILES', ncfiles)
console.log('ROUTER', ncrouter)
export default {
	name: 'tu_dashboard',
	props: ['background', 'favs', 'favfolders'],
	components: {
		'v-select': vSelect,
		FileList
	},
	data () {
		return {
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
		backgroundImageURL: function () {
			return this.items.map(function (item) {
				if (item.info.type == "dir")
					return generateUrl("/apps/theming/img/core/filetypes/folder.svg");
				else {
					return generateUrl("/core/preview?fileId=" + item.id + "&y=32&mimeFallback=true&a=1&a=1")
				}
			});
		},
		favoriteFileUrl: function () {
			return this.favorites.map(function (item) {
				return generateUrl(`/f/${item.fileid}`);

			});
		},
		favoriteBackgroundImageUrl: function () {
			return this.favorites.map(function (item) {
				console.log('FAV ITEM2', item)
				return generateUrl("/core/preview?fileId=" + item.fileid + "&y=32&mimeFallback=true&a=1&a=1");
			});
		},
	},
	mounted: function () {
		$.ajax({
			method: 'GET',
			url: generateUrl(`${appRootUrl}/systemtags`)
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
			let metaTerm = this.metaTerm.toLowerCase()
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
									"tags": this.selectedTags,
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


</style>
