<?php
defined('BASEPATH') or exit('No direct script access allowed');
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
    $sample = isset($sample) ? $sample : 0;
?>
<!-- add form -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">THÔNG TIN ĐƠN HÀNG SO#: <?php echo '<span style="color:blue;font-weight:bold;">' . $orders . '</span>'; ?></h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form role="form" accept-charset="utf-8" name="po_form" id="po_form" action="" method="post">
        <div class="box-footer">
            Đơn hàng này được tạo trong oracle lúc: &nbsp; <?php echo isset($orders_info[0]['CREATION_DATE']) ? date('d-M-yy h:m:s',strtotime($orders_info[0]['CREATION_DATE'])) : "" ; ?>
            <button type="submit" name="PO_SAVE" id="PO_SAVE" class="btn btn-primary" style="float:right; margin-right:40px;">
            <i class="fa fa-floppy-o" aria-hidden="true"></i>&nbsp; Save Orders</button>
        </div>
        <!-- col 1 -->
        <div class="box-body" style="float:left; width:11%;">
            <div class="form-group">
                <label for="exampleInputEmail1">NO#</label>
                <input type="text" class="form-control" name="PO_NO" id="PO_NO" placeholder="Auto load" value="<?php echo isset($orders_info[0]['PO_NO']) ? $orders_info[0]['PO_NO'] : "" ; ?>" pattern="(?=.*\d)(?=.*[-])(?=.[A-Za-z]).{12,18}" title="Chiều dài NO# từ 12 đến 18 ký tự">
            </div>
            <div class="form-group">
                <div class="form-group">
                    <label>Ngày làm đơn:</label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input type="text" class="form-control pull-right" name="CREATE_DATE" id="CREATE_DATE" value="<?php echo isset($orders_info[0]['CREATE_DATE']) ? $orders_info[0]['CREATE_DATE'] : '' ; ?>">
                    </div>
                    <!-- /.input group -->
                </div>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Tổng số lượng</label>
                <input type="text" class="form-control" name="QTY_TOTAL" id="QTY_TOTAL" placeholder="Enter qty" value="<?php //load tu js (set by id value) ?>" readonly>
            </div>
        </div>
        <!-- col 2 -->
        <div class="box-body" style="float:left; width:10%;">
            <div class="form-group">
                <label for="exampleInputEmail1">Ordered date</label>
                <input type="text" class="form-control" name="ORDERED_DATE" id="ORDERED_DATE" placeholder="Enter ordered date" value="<?php echo isset($orders_info[0]['ORDERED_DATE']) ? $orders_info[0]['ORDERED_DATE'] : ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Promise date</label>
                <input type="text" class="form-control" name="PROMISE_DATE" id="PROMISE_DATE" placeholder="Enter promise date" value="<?php echo isset($orders_info[0]['PROMISE_DATE']) ? $orders_info[0]['PROMISE_DATE'] : ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Request date</label>
                <input type="text" class="form-control" name="REQUEST_DATE" id="REQUEST_DATE" placeholder="Enter request date" value="<?php echo isset($orders_info[0]['REQUEST_DATE']) ? $orders_info[0]['REQUEST_DATE'] : ''; ?>" readonly>
            </div>
        </div>
        <!-- col 3 -->
        <div class="box-body" style="float:left; width:30%;">
            <div class="form-group">
                <label for="exampleInputEmail1">Ship to</label>
                <input type="text" class="form-control" name="SHIP_TO" id="SHIP_TO" placeholder="Enter ship to" value="<?php echo isset($orders_info[0]['SHIP_TO_CUSTOMER']) ? $orders_info[0]['SHIP_TO_CUSTOMER'] : ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Bill to</label>
                <input type="text" class="form-control" name="BILL_TO" id="BILL_TO" placeholder="Enter bill to" value="<?php echo isset($orders_info[0]['BILL_TO_CUSTOMER']) ? $orders_info[0]['BILL_TO_CUSTOMER'] : ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">RBO</label>
                <input type="text" class="form-control" name="RBO" id="RBO" placeholder="Enter EBO" value="<?php echo isset($orders_info[0]['rbo']) ? $orders_info[0]['rbo'] : ''; ?>" readonly>
            </div>
        </div>
        <!-- col 4 -->
        <div class="box-body" style="float:left; width:14%;">
            <div class="form-group">
                <label for="exampleInputEmail1">CS name</label>
                <input type="text" class="form-control" name="CS_NAME" id="CS_NAME" placeholder="Enter CS name" value="<?php echo isset($orders_info[0]['CS']) ? $orders_info[0]['CS'] : ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Label size</label>
                <input type="text" class="form-control" name="LABEL_SIZE" id="LABEL_SIZE" placeholder="Enter label size" value="<?php echo isset($orders_info[0]['length']) ? $orders_info[0]['length'] . 'mm x ' . $orders_info[0]['width'] . 'mm' : ''; ?>" readonly>
            </div>
            <!-- select -->
            <div class="form-group">
                <label>Sample</label>
                <select class="form-control" name="SAMPLE_TYPE" id="SAMPLE_TYPE">
                    <option value="NO_SAMPLE" <?php if($sample == 1){echo("selected");}?> >Đơn không mẫu</option>
                    <option value="SAMPLE" <?php if($sample == 2){echo("selected");}?> >Đơn mẫu</option>
                    <option value="HAS_SAMPLE" <?php if($sample == 3){echo("selected");}?> >Đơn có mẫu</option>
                </select>
            </div>
        </div>
        <!-- col 5 -->
        <div class="box-body" style="float:left; width:35%;">
            <div class="form-group">
                <label for="exampleInputEmail1">Remark 1</label>
                <input type="text" class="form-control" name="REMARK_1" id="REMARK_1" placeholder="Enter remark 1" value="">
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Remark 2</label>
                <input type="text" class="form-control" name="REMARK_2" id="REMARK_2" placeholder="Enter remark 2" value="">
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Remark 3</label>
                <input type="text" class="form-control" name="REMARK_3" id="REMARK_3" placeholder="Enter remark 3" value="">
            </div>
        </div>
        <!-- packing instr -->
        <div class="box-body" style="width:100%;float:left;">Packing instruction: <span style="font-weight:bold;color:blue;"><?php echo isset($orders_info[0]['PACKING_INSTR']) ? $orders_info[0]['PACKING_INSTR'] : "" ; ?></span></div>

        <!-- Get order infomation data  -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">CÁC THÔNG TIN CHI TIẾT</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table id="detail" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fa fa-check-square" aria-hidden="true"></i></th>
                                    <th>SO#</th>
                                    <th>Lines</th>
                                    <th>Qty</th>
                                    <th>Ordered Item</th>
                                    <th>Internal Item</th>

                                    <th>Material Code</th>
                                    <th>Material Description</th>
                                    <th>Material Remark</th>
                                    <th>Material Qty</th>
                                    <th>M Unit</th>
                                    <th>Ink Code</th>

                                    <th>Ink Description</th>
                                    <th>Ink Remark</th>
                                    <th>Ink Qty</th>
                                    <th>I Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                //Trường hợp chưa load được data, reresh lại. sử dụng biến count để tránh trường hợp lặp mãi
                                $refresh_count = isset($_COOKIE['refresh_count']) ? $_COOKIE['refresh_count'] : 0;
                                if (empty($orders_info)) {
                                    if ($refresh_count==0) { setcookie('refresh_count', 1, time() + 12, "/"); header("Refresh:0"); }
                                }

                                // Thay đổi giá trị material qty và ink qty sau
                                // print_r($automail);
                                $ORDER_NUMBER = $LINE_NUMBER = $QTY = $ITEM = $ORDERED_ITEM = '';
                                $material_code = $material_des = $material_remark = $ink_code = $ink_des = $ink_remark = '';
                                $PACKING_INSTR = '';
                                $QTY = $QTY_TOTAL = $MATERIAL_QTY_TOTAL = $INK_QTY_TOTAL = 0;
                                foreach ($orders_info as $key => $order_item) {
                                    $ID = $key + 1;
                                    $ORDER_NUMBER = !empty($order_item['ORDER_NUMBER']) ? $order_item['ORDER_NUMBER'] : '<span style="color:red;">No data </span>';
                                    $LINE_NUMBER = !empty($order_item['LINE_NUMBER']) ? $order_item['LINE_NUMBER'] : '<span style="color:red;">No data </span>';
                                    $QTY = !empty($order_item['QTY']) ? $order_item['QTY'] : 0;
                                    $QTY_SHOW = $QTY ? $QTY :  '<span style="color:red;">No data </span>';
                                    $ITEM = !empty($order_item['ITEM']) ? $order_item['ITEM'] : '<span style="color:red;">No data </span>';
                                    $ORDERED_ITEM = !empty($order_item['ORDERED_ITEM']) ? $order_item['ORDERED_ITEM'] : '<span style="color:red;">No data </span>';
                                    $material_code = !empty($order_item['material_code']) ? $order_item['material_code'] : '<span style="color:red;">No data </span>';
                                    $material_des = !empty($order_item['material_des']) ? $order_item['material_des'] : '<span style="color:red;">No data </span>';
                                    $material_remark = !empty($order_item['material_remark']) ? $order_item['material_remark'] : '<span style="color:red;">No data </span>';
                                    $material_qty = !empty($order_item['material_qty']) ? $order_item['material_qty'] : '<span style="color:red;">No data </span>';
                                    $material_unit = !empty($order_item['material_unit']) ? $order_item['material_unit'] : '<span style="color:red;">No data </span>';
                                    $ink_code = !empty($order_item['ink_code']) ? $order_item['ink_code'] : '<span style="color:red;">No data </span>';
                                    $ink_des = !empty($order_item['ink_des']) ? $order_item['ink_des'] : '<span style="color:red;">No data </span>';
                                    $ink_remark = !empty($order_item['ink_remark']) ? $order_item['ink_remark'] : '<span style="color:red;">No data </span>';
                                    $ink_qty = !empty($order_item['ink_qty']) ? $order_item['ink_qty'] : '<span style="color:red;">No data </span>';
                                    $ink_unit = !empty($order_item['ink_unit']) ? $order_item['ink_unit'] : '<span style="color:red;">No data </span>';
                                    $PACKING_INSTR = !empty($order_item['PACKING_INSTR']) ? trim($order_item['PACKING_INSTR']) : '<span style="color:red;">No data </span>';

                                    $material_qty_show = $material_qty ? $material_qty : '<span style="color:red;">No data </span>';
                                    $ink_qty_show = $ink_qty ? $ink_qty : '<span style="color:red;">No data </span>';
                                    echo "<tr>\n";
                                        echo "<td style='text-align:center;'>";
                                            echo "<div class='checkbox' >";
                                                echo "<label><input type='checkbox' class='flat-red' name='LINE_CHECK' id='CHECK_$ID' value='1' checked></label>";
                                            echo "</div>";
                                        echo "</td>\n";
                                        echo "<td>$ORDER_NUMBER</td>\n";
                                        echo "<td>$LINE_NUMBER</td>\n";
                                        echo "<td>$QTY_SHOW</td>\n";
                                        echo "<td>$ORDERED_ITEM</td>\n";
                                        echo "<td>$ITEM</td>\n";

                                        echo "<td>$material_code</td>\n";
                                        echo "<td>$material_des</td>\n";
                                        echo "<td>$material_remark</td>\n";
                                        echo "<td>$material_qty_show</td>\n";
                                        echo "<td>$material_unit</td>\n";
                                        echo "<td>$ink_code</td>\n";

                                        echo "<td>$ink_des</td>\n";
                                        echo "<td>$ink_remark</td>\n";
                                        echo "<td>$ink_qty_show</td>\n";
                                        echo "<td>$ink_unit</td>\n";
                                    echo "</tr>\n";

                                    // total qty
                                    $QTY_TOTAL += $QTY;
                                    $MATERIAL_QTY_TOTAL += $material_qty;
                                    $INK_QTY_TOTAL += $ink_qty;
                                }
                            ?>
                            </tfoot>
                        </table>
                    </div>
                    <!-- load size data from automail -->
                    <?php 
                        // Kiểm tra xem internal item có phải là CBS hay không, nếu đúng thì kiểm tra tiếp size
                        $cbs = isset($orders_info[0]['cbs']) ? $orders_info[0]['cbs'] : '';
                        if ($cbs == 1) {
                            if (!empty($orders_size)) {
                                echo '
                                    <div class="box-body">
                                        <table id="orders_size" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;"><i class="fa fa-list" aria-hidden="true"></i></th>
                                                    <th>SO Line</th>
                                                    <th>Size</th>
                                                    <th>Color</th>
                                                    <th>Quantity</th>
                                                    <th>Material Code </th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                    
                                    foreach ($orders_size as $key_size => $size_item) {
                                        $ID_Size = $key_size + 1;
                                        $so_line = !empty($size_item['so_line']) ? $size_item['so_line'] : '<span style="color:red;">No data </span>';
                                        $size = !empty($size_item['size']) ? $size_item['size'] : '<span style="color:red;">No data </span>';
                                        $color = !empty($size_item['color']) ? $size_item['color'] : '<span style="color:red;">No data </span>';
                                        $size_qty = !empty($size_item['qty']) ? $size_item['qty'] : '<span style="color:red;">No data </span>';
                                        $size_material_code = !empty($size_item['material_code']) ? $size_item['material_code'] : '<span style="color:red;">No data </span>';
                                        
                                        echo "<tr>\n";
                                            echo "<td>$ID_Size</td>\n";
                                            echo "<td>$so_line</td>\n";
                                            echo "<td>$size</td>\n";
                                            echo "<td>$color</td>\n";
                                            echo "<td>$size_qty</td>\n";
                                            echo "<td>$size_material_code</td>\n";
                                        echo "</tr>";
                                    }

                                echo '      </tfoot>
                                        </table>
                                    </div>';
                            } else {
                                echo '<script> alert("Không lấy được size từ automail. Vui lòng kiểm tra lại cột VIRABLE_BREAKDOWN_INSTRUCTIONS trong automail");window.location = "' . base_url() . '";</script>';
                            }
                        } //end if cbs
                        // Nếu không phải là CBS thì không hiển thị thông tin size
                    ?>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
        <!-- /.box-body -->
    </form>
</div>
<!-- load js script -->

<?php
/*
| -------------------------------------------------------------------
| Sử dụng ob_get_clean() để render to master.php
| -------------------------------------------------------------------
*/
$content = ob_get_clean();
include_once (ROOTPATH . "./thermal/master.php");
?>

<script>
    document.getElementById("QTY_TOTAL").value = <?php echo $QTY_TOTAL; ?>;
    //DataTables: xử lý phân trang table
    loadOrdersLayout();
</script>