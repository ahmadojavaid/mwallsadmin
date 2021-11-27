<?php
	include_once('includes/connect_database.php'); 
	include_once('functions.php'); 
	require_once("thumbnail_images.class.php");
?>
<div id="content" class="container col-md-12">
	<?php 
	
		if(isset($_GET['id'])){
			$ID = $_GET['id'];
		}else{
			$ID = "";
		}
		
		// create array variable to store category data
		$category_data = array();
			
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
			
		$sql_query = "SELECT image FROM tbl_gallery WHERE id = ?";
		
		$stmt = $connect->stmt_init();
		if($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			$stmt->bind_param('s', $ID);
			// Execute query
			$stmt->execute();
			// store result 
			$stmt->store_result();
			$stmt->bind_result($previous_image);
			$stmt->fetch();
			$stmt->close();
		}
				
		if(isset($_POST['btnEdit'])){

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
			
			// common image file extensions
			$allowedExts = array("gif", "jpeg", "jpg", "png");
			
			// get image file extension
			error_reporting(E_ERROR | E_PARSE);
			$extension = end(explode(".", $_FILES["image"]["name"]));
			
			if(!empty($image)){
				if(!(($image_type == "image/gif") || 
					($image_type == "image/jpeg") || 
					($image_type == "image/jpg") || 
					($image_type == "image/x-png") ||
					($image_type == "image/png") || 
					($image_type == "image/pjpeg")) &&
					!(in_array($extension, $allowedExts))){
					
					$error['image'] = "*<span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
				}
			}
			
					
			if( 
				!empty($cid) && 
				empty($error['image'])){
				
				if(!empty($image)){
					
					// create random image file name
					$string = '0123456789';
					$file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
					$function = new functions;
					$image = $function->get_random_string($string, 4)."-".date("Y-m-d").".".$extension;
				
					// delete previous image
					$delete = unlink('upload/'."$previous_image");
					$delete = unlink('upload/thumbs/'."$previous_image");
					
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
	  
					// updating all data
					$sql_query = "UPDATE tbl_gallery SET cat_id = ?, image = ? WHERE id = ?";
					
					$upload_image = $image;
					$stmt = $connect->stmt_init();
					if($stmt->prepare($sql_query)) {	
						// Bind your variables to replace the ?s
						$stmt->bind_param('sss', 
									$cid,
									$upload_image,
									$ID);
						// Execute query
						$stmt->execute();
						// store result 
						$update_result = $stmt->store_result();
						$stmt->close();
					}
				}else{
					
					// updating all data except image file
					$sql_query = "UPDATE tbl_gallery SET cat_id = ? WHERE id = ?";
							
					$stmt = $connect->stmt_init();
					if($stmt->prepare($sql_query)) {	
						// Bind your variables to replace the ?s
						$stmt->bind_param('ss', 
									$cid,
									$ID );
						// Execute query
						$stmt->execute();
						// store result 
						$update_result = $stmt->store_result();
						$stmt->close();
					}
				}
					
				// check update result
				if($update_result){
					$error['update_data'] = " <span class='label label-primary'>Update Successfull.</span>";
				}else{
					$error['update_data'] = " <span class='label label-danger'>Update Failed</span>";
				}
			}
			
		}
		
		// create array variable to store previous data
		$data = array();
			
		$sql_query = "SELECT * FROM tbl_gallery WHERE id = ?";
			
		$stmt = $connect->stmt_init();
		if($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			$stmt->bind_param('s', $ID);
			// Execute query
			$stmt->execute();
			// store result 
			$stmt->store_result();
			$stmt->bind_result($data['id'], 
					$data['cid'],
					$data['image_date'],
					$data['image']
					);
			$stmt->fetch();
			$stmt->close();
		}
		
			
	?>
	<div class="col-md-8">
	<h1>Edit News <?php echo isset($error['update_data']) ? $error['update_data'] : '';?></h1>
	<hr />
	</div>
	<form method="post" enctype="multipart/form-data">
	<div class="col-md-9">

	    <label>Category :</label><?php echo isset($error['cid']) ? $error['cid'] : '';?>
		<select name="cid" class="form-control">
			<?php while($stmt_category->fetch()){ 
				if($category_data['cid'] == $data['cid']){?>
					<option value="<?php echo $category_data['cid']; ?>" selected="<?php echo $data['cid']; ?>" ><?php echo $category_data['category_name']; ?></option>
				<?php }else{ ?>
					<option value="<?php echo $category_data['cid']; ?>" ><?php echo $category_data['category_name']; ?></option>
				<?php }} ?>
		</select>
		
	    <br/>
		<label>Image :</label><?php echo isset($error['image']) ? $error['image'] : '';?>
		<input type="file" name="image" id="image"/><br />
		<img src="upload/<?php echo $data['image']; ?>" width="210" height="160"/>
	</div>
		
	<div class="col-md-3">
	<br/>
		<div class="panel panel-default">
			<div class="panel-heading">Action</div>
				<div class="panel-body">
					<input type="submit" class="btn-primary btn" value="Update" name="btnEdit" />
				</div>
		</div>
	</div>
	</form>
	<div class="separator"> </div>
</div>

<?php 
	$stmt_category->close();
	include_once('includes/close_database.php'); ?>