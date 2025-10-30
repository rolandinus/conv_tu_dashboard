<template>

		<NcAppContent app-name="tuuls_dashboard">
			<div class="tu-dashboard">
				<section
					class="tu-dashboard__hero"
					:class="{ 'tu-dashboard__hero--with-background': hasBackground }"
					:style="heroStyle"
				>
					<div class="tu-dashboard__hero-body">
						<h2 id="tu-dashboard-hero-heading" class="tu-dashboard__welcome">
							{{ welcomeHeadline }}
						</h2>
						<form
							class="tu-dashboard__form"
							aria-labelledby="tu-dashboard-hero-heading"
							@submit.prevent="search"
						>
								<NcTextField
									v-model="term"
									type="search"
									input-id="tu-dashboard-term"
									label="Suche nach Dateien"
									:disabled="searching"
									clearable
								/>

								<div class="tu-dashboard__select-wrapper">
									<NcSelect
										v-model="selectedTags"
										multiple
										input-id="tu-dashboard-tags"
										:options="systemTags"
										placeholder="Suche nach Tags"
										:disabled="!systemTags.length"
									/>
									<button
										v-if="enableTagAndOrSwitch"
										class="tag-mode-toggle"
										role="switch"
										:aria-checked="tagMode === 'and'"
										:aria-label="tagModeAriaLabel"
										type="button"
										@click="toggleTagMode"
									>
										<span class="toggle-label" :class="{ 'is-active': tagMode === 'and' }">UND</span>
										<span class="toggle-icon" v-html="tagMode === 'and' ? toggleSwitchOffOutline : toggleSwitchOutline"></span>
										<span class="toggle-label" :class="{ 'is-active': tagMode === 'or' }">ODER</span>
									</button>
								</div>

								<NcTextField
									v-model="metaTerm"
									input-id="tu-dashboard-meta-term"
									label="Suche in Metadata"
									:disabled="searching"
									clearable
								/>

								<NcButton
									variant="primary"
									native-type="submit"
									class="tu-dashboard__submit"
									:disabled="isSearchDisabled"
								>
									Suchen
								</NcButton>
						</form>
					</div>
				</section>

				<section class="tu-dashboard__content" :class="{ 'tu-dashboard__content--full': isSearchFullwidth || favoritesDisabled }">
					<aside
						v-if="!favoritesDisabled && !isSearchFullwidth"
						class="tu-dashboard__favorites"
						aria-labelledby="tu-dashboard-favorites-heading"
					>
						<h3 id="tu-dashboard-favorites-heading" class="tu-dashboard__section-title">
							Favoriten
						</h3>
						<FileList
							:items="favorites"
							:state="favoritesState"
							initialText="Initialisiere Favoriten..."
							loadingText="Lade Favoriten..."
							emptyText="Noch keine Favoriten vorhanden"
							:columns="['name']"
							class="tu-dashboard__list tu-dashboard__list--favorites"
						/>
					</aside>

					<div class="tu-dashboard__results" aria-labelledby="tu-dashboard-results-heading">
						<div class="tu-dashboard__results-header">
							<h3 id="tu-dashboard-results-heading" class="tu-dashboard__section-title">
								Suchergebnis
							</h3>
							<div class="tu-dashboard__actions">
								<NcButton
									v-if="enableGridView"
									variant="tertiary"
									native-type="button"
									size="small"
									class="tu-dashboard__action"
									:aria-pressed="isGridView"
									:aria-label="isGridView ? 'Listenansicht' : 'Rasteransicht'"
									@click="toggleGridView"
								>
									<template #icon>
										<NcIconSvgWrapper :svg="isGridView ? formatListBulletedSquareIcon : viewGridIcon" />
									</template>
								</NcButton>
								<NcButton
									v-if="!favoritesDisabled"
									variant="tertiary"
									native-type="button"
									size="small"
									class="tu-dashboard__action"
									:aria-pressed="isSearchFullwidth"
									:aria-label="isSearchFullwidth ? 'Favoriten anzeigen' : 'Vollbild'"
									@click="toggleSearchFullwidth"
								>
									<template #icon>
										<NcIconSvgWrapper :svg="isSearchFullwidth ? fullscreenExitIcon : fullscreenIcon" />
									</template>
								</NcButton>
							</div>
						</div>

						<FileList
							:items="items"
							:state="searchState"
							initialText="Führen Sie eine Suche aus"
							loadingText="Suche läuft..."
							emptyText="Keine Dateien oder Ordner gefunden"
							:columns="searchColumns"
							:grid="isGridView"
							class="tu-dashboard__list"
						/>
					</div>
				</section>
			</div>
		</NcAppContent>

