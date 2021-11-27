<?php
	include_once('includes/connect_database.php'); 
	include_once('functions.php'); 
	require_once("thumbnail_images.class.php");
?>
<div id="content" class="container col-md-12">
	<?php 
		$sql_query = "SELECT cid, category_name 
			FROM tbl_category 
			ORDER BY cid ASC";
				
		$stmt_category = $connect->stmt_init();
		if($stmt_category->prepare($sql_query)) {	
			// Execute query
			$stmt_category->execute();
			// store result 
			$stmt_category->store_result();
			$stmt_category->bind_result($category_data['cid'], 
				$category_data['category_name']
				);		
		}
			
		//$max_serve = 10;
			
		if(isset($_POST['btnAdd'])){
			$cid = $_POST['cid'];
				
			// get image info
			$image = $_FILES['image']['name'];
			$image_error = $_FILES['image']['error'];
			$image_type = $_FILES['image']['type'];			
				
			// create array variable to handle error
			$error = array();
			
				
			if(empty($cid)){
				$error['cid'] = " <span class='label label-danger'>Required, please fill out this field!!</span>";
			}				
				
			if(empty($image)){
				$error['image'] = " <span class='label label-danger'>Required, please fill out this field!!</span>";
			}
			
			// common image file extensions
			$allowedExts = array("gif", "jpeg", "jpg", "png");
			
			// get image file extension
			error_reporting(E_ERROR | E_PARSE);
			$extension = end(explode(".", $_FILES["image"]["name"]));
					
			if($image_error > 0){
				$error['image'] = " <span class='label label-danger'>Image Not Uploaded!!</span>";
			}else if(!(($image_type == "image/gif") || 
				($image_type == "image/jpeg") || 
				($image_type == "image/jpg") || 
				($image_type == "image/x-png") ||
				($image_type == "image/png") || 
				($image_type == "image/pjpeg")) &&
				!(in_array($extension, $allowedExts))){
			
				$error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
			}
				
			if( 
				!empty($cid) && 
				empty($error['image'])) {
				
				// create random image file name
				$string = '0123456789';
				$file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
				$function = new functions;
				$image = $function->get_random_string($string, 4)."-".date("Y-m-d").".".$extension;
					
				// upload new image
				$unggah = 'upload/'.$image;
				$upload = move_uploaded_file($_FILES['image']['tmp_name'], $unggah);

				error_reporting(E_ERROR | E_PARSE);
				copy($image, $unggah);
									 
											$thumbpath= 'upload/thumbs/'.$image;
											$obj_img = new thumbnail_images();
											$obj_img->PathImgOld = $unggah;
											$obj_img->PathImgNew =$thumbpath;
											$obj_img->NewWidth = 150;
											$obj_img->NewHeight = 150;
											if (!$obj_img->create_thumbnail_images()) 
												{
												echo "Thumbnail not created... please upload image again";
													exit;
												}	 
		
				// insert new data to menu table
				$sql_query = "INSERT INTO tbl_gallery (cat_id, image)
						VALUES(?, ?)";
						
				$upload_image = $image;
				$stmt = $connect->stmt_init();
				if($stmt->prepare($sql_query)) {	
					// Bind your variables to replace the ?s
					$stmt->bind_param('ss', 
								$cid, 
								$upload_image
								);
					// Execute query
					$stmt->execute();
					// store result 
					$result = $stmt->store_result();
					$stmt->close();
				}
				
				if($result){
					$error['add_menu'] = " <span class='label label-primary'>Success added</span>";
				}else {
					$error['add_menu'] = " <span class='label label-danger'>Failed</span>";
				}
			}
				
			}
	?>
	<div class="col-md-12">
	<h1>Add Image <?php echo isset($error['add_menu']) ? $error['add_menu'] : '';?></h1>
	<hr />
	</div>

	<div class="col-md-12">
	<form method="post" enctype="multipart/form-data">

	<div class="col-md-9">


	    <label>Category :</label><?php echo isset($error['cid']) ? $error['cid'] : '';?>
		<select name="cid" class="form-control">
			<?php while($stmt_category->fetch()){ ?>
				<option value="<?php echo $category_data['cid']; ?>"><?php echo $category_data['category_name']; ?></option>
			<?php } ?>
		</select>
		
		<br/>
		<label>Image :</label><?php echo isset($error['image']) ? $error['image'] : '';?>
		<input type="file" name="image" id="image"/>


	</div>
	
	<br/>
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">Action</div>
				<div class="panel-body">
					<input type="submit" class="btn-primary btn" value="Add" name="btnAdd" />&nbsp;
					<input type="reset" class="btn-danger btn" value="Clear"/>
				</div>
		</div>
	</div>
	</form>
	</div>	
	<div class="separator"> </div>
</div>
			

<?php 
	$stmt_category->close();
	include_once('includes/close_database.php'); ?>