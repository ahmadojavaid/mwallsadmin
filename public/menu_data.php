<?php
	include_once('includes/connect_database.php'); 
?>

<div id="content" class="container col-md-12">
	<?php 
		if(isset($_GET['id'])){
			$ID = $_GET['id'];
		}else{
			$ID = "";
		}
		
		// create array variable to store data from database
		$data = array();
		
		// get all data from menu table and category table
		$sql_query = "SELECT image 
				FROM tbl_gallery m, tbl_category c
				WHERE m.id = ? AND m.cat_id = c.cid";
		
		$stmt = $connect->stmt_init();
		if($stmt->prepare($sql_query)) {	
			// Bind your variables to replace the ?s
			$stmt->bind_param('s', $ID);
			// Execute query
			$stmt->execute();
			// store result 
			$stmt->store_result();
			$stmt->bind_result($data['image']
					);
			$stmt->fetch();
			$stmt->close();
		}
		
	?>

<div>
	<h1>View Full Image</h1>
	<form method="post">
		<table table class='table'>
			<td class="detail"><img src="upload/<?php echo $data['image']; ?>"/></td>
		
		</table>
		
	</form>
	<div id="option_menu">
			<a href="edit-menu.php?id=<?php echo $ID; ?>"><button class="btn btn-primary">Edit</button></a>
			<a href="delete-menu.php?id=<?php echo $ID; ?>"><button class="btn btn-danger">Delete</button></a>
	</div>
	<br>
	</div>
				
	<div class="separator"> </div>
</div>
			
<?php include_once('includes/close_database.php'); ?>