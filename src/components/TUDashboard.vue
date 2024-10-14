<!--
  - @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @author Roeland Jago Douma <roeland@famdouma.nl>
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
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<div id="tu-dashboard" class="section kroisi">
		<h3 @click="syncMe"><a href="#">Willkommen {{ postTitle }}!</a></h3>
		<form @submit.prevent="search">
			<input type="text" id="term" name="term" placeholder="Suche nach Dateien" v-model="term">
		</form>
			<div id="files-list" v-if="items.length">
				<div class="files_header">
					<div class="files_div_checkbox">&nbsp;</div>
					<div class="files_div_thumb">&nbsp;</div>
					<div class="files_header_div files_div_name">Name</div>
				</div>

				<div class="provider_result">
					<div v-for="(item, index) in items"
						class="result_entry"
						:data-id="item.id"
						:data-link="item.link"
						:data-source="item.source"
						:data-info="infoJSON[index]"
						:data-result="entryJSON[index]"
						style="">
						<a :href="item.link" target="_self">
							<div class="result_template" style="opacity: 1; display: block; cursor: pointer;">
								<div class="files_result" :data-id="item.id" :data-type="item.info.type" :data-size="item.info.size" :data-file="item.info.file" :data-mime="item.info.mine" :data-mtime="item.info.mtime" :data-etag="item.info.etag" :data-permissions="item.info.permissions" :data-dir="item.info.dir" style="cursor: pointer;">
										<div class="files_div_checkbox" style="cursor: pointer;"></div>
										<div class="files_div_thumb files_result_div" :style="{'background-image': 'url(' + backgroundImageURL[index] + ')'}"></div>
										<div class="files_result_div files_div_name" style="cursor: pointer;">
												<div class="files_result_file" style="cursor: pointer;">
														<div id="title" class="files_result_title" style="cursor: pointer;">{{item.info.file}}</div>
														<div id="extract" class="files_result_extract" style="cursor: pointer;">{{item.excerpts[0]}}</div>
												</div>
										</div>
								</div>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>>
</template>

<script>
import axios from 'axios';
// import BruteForceItem from './components/BruteForceItem'

export default {
	name: 'App',
  props: ['postTitle'],
	components: {
	//	BruteForceItem
	},
	data: function() {
		return {
			items: [],
			term : '',
			newWhitelist: {
				ip: '',
				mask: ''
			}
		};
	},
	computed: {
		infoJSON: function() {
				return this.items.map(function(item) {
						return JSON.stringify(item.info);
				});
		},
		entryJSON: function() {
				return this.items.map(function(item) {
						return JSON.stringify(item);
				});
		},
		backgroundImageURL: function() {
				return this.items.map(function(item) {
						if(item.info.type == "dir")
							return "/apps/theming/img/core/filetypes/folder.svg";
						else
							return "/core/preview?fileId=" + item.id + "&x=32&y=32&forceIcon=0&c=" + item.info.etag;
				});
		},
	},
	methods: {
	    syncMe() {
	        $.ajax({
				method: 'GET',
				url: OC.generateUrl('/apps/tu_dashboard/sync_me')
			})
		}
	    ,
		search() {
			 $.ajax({
				method: 'GET',
				url: OC.generateUrl('/apps/fulltextsearch/v1/search'),
				data: {
					request: JSON.stringify( {
							providers: 'all',
							search: document.getElementById("term").value,
							page: 1,
							options: {"files_local":"0","files_extension":""},
							size: 20
						}
					)
				}
			}).then((response) => {
					this.items = response.result[0].documents
				}
			);
			/*axios.get(
				OC.generateUrl('apps/fulltextsearch/v1/search'),
				{
					data: {
						request: JSON.stringify( {
								providers: 'all',
								search: document.getElementById("term").value,
								page: 1,
								options: {"files_local":"0","files_extension":""},
								size: 20
							}
						)
					}
				})
				.then((response) => {
					console.log(response.data);
					console.log(this.items);
					this.items.push(response.data);
					console.log(this.items);
				}
				);*/
		}
	}
	/*beforeMount: function() {
		let requestToken = OC.requestToken;
		let tokenHeaders = { headers: { requesttoken: requestToken } };

		axios.get(OC.generateUrl('apps/bruteforcesettings/ipwhitelist'), tokenHeaders)
			.then((response) => {
			this.items = response.data;
		});
	},
	methods: {
		deleteWhitelist(id) {
			let requestToken = OC.requestToken;
			let tokenHeaders = { headers: { requesttoken: requestToken } };

			axios.delete(OC.generateUrl('apps/bruteforcesettings/ipwhitelist/{id}', {id: id}), tokenHeaders)
				.then((response) => {
					this.items = this.items.filter(item => item.id !== id);
				});
		},
		addWhitelist() {
			let requestToken = OC.requestToken;
			let tokenHeaders = { headers: { requesttoken: requestToken } };

			axios.post(
				OC.generateUrl('apps/bruteforcesettings/ipwhitelist'),
				{
					ip: this.newWhitelist.ip,
					mask: this.newWhitelist.mask
				},
				tokenHeaders)
				.then((response) => {
					console.log(response.data);
			console.log(this.items);
					this.items.push(response.data);
			console.log(this.items);

					this.newWhitelist.ip = '';
					this.newWhitelist.mask = '';
				}
			);
		}
	},*/
}
</script>
