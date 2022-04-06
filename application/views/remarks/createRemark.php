<?php
    $formStruct = '
        formStructRemark = [
            { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 200 },
            {
                type: "fieldset", label: "<span style=\'color:red;font-weight:bold;\'>Remarks</span>", width: 550, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                list: [
                    { type: "settings", position: "label-left", labelWidth: 120, inputWidth: 350, labelAlign: "left" },
                    { type: "select", id: "condition", name: "condition", label: "Condition Code", style: "color:red; ",  required: true, validate: "NotEmpty", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "rbo", name: "rbo", label: "RBO", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "ship_to_customer", name: "ship_to_customer", label: "Ship To Customer", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "bill_to_customer", name: "bill_to_customer", label: "Bill To Customer", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "internal_item", name: "internal_item", label: "Internal Item", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "order_item", name: "order_item", label: "Order Item", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "order_type", name: "order_type", label: "Order Type", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "material_code", name: "material_code", label: "Material Code", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "select", id: "ink_code", name: "ink_code", label: "Ink Code", options: [
                        { value: "", text: "Choose" }
                    ]},
                    { type: "input", id: "packing_instr", name: "packing_instr", label: "Packing Instr:", icon: "icon-input", className: "" },
                    { type: "input", id: "remarks", name: "remarks", label: "Remark Content:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                    // { type: "input", id: "updated_by", name: "updated_by", label: "Updated By:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", disabled: true },
                    // { type: "input", id: "updated_date", name: "updated_date", label: "Updated Date:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", disabled: true  }

                ]
            }, 
            {   type: "button", id: "updateRemark", name: "updateRemark", value: "Update", position: "label-center", width: 210, offsetLeft: 360 }
        ];
    ';