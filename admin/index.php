<?php

// Connect to database
include('../includes/db_login.php');


include('includes/admin_header.php');
?>

                <img class="pull-right visible-desktop" src="../img/puppies/puppy_4.jpg" style="max-height:200px; margin-top:-50px;">
		<p class="lead">Welcome to the administration area.</p>
		<p>You can edit papers and datasets here, as well as hooking up datasets with their files on the pipeline server.</p>
		
		<hr>
		
		<?php
		$query = "SELECT `papers`.* FROM `papers` LEFT JOIN `datasets` ON `papers`.`id` = `datasets`.`paper_id` WHERE `datasets`.`id` IS NULL ORDER BY `papers`.`first_author`";
		$results = mysql_query($query);
		if(mysql_num_rows($results) > 0):
		?>
		<h3>Papers without any datasets</h3>
		<p>These papers do not have any datasets associated with them: <small><em>(click to add datasets)</em></small></p>
		
		<table class="table table-condensed table-bordered table-striped table-hover small" id="papers_missing_datasets_table">
			<tr>
				<th>First Author</th>
				<th>Year</th>
				<th>Title</th>
				<th>Requested By</th>
			</tr>
			<?php
			while($result = mysql_fetch_array($results)): ?>
			<tr id="<?= $result['id'] ?>">
				<td><?= $result['first_author'] ?></td>
				<td><?= $result['year'] ?></td>
			   <td><?= stripslashes($result['paper_title']) ?></td>
				<td><?= $result['requested_by'] ?></td>
			</tr>
			<?php endwhile; // papers without dataset sql while loop ?>
		</table>
		
		
		<?php endif; // of of check for papers without any datasets
		
// LOOK FOR DATASETS WITHOUT FILES
                 $query = "SELECT DISTINCT `papers`.* FROM `papers` 
                              LEFT JOIN `datasets` ON `papers`.`id` = `datasets`.`paper_id` 
                              LEFT JOIN `files_aligned` ON `datasets`.`id` = `files_aligned`.`dataset_id` 
                              LEFT JOIN `files_raw` ON `datasets`.`id` = `files_raw`.`dataset_id` 
                              LEFT JOIN `files_derived` ON `datasets`.`id` = `files_derived`.`dataset_id` 
                              WHERE `datasets`.`id` IS NOT NULL AND `files_aligned`.`id` IS NULL 
                                 AND `files_raw`.`id` IS NULL 
                                 AND `files_derived`.`id` IS NULL 
                              ORDER BY `papers`.`first_author`";
$results = mysql_query($query);
if(mysql_num_rows($results) > 0 ) {
?>

<h3>Papers with datasets but no files</h3>

<table class="table table-condensed table-bordered table-hover table-striped small" id="papers_missing_files_table">
    <tr>
    <th>First Author</th>
    <th>Year</th>
    <th>Title</th>
    <th>Requested By</th>
    </tr>

<?php
while($result = mysql_fetch_array($results)) {
  ?>
  <tr id="<?= $result['id'] ?>">
  <td><?= $result['first_author'] ?></td>
  <td><?= $result['year'] ?></td>
  <td><?= stripslashes($result['paper_title']) ?></td>
  <td><?= $result['requested_by'] ?></td>
  </tr>

<?php
} // end of while loop

?>
</table>
<hr>
    <?php } // end of if check for rows ?>		
		
		

		
		</div> <!-- /container -->
		
		<!-- Loading Modal -->
		<div id="loadingModal" class="modal hide fade" tabindex="-1" role="dialog">
			<div class="modal-header">
				<h3>Loading Page</h3>
			</div>
			<div class="modal-body">
				<p>The page is loading, please wait.. <br>
					<small><em>(it's fetching all of the GEO and SRA numbers, which can take a while)</em></small></p>
			</div>
		</div>

		<!-- Le javascript
		================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="../js/jquery-1.8.3.min.js"></script>
		<script src="../js/bootstrap-transition.js"></script>
		<script src="../js/bootstrap-alert.js"></script>
		<script src="../js/bootstrap-modal.js"></script>
		<script src="../js/bootstrap-dropdown.js"></script>
		<script src="../js/bootstrap-scrollspy.js"></script>
		<script src="../js/bootstrap-tab.js"></script>
		<script src="../js/bootstrap-tooltip.js"></script>
		<script src="../js/bootstrap-popover.js"></script>
		<script src="../js/bootstrap-button.js"></script>
		<script src="../js/bootstrap-collapse.js"></script>
		<script src="../js/bootstrap-carousel.js"></script>
		<script src="../js/bootstrap-typeahead.js"></script>
		
		<script type="text/javascript">

			$('#papers_missing_datasets_table tr td').click(function(){
				var id = $(this).parent().attr('id');
				$('#loadingModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				window.location = '../create_dataset.php?paper_id=' + id;
			});

 $('#papers_missing_files_table tr td').click(function(){
								       var id = $(this).parent().attr('id');
								       	window.location = 'create_files.php?paper_id=' + id;
	});

// hide loading modal when the page changes (to avoid buggy back button behaviour)
$(window).unload(function(){
		   $('#loadingModal').modal('hide');
		 });

		</script>
	
  </body>
</html>
