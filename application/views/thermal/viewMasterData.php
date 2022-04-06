<?php
defined('BASEPATH') or exit('No direct script access allowed');
if (!isset($_COOKIE['LoginUser']) || strtolower($_COOKIE['department']) != 'thermal') {
	exit('No direct script access allowed');
}
//file chứa các hàm JS
// include_once(ROOTPATH . "/tandoan/planning/api/config/JSfunction.php");
/*
| -------------------------------------------------------------------
| Sử dụng ob_start() để bắt đầu lấy dữ liệu để render to master.php
| -------------------------------------------------------------------
*/
ob_start();

/*
| -------------------------------------------------------------------
| Nội dung cần hiển thị trong master.php. Xử lý tại đây
| -------------------------------------------------------------------
*/
?>
<!-- Get header table  -->
<div class="row">
	<div class="col-xs-12">
		<div class="box">
			<div class="box-header">
				<h3 class="box-title"><?php echo 'Master Data Views'; ?></h3>
			</div>
			<!-- /.box-header -->
			<div class="box-body">
				<table id="example1" class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>TT</th>
							<th>form_type</th>
                            <th>internal_item</th>
                            
							<th>material_code</th>
							<th>material_des</th>
                            <th>material_remark</th>
                            <th>material_unit</th>

                            <th>ink_code</th>
							<th>ink_des</th>
                            <th>ink_remark</th>
                            <th>ink_unit</th>

							<th>rbo</th>
							<th>rbo_remark</th>
							<th>kind_of_label</th>
							<th>length</th>
                            <th>width</th>
                            
                            <th>cbs</th>
							<th>roll_qty</th>
							<th>ups</th>
                            <th>updated_by</th>
                            <th>updated_date</th>
						</tr>
					</thead>
					<tbody>
					<?php
                        $index = 1;
						foreach ($dataInfo as $item) {
                            
                            echo "<tr>";
                                echo "<td>$index</td>";
								echo "<td>$item[form_type]</td>";
								echo "<td>$item[internal_item]</td>";
                                
                                echo "<td>$item[material_code]</td>";
								echo "<td>$item[material_des]</td>";
								echo "<td>$item[material_remark]</td>";
                                echo "<td>$item[material_unit]</td>";
                                
								echo "<td>$item[ink_code]</td>";
                                echo "<td>$item[ink_des]</td>";
                                echo "<td>$item[ink_remark]</td>";
                                echo "<td>$item[ink_unit]</td>";
                                
								echo "<td>$item[rbo]</td>";
								echo "<td>$item[rbo_remark]</td>";
								echo "<td>$item[kind_of_label]</td>";
								echo "<td>$item[length]</td>";
                                echo "<td>$item[width]</td>";
                                
                                echo "<td>$item[cbs]</td>";
                                echo "<td>$item[roll_qty]</td>";
								echo "<td>$item[ups]</td>";
								echo "<td>$item[updated_by]</td>";
								echo "<td>$item[updated_date]</td>";
                            echo "</tr>";
                            $index++;
						}
					?>
					</tfoot>
				</table>
			</div>
			<!-- /.box-body -->
		</div>
		<!-- /.box -->
	</div>
	<!-- /.col -->
</div>
<!-- /.row -->
<?php
	
/*
| -------------------------------------------------------------------
| Sử dụng ob_get_clean() để render to master.php
| -------------------------------------------------------------------
*/
$content = ob_get_clean();
include_once(ROOTPATH . "/tandoan/planning/master.php");
?>

<script>
	//DataTables: xử lý phân trang table
	$(function() {
		$('#example1').DataTable()
		$('#example2').DataTable({
			'paging'      : true,
			'lengthChange': true, 
			'searching'   : true,
			'ordering'    : false,
			'info'        : true,
			'autoWidth'   : false
		})
	})

	function delete_confirm(po_no) {
		if (!window.confirm('Bạn chắc chắn muốn xóa No: ?'+po_no)) {
			return false;
		} else {

		}
	}

	

</script>