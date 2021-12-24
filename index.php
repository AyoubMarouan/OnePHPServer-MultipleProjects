<?php
	
	// load json config
	$f = fopen( "manager_req_config.json" ,  "r" );
	$config = json_decode( fread( $f , filesize( "manager_req_config.json" )  ) , TRUE); fclose( $f );

	$folder = $config["base_folder"];
	$file_to_display =  $folder . ( isset( $_GET['req'] )  ? $_GET['req'] : "" );

	// manager api handler
	if(  isset($_GET["req"]) && $_GET["req"] == $config["base_req"] . "/api" ){
		$path = isset( $_POST["path"] ) ? $_POST["path"] . "/*" : "C:\*" ;
		// check manager commands
		if( isset($_POST['action']) ){
			$req_if = array( "set_base" => "base_folder",  "set_escape_req" =>  "base_req" );
			$config[  $req_if[ $_POST["action"] ]   ] = $_POST["path"] . "\\";
			file_put_contents( "manager_req_config.json" , json_encode( $config )  );
			echo '{ "data": [ { "type": "file", "name": "Change saved successfully" } ] }';
			return;
		}
		// normal scrap
		$filenames = glob( $path );
		$data = array( "data" => array() );
		foreach($filenames as $elm){
			// check elm type 
			array_push($data["data"], 
				array(
					"type" => is_file( $elm ) ? "file" : "folder", 
	 				"name" => basename($elm)
				)
			);
		}
		echo json_encode( $data );
		exit();
	}

	// include file
	if ( isset($_GET["req"]) && $_GET["req"] != $config["base_req"] && file_exists( $file_to_display )) {
    	include( $file_to_display );
    	exit();
	}
	

?>



<!DOCTYPE html>
<html>
<head>
	<title> Request Manager </title>
	<link rel="stylesheet/less" type="text/css" href="manager_req_style.css" />
	<script src="https://cdn.jsdelivr.net/npm/less@4.1.1" ></script>
</head>
<body>

	<div class="container">


		<div class="directory">

			<div class="menu noselect">
				<span action="back"><img action="back" src="manager_req_img/return_icon.png"></span>
				<span action="set_as_base"> SET AS BASE </span>
			</div>

			<div class="path">
				<div class="input_search">
					<input type="text" name="path_url">
					<span class="noselect"> > </span>
				</div>
			</div>
			

			<div class="container"> </div>

		</div>

	</div>

	<script>

		class path_manager{

			constructor( api_url , folder_base ){
				// init
				var _this = this;
				this.api_url = api_url;
				this.input 			= document.querySelector(".input_search input");
				this.folder_display = document.querySelector(".directory .container");

				// GUI Joint
				this.input.onkeypress = _this.event_set_path.bind( _this );
				document.querySelectorAll(".container .directory  .menu span").forEach( elm => { elm.onclick = _this.handle_menu.bind( _this ) } );

				// init load
				this.input.value = folder_base.replace(/\\\//g, "/");
				this.api_call( "action" , { path : this.input.value } );
			}

			display_files( dict_items ){

				var files_type = {
					"folder" : `<div class="folder"> <img src="manager_req_img/folder.png">[NAME]</div>`,
					"file"   : `<div class="file">[NAME]</div>`,
				}

				var data = "";

				// sort by folder first
				dict_items.sort(function(x, y) {
				  if ( x["type"] == "file" && y["type"] == "folder" ) {
				    return 1;
				  }
				  if ( y["type"] == "file" && x["type"] == "folder" ) {
				    return -1;
				  }
				  return 0;
				});

				dict_items.forEach(
					elm => {
						data += files_type[ elm.type ].replace( "[NAME]" , elm.name );
					}
				);


				var _this = this;

				// display result
				this.folder_display.innerHTML = data;

				// GUI joint
				document.querySelectorAll(".container .directory .container div").forEach( elm => { elm.onclick = _this.handle_files.bind( _this ) } );

			}

			event_set_path( e ){
				if(e.keyCode === 13){
		            e.preventDefault();
		            this.api_call( "action" , { path : this.input.value } );
		        }
			}

			handle_menu( e ){

				var button_type = event.target.getAttribute( "action" );

				if( button_type == "back"){
					var path_value = this.input.value.split("\\");
					if( path_value.length <= 1 ) return;
					let r = path_value.pop();
					this.input.value = path_value.join( "\\" );
					this.api_call( "action" , { path : this.input.value } );
					return;
				}

				if( button_type == "set_as_base"){
					var path_value = this.input.value;
					this.api_call( "action" , { path: this.input.value , action: "set_base"  } );
					return;
				}
				
			}

			handle_files( e ){
				var name = event.target.innerText;
				var type = event.target.className;
				if( type == "folder" ){
					this.input.value = this.input.value + ( this.input.value.endsWith("\\") ? "" : "\\" ) + name;
					this.api_call( "action" , { path : this.input.value } );
				}
			}

			// api handle
			api_call( action , parameters ){

				const formData  = new FormData();
				for(const name in parameters) formData.append(name, parameters[name]);

				fetch(
					this.api_url , 
					{
						method: 'POST',
    					body: formData
    				}
				).then(
					res => res.json()
				).then(
					json => {
						// handle responce
						this.display_files( json[ "data" ] );
					}
				);

			}

		}

		controler = new path_manager( "<?= $config["base_req"] ?>/api" , "<?= addslashes($config["base_folder"]) ?>" );

	</script>

</body>
</html>

