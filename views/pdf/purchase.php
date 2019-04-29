<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no" />
    <title>导出pdf</title>
</head>
<body>
<div style="width: 100%;">
    <h2 style="width: 100%; text-align: center; font-size: 38px; line-height: 80px; font-weight: 400; margin-top:30px;">上海真快信息技术有限公司采购订单</h2>
    <ul style="width: 100%; box-sizing: border-box; overflow: hidden; display: block; margin: 20px auto;">
        <li style=" width: 33%; float: left; list-style: none; text-align: left">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; padding-right: 10px; box-sizing: border-box;">采购单号:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block;"><?php echo $purchase['purchase_id'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">采购时间:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['purchase_time'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">到货时间:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['finish_time'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none; text-align: left">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; padding-right: 10px; box-sizing: border-box;">供应商名称:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block;"><?php echo $purchase['supplier']['supplier_name'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">供应商联系人:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['supplier']['contact_name'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">供应商电话:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['supplier']['phone'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none; text-align: left">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; padding-right: 10px; box-sizing: border-box;">收货仓库:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block;"><?php echo $purchase['warehouse']['warehouse_name'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">收货地址:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['warehouse']['address'];?></span>
        </li>
        <li style="width: 33%; float: left; list-style: none;border-color: #00aa00">
            <span style="color: #909399; font-size: 12px; width: 25%; line-height: 50px; display: inline-block; float: left; text-align: right; padding-right: 10px; box-sizing: border-box;">制单人:</span>
            <span style="color: #909399; font-size: 12px; width: 60%; line-height: 50px; display: inline-block; float: left; text-align: left;"><?php echo $purchase['operator_name'];?></span>
        </li>
    </ul>
    <h4 style="width: 100%; font-size: 16px; text-align: left; font-weight: 400;box-sizing: border-box;">商品清单:</h4>
    <table rules="all" cellspacing="0" cellpadding="0" align="center" border="0" style="border:1px solid #909399;width: 100%; display: block; margin: 15px auto; table-layout: fixed;">
        <tr>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">商品编号</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">商品名称</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">品牌</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">型号</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">单位</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">含税单价</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">数量</th>
            <th style="color: #909399; width: 13%; padding: 5px; border: 1px solid #909399;">金额</th>
        </tr>
        <?php foreach ($purchase['purchaseDetail'] as $val){?>
            <tr>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['goods_id'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['goods_name'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['brand'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['model'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['unit'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['goods']['purchase_amount'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['purchase_num'];?></td>
                <td style="color: #606266; width: 13%; padding: 5px; text-align: center; box-sizing: border-box; border: 1px solid #909399;"><?php echo $val['actual_amount'];?></td>
            </tr>
        <?php }?>

    </table>
    <div style="width: 80%; text-align: right; margin: 20px auto; font-size: 16px; font-weight: bold; color: #909399; box-sizing: border-box">
        <span style="padding-right: 30px;">实际总金额:</span> <span><?php echo $purchase['actual_amount'];?></span>元
    </div>
</div>

</body>
</html>