</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import axios from '@nextcloud/axios'
import { davGetClient, getFavoriteNodes } from '@nextcloud/files'
import { generateFilePath, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'

import ViewGridIcon from '@mdi/svg/svg/view-grid.svg?raw'
import FormatListBulletedSquareIcon from '@mdi/svg/svg/format-list-bulleted-square.svg?raw'
import FullscreenIcon from '@mdi/svg/svg/fullscreen.svg?raw'
import FullscreenExitIcon from '@mdi/svg/svg/fullscreen-exit.svg?raw'
import ToggleSwitchOutline from '@mdi/svg/svg/toggle-switch-outline.svg?raw'
import ToggleSwitchOffOutline from '@mdi/svg/svg/toggle-switch-off-outline.svg?raw'

import FileList from './components/FileList'

const APP_ID = 'tuuls_dashboard'

const enableGridViewState = true;// loadState(APP_ID, 'enable_gridview', false)
const enableTagAndOrSwitchState = true;// loadState(APP_ID, 'enable_tag_and_or_switch', true)
const disableFavoritesState = loadState(APP_ID, 'disable_favorites', false)
const customHeaderImg = loadState(APP_ID, 'custom_header_img', '')
const disabledColumnsState = loadState(APP_ID, 'disabled_columns', [])

let backgroundUrl = ''
if (!customHeaderImg) {
	backgroundUrl = generateFilePath(APP_ID, 'img', 'conversory-tuuls-dam-header.jpg')
} else if (customHeaderImg === 'none') {
	backgroundUrl = ''
} else {
	backgroundUrl = generateFilePath(APP_ID, 'img', customHeaderImg)
}

const client = davGetClient()

export default {
	name: APP_ID,
	components: {
		NcAppContent,
		NcContent,
		NcButton,
		NcTextField,
		NcSelect,
		NcIconSvgWrapper,
		FileList,
	},
	data() {
		return {
			background: backgroundUrl,
			items: [],
			favorites: [],
			favoritesState: 'loading',
			term: '',
			metaTerm: '',
			searching: false,
			searched: false,
			selectedTags: [],
			systemTags: [],
			tagMode: 'and',
			userName: getCurrentUser().displayName,
			isSearchFullwidth: false,
			enableGridView: enableGridViewState,
			enableTagAndOrSwitch: enableTagAndOrSwitchState,
			favoritesDisabled: disableFavoritesState,
			isGridView: false,
			viewGridIcon: ViewGridIcon,
			formatListBulletedSquareIcon: FormatListBulletedSquareIcon,
			fullscreenIcon: FullscreenIcon,
			fullscreenExitIcon: FullscreenExitIcon,
			toggleSwitchOutline: ToggleSwitchOutline,
			toggleSwitchOffOutline: ToggleSwitchOffOutline,
			disabledColumns: disabledColumnsState,
		}
	},
	computed: {
		pageHeading() {
			return 'Dashboard'
		},
		welcomeHeadline() {
			return `Willkommen ${this.userName}!`
		},
		heroStyle() {
			if (!this.background) {
				return {}
			}
			return {
				'--tu-dashboard-hero-image': `url(${this.background})`,
			}
		},
		hasBackground() {
			return Boolean(this.background)
		},
		tagModeAriaLabel() {
			return this.tagMode === 'and'
				? 'Tagfilter im UND-Modus. Aktivieren Sie zum Wechsel in den ODER-Modus.'
				: 'Tagfilter im ODER-Modus. Aktivieren Sie zum Wechsel in den UND-Modus.'
		},
		searchState() {
			if (!this.searched) {
				return 'initial'
			}
			if (this.searching) {
				return 'loading'
			}
			return 'ready'
		},
		searchColumns() {
			const allColumns = ['name', 'size', 'modified', 'beschreibung', 'tags', 'info']
			return allColumns.filter(col => !this.disabledColumns.includes(col))
		},
		isSearchDisabled() {
			return this.searching
		},
	},
	created() {
		this.fetchFavorites()
	},
	mounted() {
		this.loadSystemTags()
	},
	methods: {
		fetchFavorites() {
			if (this.favoritesDisabled) {
				this.favoritesState = 'ready'
				this.favorites = []
				return
			}
			this.favoritesState = 'loading'
			getFavoriteNodes(client)
				.then(nodes => {
					this.favorites = nodes
					this.favoritesState = 'ready'
				})
				.catch(() => {
					this.favorites = []
					this.favoritesState = 'ready'
				})
		},
		loadSystemTags() {
			axios.get(generateUrl(`/apps/${APP_ID}/systemtags`))
				.then(response => {
					this.systemTags = Array.isArray(response.data)
						? response.data.map(tag => ({
							value: tag,
							label: tag,
						}))
						: []
				})
				.catch(() => {
					this.systemTags = []
				})
		},
		search() {
			let searchTerm = this.term.trim().toLowerCase()
			let metaTags = this.metaTerm.trim().toLowerCase().split(' ')
			metaTags = metaTags.filter(tag => tag !== '')
			const normalizedTags = this.selectedTags
				.map(tag => {
					if (typeof tag === 'string') {
						return tag
					}
					if (tag && typeof tag === 'object') {
						return tag.value || tag.label || ''
					}
					return ''
				})
				.filter(Boolean)
				.map(tag => tag.toLowerCase())

			if (!/\S/.test(searchTerm)) {
				searchTerm = '*  '
			}

			if (searchTerm.length >= 3) {
				this.searched = true
				this.searching = true
				axios.get(generateUrl('/apps/fulltextsearch/v1/search'), {
					params: {
						request: JSON.stringify({
							providers: 'all',
							search: searchTerm,
							page: 1,
									metatags: metaTags,
									options: {
										files_local: '0',
										files_extension: '',
										tags: normalizedTags,
										metatags: metaTags,
										tag_mode: this.tagMode,
							},
							size: 100,
						}),
					},
				}).then(response => {
					const result = response.data
					this.searching = false
					if (result.result?.[0]?.documents) {
						this.items = result.result[0].documents.map(item => ({
							...item,
							displayname: item.info.file,
							size: item.info.size,
							mtime: item.info.mtime,
							fileid: item.id,
						}))
					} else {
						this.items = []
					}
				}).catch(() => {
					this.searching = false
					this.items = []
				})
			}
		},
		toggleSearchFullwidth() {
			this.isSearchFullwidth = !this.isSearchFullwidth
		},
		toggleGridView() {
			this.isGridView = !this.isGridView
		},
		toggleTagMode() {
			this.tagMode = this.tagMode === 'and' ? 'or' : 'and'
		},
	},
}
</script>

<style>
.tu-dashboard {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
	padding: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 3);
	padding-top: 0;
	width: 100%;
	box-sizing: border-box;
	min-height: 100%;
	flex: 1 1 auto;
	height: 100%;
	overflow: hidden;
}

