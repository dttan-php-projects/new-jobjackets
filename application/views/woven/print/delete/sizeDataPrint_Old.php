<?php 
    // width tối đa: 1024 px (1021.25). chia 3 ~ 341, chia 2 ~ 510

    function scrapTotal($target_total, $qty_size_total_total ) {
        
        $scrap_total = ( ($target_total - $qty_size_total_total) / $target_total ) * 100;
        $scrap_total = round($scrap_total,2 );
        $scrap_total = $scrap_total . ' %';

        return $scrap_total;
    }

    $qty_size_total_total = 0;
    $qty_soline_total = 0;
    $target_total = 0;
    $tong_so_cai_day_total = 0;
    $scrap_total = 0;

    $htmls .= '<div id="size-box">';
        // content
        $title = $sizeDataPrint[0];
        unset($sizeDataPrint[0]);
        $count_size = count($sizeDataPrint);
    
        $index = 0;
        foreach ($sizeDataPrint as $item) {
            
            $index++;

            if ($index == 1) {
                $size_box_id = 'left-size-box';
                $size_table_id = 'size-table-1';
            } else if ($index == 11 ) {
                $size_box_id = 'right-size-box';
                $size_table_id = 'size-table-2';
            } else if ($index == 21 ) {
                $size_box_id = 'left-size-box';
                $size_table_id = 'size-table-3';
            } else if ($index == 31 ) {
                $size_box_id = 'right-size-box';
                $size_table_id = 'size-table-4';
            }

            if ($count_size <= 10 ) {
                $widthS = '60%';
                $display = 'display: none;';
            } else {
                $widthS = '49.5%';
                $display = '';
            }


            if ($index == 1 || $index == 11 || $index == 21 || $index == 31 ) {
                $htmls .= '<div id="' . $size_box_id . '" style="width:' . $widthS . '">';
                    $htmls .= '<table id="' . $size_table_id . '" style="width:100%; font-size:10px; ">';
                        $htmls .= '<thead>';
                            $htmls .= '<tr>';
                                $htmls .= '<th style="">'. $title['index'] .'</th>';
                                $htmls .= '<th style="" >'. $title['size'] .'</th>';
                                $htmls .= '<th style="" >'. $title['qty_size_total'] .'</th>';
                                $htmls .= '<th style="">'. $title['qty_soline'] .'</th>';
                                $htmls .= '<th style="">'. $title['target'] .'</th>';
                                $htmls .= '<th style="">'. $title['tong_so_cai_day'] .'</th>';
                                $htmls .= '<th >'. $title['scrap_size'] .'</th>';
                            $htmls .= '</tr>';
                        $htmls .= '</thead>';
                        $htmls .= '<tbody>';
            }

            $qty_size_total_total += (int)$item['qty_size_total'];
            $qty_soline_total += (int)$item['qty_soline'];
            $target_total += (int)$item['target'];
            $tong_so_cai_day_total += (int)$item['tong_so_cai_day'];

            $htmls .= '<tr>';
                $htmls .= '<td class="so-line-barcode supply">'. $item['index'] .'</td>';
                $htmls .= '<td class="so-line-barcode supply">'. $item['size'] .'</td>';
                $htmls .= '<td class="so-line-barcode supply">'. number_format($item['qty_size_total']) .'</td>';
                $htmls .= '<td class="so-line-barcode supply">'. number_format($item['qty_soline']) .'</td>';
                $htmls .= '<td class="so-line-barcode supply" >'. number_format($item['target']) .'</td>';
                $htmls .= '<td class="so-line-barcode supply" >'. $item['tong_so_cai_day'] .'</td>';
                $htmls .= '<td class="so-line-barcode supply" >'. $item['scrap_size'] .'</td>';
            $htmls .= '</tr>';

            // close tab
            if ($count_size <= 10 ) {
                
                if ($index == $count_size ) {
                    $scrap_total = scrapTotal($target_total, $qty_size_total_total );

                                $htmls .= '<tr>';
                                    $htmls .= '<th colspan=2 style="">TỔNG</th>';
                                    $htmls .= '<th style="" >'. number_format($qty_size_total_total) .'</th>';
                                    $htmls .= '<th style="">'. number_format($qty_soline_total) .'</th>';
                                    $htmls .= '<th style="">'. number_format($target_total) .'</th>';
                                    $htmls .= '<th style="">'. $tong_so_cai_day_total .'</th>';
                                    $htmls .= '<th >'. $scrap_total .'</th>';
                                $htmls .= '</tr>';
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }

            } else if ($count_size > 10 && $count_size <= 20 ) {
                if ($index == 10 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }
                if ($index == $count_size ) {
                        
                    $scrap_total = scrapTotal($target_total, $qty_size_total_total );

                                $htmls .= '<tr>';
                                    $htmls .= '<th colspan=2 style="">TỔNG</th>';
                                    $htmls .= '<th style="" >'. $qty_size_total_total .'</th>';
                                    $htmls .= '<th style="">'. $qty_soline_total .'</th>';
                                    $htmls .= '<th style="">'. $target_total .'</th>';
                                    $htmls .= '<th style="">'. $tong_so_cai_day_total .'</th>';
                                    $htmls .= '<th >'. $scrap_total .'</th>';
                                $htmls .= '</tr>';
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }

            } else if ($count_size > 20 && $count_size <= 30 ) {
                
                if ($index == 10 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }
                if ($index == 20 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                    // break page
                    $htmls .= '<p style="page-break-after:always;">&nbsp;</p>';
                    $htmls .= '<hr class="box-break" style="width:100%;">';
                }
                if ($index == $count_size ) {
                    
                    $scrap_total = scrapTotal($target_total, $qty_size_total_total );

                                $htmls .= '<tr>';
                                    $htmls .= '<th colspan=2 style="">TỔNG</th>';
                                    $htmls .= '<th style="" >'. $qty_size_total_total .'</th>';
                                    $htmls .= '<th style="">'. $qty_soline_total .'</th>';
                                    $htmls .= '<th style="">'. $target_total .'</th>';
                                    $htmls .= '<th style="">'. $tong_so_cai_day_total .'</th>';
                                    $htmls .= '<th >'. $scrap_total .'</th>';
                                $htmls .= '</tr>';
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }

            } else if ($count_size > 30 && $count_size <= 40 ) {
                
                if ($index == 10 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }
                if ($index == 20 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                    // break page
                    $htmls .= '<p style="page-break-after:always;">&nbsp;</p>';
                    $htmls .= '<hr class="box-break" style="width:100%;">';
                }

                if ($index == 30 ) {
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }
                
                if ($index == $count_size ) {
                    
                    $scrap_total = scrapTotal($target_total, $qty_size_total_total );

                                $htmls .= '<tr>';
                                    $htmls .= '<th colspan=2 style="">TỔNG</th>';
                                    $htmls .= '<th style="" >'. $qty_size_total_total .'</th>';
                                    $htmls .= '<th style="">'. $qty_soline_total .'</th>';
                                    $htmls .= '<th style="">'. $target_total .'</th>';
                                    $htmls .= '<th style="">'. $tong_so_cai_day_total .'</th>';
                                    $htmls .= '<th >'. $scrap_total .'</th>';
                                $htmls .= '</tr>';
                            $htmls .= '</tbody>';
                        $htmls .= '</table>';
                    $htmls .= '</div>';
                }

            }
            
        } // for end style="width:23%;"

    $htmls .= '</div>';
?>

