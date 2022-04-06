<?php
	echo '<!DOCTYPE html>';
	echo '<html>';
		echo '<head>';	
			echo '<title>Thermal Master Item</title>';
			echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" charset="utf-8">';
			echo '<script type="text/javascript" src="'.base_url("assets/suite6/codebase/suite.js?v=6.5.8").'"></script>';
			echo '<link rel="stylesheet" href="'.base_url("assets/suite6/codebase/suite.css?v=6.5.8").'">';
			echo '<link rel="stylesheet" href="'.base_url("assets/font-awesome/css/font-awesome.css").'">';
			echo '<link href="https://fonts.googleapis.com/css?family=Alfa Slab One" rel="stylesheet">';
			
			echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/4.4.95/css/materialdesignicons.css?v=6.5.8" media="all" rel="stylesheet" type="text/css">';
			echo '
				<style>
					.dhx_sample-container {
						-webkit-box-orient: vertical;
						-webkit-box-direction: normal;
						-webkit-flex-direction: column;
						-ms-flex-direction: column;
						flex-direction: column;
					}
					.toolbar-title-show {
						color: red;
						font-weight: bold;
						font-family: "Impact,Charcoal,sans-serif",
						font-size: 45px;
					}
				</style>
			';
		echo '</head>';
		echo '<body onload="doLoad()" >';
			echo '<section class="dhx_sample-container">';
				echo '<div class="dhx_sample-container__widget dhx_widget--bordered" id="toolbar"></div>';
				echo '<div class="dhx_sample-container__widget " id="layout_container" style="width: 100%;height:100%;"></div>';
			echo '</section>';
		echo '</body>';
	echo '</html>';

?>

<script>
	var w = window.innerWidth;
	var h = window.innerHeight;
	function doLoad() 
	{
		var toolbar;
		var layout;
		var grid;
		var form;

		// Toolbar
			initToolbar();
		// Layout
			initLayout();
		// Grid
			initGrid();
		// form
			initForm();
	}

	// toolbar
	function initToolbar() 
	{
		var data = [
			{
				id: "other",
				icon: "fa fa-database",
				type: "button",
				view: "link",
				circle: true,
				color: "secondary",
			},
			{
				type: "separator"
			},
			{
				type: "text",
				value: "THERMAL MASTER DATA",
				tooltip: "Thermal Master data Details",
				css: "toolbar-title-show"
				
			},
			{
				type: "separator"
			},
			{
				type: "spacer"
			},
			// {
			// 	type: "separator"
			// },
			// {
			// 	id: "save",
			// 	value: "Save",
			// 	icon: "fa fa-floppy-o",
			// 	tooltip: "Notifications",
			// },
			{
				type: "separator"
			},
			{
				id: "views",
				value: "Master Data Details",
				icon: "fa fa-list",
				items: [
					{
						id: "main_master",
						value: "Master Data (Main) Views",
						icon: "fa fa-list-alt",
					},
					{
						id: "material",
						value: "Material Views",
						icon: "fa fa-list-alt",
					},
					{
						id: "ink",
						value: "Ink Views",
						icon: "fa fa-list-alt",
					}
				]
			},
			{
				id: "file",
				value: "Import/Download",
				icon: "fa fa-file",
				items: [
					{
						id: "import",
						value: "Import as Excel (.XLSX) File",
						icon: "fa fa-upload"
					},
					{
						id: "export",
						value: "Download as Excel (.XLSX) File",
						icon: "fa fa-download"
					},
					{
						id: "export_sample",
						value: "Download Sample XLSX File",
						icon: "fa fa-file-excel-o"
					}
				]
			},
			{
				type: "separator"
			},
			// {
			// 	id: "notifications",
			// 	icon: "mdi mdi-bell",
			// 	tooltip: "Notifications",
			// 	count: 7,
			// 	"type": "button",
			// 	"view": "link",
			// 	"color": "secondary",
			// 	"circle": true,
			// },
			{
				id: "avatar",
				icon: "fa fa-user-circle",
				circle: true,
				color: "secondary",
				view: "link",
				tooltip: "User",
			}
		];
		toolbar = new dhx.Toolbar("toolbar", {
			css: "dhx_widget--bordered dhx_widget--bg_gray"
		});
		toolbar.data.parse(data);
	}

	// layout
	function initLayout()
	{
		layout = new dhx.Layout("layout_container", {
			cols: [
				{ header: "", id: "masterData", height:h-80, cols: [
					{ header: "", id: "loadData", cols: [
						{ header: "Master Data Views", id: "grid", width:800 },
						{ header: "Details", id: "form" }
					]}
				]}
				
			]
		});
	}

	// grid
	function initGrid() 
	{
		var dataset = [
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "1",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			},
			{
				"index": "2",
				"form_type": "PAXAR",
				"internal_item": "2-470522-121-00",
				"rbo": "NIKE"
			}
		];
		var grid = new dhx.Grid(null, {
			columns: [
				{ width: 50, id: "index", header: [{ text: "#" }] },
				{ width: 100, id: "form_type", header: [{ text: "Form Type" }] },
				{ width: 200, id: "internal_item", header: [{ text: "Internal Item" }] },
				{ width: 200, id: "rbo", header: [{ text: "RBO" }] }
			],
			selection: true,
			editable: true,
			data: dataset,
			autoWidth:true,
			// adjust: true,
			selection:"row",
			resizable: true,
			// dragMode: "both", 
			dragCopy: true
		});

		layout.getCell("grid").attach(grid);
	}

	function initForm() 
	{
		form = new dhx.Form(null, {
			css: "dhx_widget--bg_white dhx_widget--bordered",
			rows: [
				{
				type: "input",
				gravity: false,
				label: "Name",
				icon: "dxi dxi-magnify",
				placeholder: "John Doe",
				name: "name"
				},
				{
				type: "input",
				gravity: false,
				label: "Email",
				placeholder: "jd@mail.name",
				name: "email"
				},
				{
				type: "input",
				gravity: false,
				inputType: "password",
				label: "Password",
				placeholder: "********",
				name: "password"
				},
				{	
				type: "checkbox",
				gravity: false,
				label: "I agree",
				labelPosition: "right",
				value: "checkboxvalue",
				id: "agree",
				name: "agree"
				},
				{
				type: "button",
				gravity: false,
				value: "Send",
				size: "medium",
				view: "flat",
				submit: true,
				color: "primary"
				},
			]
		});

		layout.getCell("form").attach(form);
	}
</script>