.tu-dashboard__hero {
	position: relative;
	overflow: hidden;
	border-radius: var(--border-radius-large);
	background-color: var(--color-background-muted);
	padding: calc(var(--default-grid-baseline) * 6) calc(var(--default-grid-baseline) * 8);
	color: var(--color-primary-text);
	width: 100vw;
	max-width: 100vw;
	box-sizing: border-box;
	margin-left: calc(50% - 50vw);
	height: 400px;
	display: flex;
	justify-content: center;
	align-items: center;
}

.tu-dashboard__hero--with-background {
	background-image: var(--tu-dashboard-hero-image);
	background-position: center;
	background-size: cover;
}

.tu-dashboard__hero::after {
	content: '';
	position: absolute;
	inset: 0;
	background: linear-gradient(135deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.1));
	pointer-events: none;
	z-index: 0;
}

.tu-dashboard__hero-body {
	position: relative;
	z-index: 1;
	display: grid;
	gap: calc(var(--default-grid-baseline) * 3);
	max-width: 520px;
	min-width: 350px;
	margin: 0 auto;
}

.tu-dashboard__welcome {
	margin: 0;
	font-size: 1.75rem;
	font-weight: 600;
	color: var(--color-primary-text);
}

.tu-dashboard__form {
	display: grid;
	gap: calc(var(--default-grid-baseline) * 2);
	width: 100%;
}

.tu-dashboard__select-wrapper {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	width: 100%;
	margin-block-start: 6px;
}

.v-select.select {
	margin-bottom: 0 !important;
	width: 100%;
}

.tag-mode-toggle {
	display: inline-flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline));
	padding: 8px !important;
	background-color: var(--color-primary-element) !important;
	user-select: none;
	transition: background-color var(--animation-quick) ease, border-color var(--animation-quick) ease;
	justify-content: center;
	margin-top:0 !important;
	margin-bottom:0 !important;
	height: 34px !important;
}

.tag-mode-toggle:hover {
	background-color: var(--color-primary-element-hover);
}

.tag-mode-toggle:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
}

.tag-mode-toggle:active {
	background-color: var(--color-background-muted);
}

.tag-mode-toggle .toggle-label {
	font-size: 0.8rem;
	font-weight: 600;
	color: var(--color-primary-element-text);
	letter-spacing: 0.04em;
    opacity: 0.5;
    filter: saturate(0.7);
}

.tag-mode-toggle .toggle-label.is-active {
    opacity: 1;
    filter: none;
}

.tag-mode-toggle .toggle-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 28px;
	height: 28px;
}

.tag-mode-toggle .toggle-icon svg {
	width: 22px;
	height: 22px;
	fill: var(--color-primary-element-text);
}


.tu-dashboard__submit {
	margin-block-start: 6px;
}

.tu-dashboard__content {
	display: grid;
	grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
	gap: calc(var(--default-grid-baseline) * 3);
	width: 100%;
	align-items: stretch;
	flex: 1 1 auto;
	min-height: 0;
}

.tu-dashboard__content--full {
	display: block;
}

.tu-dashboard__content--full .tu-dashboard__favorites,
.tu-dashboard__content--full .tu-dashboard__results {
	grid-column: 1 / -1;
}

.tu-dashboard__favorites {
	background-color: transparent;
	border-radius: var(--border-radius);
	padding: calc(var(--default-grid-baseline) * 2);
	min-height: 0;
	overflow-y: auto;
}

.tu-dashboard__results {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	min-height: 0;
	overflow: hidden;
}

.tu-dashboard__results-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: calc(var(--default-grid-baseline) * 2);
}

.tu-dashboard__section-title {
	margin: 0;
	font-size: 1.25rem;
	font-weight: 600;
}

.tu-dashboard__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 1.5);
}

.tu-dashboard__action {
	padding: 0;
}

.tu-dashboard__list {
	background-color: var(--color-background-default);
	border-radius: var(--border-radius);
	border: 1px solid var(--color-border);
	box-shadow: var(--box-shadow-level-1);
	padding: calc(var(--default-grid-baseline) * 2);
	min-height: 0;
	overflow: auto;
	height: 100%;
}

.tu-dashboard__list--favorites {
	background-color: transparent;
	border: none;
	box-shadow: none;
	padding: 0;
	overflow: visible;
}

@media (max-width: 768px) {
	.tu-dashboard__hero {
		padding: calc(var(--default-grid-baseline) * 4);
	}

	.tu-dashboard__form-row {
		flex-direction: column;
		align-items: stretch;
	}

	.tu-dashboard__actions {
		justify-content: flex-end;
	}
}

@media (max-width: 1100px) {
	.tu-dashboard__content {
		grid-template-columns: 1fr;
	}

	.tu-dashboard__favorites,
	.tu-dashboard__results {
		padding: calc(var(--default-grid-baseline) * 2) 0;
	}
}

</style>
