<?php
return [
    'adminEmail' => 'admin@example.com',
    // token 有效期默认1天
    'user.apiTokenExpire' => 180*24*3600,
//    'user.apiTokenExpire' => 1*60,


//=========================================================================

    //物流费实时汇率，手动修改
    'poundRate' => 8.767, //英镑
    'auRate' => 4.8,  //澳元



    //UK虚拟仓计算参数
    'eRate' => 0.1, //eBay交易费率
    'bpRate' => 0.032, //paypal大额交易费率
    'spRate' => 0.06, //paypal小额交易费率
    'bpBasic' => 0.2, //paypal大额交易基准值（英镑）
    'spBasic' => 0.05, //paypal小额交易基准值（英镑）

    //欧速通-英伦速邮物流费参数
    'transport1' => '欧速通-英伦速邮',//物流名称
    'weight' => 150,//重量分界线（g）149
    'bwBasic' => 6.5,//超重基准值（￥）
    'swBasic' => 11.5,//未超重基准值（￥）
    'bwPrice' => 0.0715,//超重单价（￥/g）
    'swPrice' => 0.0378,//未超重单价（￥/g）

    //CNE-全球优先  280<=weight<540
    'transport2' => 'CNE-全球优先',//物流名称
    //'weight1' => 280,//重量分界线（g）
    'wBasic1' => 18,//[280,540)g基准值（￥）
    'price1' => 0.04,

    //欧速通-英伦速邮追踪物流费参数
    'transport3' => '欧速通-英伦速邮追踪',//物流名称
    //'weight2' => 540,//重量分界线（g）
    'weight3' => 2000,//重量分界线2（g）
    'weight4' => 20000,//重量分界线2（g）
    'wBasic2' => 19.8,//大于等于540g基准值（￥）
    'price2' => 0.0378,//540g-2000g单价（￥/g）
    'price3' => 0.0398,//2000g-20000g单价（￥/g）


    //英伦速邮挂号  物流费参数
    'transport5' => '英伦速邮挂号',//物流名称
    'weight5' => 150,//重量分界线（g）149
    'bwBasic5' => 11.5,//超重基准值（￥）
    'swBasic5' => 16.5,//未超重基准值（￥）
    'bwPrice5' => 0.0715,//超重单价（￥/g）
    'swPrice5' => 0.0378,//未超重单价（￥/g）


//====================================================================

    //UK真仓计算参数
    'eRate_uk' => 0.1, //eBay交易费率
    'bpRate_uk' => 0.032, //paypal大额交易费率
    'spRate_uk' => 0.06, //paypal小额交易费率
    'bpBasic_uk' => 0.2, //paypal大额交易基准值（英镑）
    'spBasic_uk' => 0.05, //paypal小额交易基准值（英镑）

    //出库费用参数
    'w_uk_out_1' => 500,//重量分界线（g）
    'w_uk_out_2' => 1000,//重量分界线（g）
    'w_uk_out_3' => 2000,//重量分界线（g）
    'w_uk_out_4' => 10000,//重量分界线（g）
    'w_uk_out_fee_1' => 0.04,//出库费用（<=500g，单位：英镑）
    'w_uk_out_fee_2' => 0.05,//出库费用（<=1000g，单位：英镑）
    'w_uk_out_fee_3' => 0.07,//出库费用（<=2000g，单位：英镑）
    'w_uk_out_fee_4' => 0.14,//出库费用（<=10000g，单位：英镑）
    'w_uk_out_fee_5' => 0.13,//出库费用（>10000g，每增加1kg的费用，向上取整，单位：英镑）

    //Royal Mail - Untracked 48 Large Letter物流费参数
    'transport_uk1' => 'Royal Mail - Untracked 48 Large Letter',//物流名称
    'w_uk_tran_1_1' => 100,//重量分界线（g）
    'w_uk_tran_1_2' => 250,//重量分界线（g）
    'w_uk_tran_1_3' => 500,//重量分界线（g）
    'w_uk_tran_1_4' => 750,//重量限制（g）,不能超过750g，超过则需要换物流方式
    'len_uk_tran' => 35.3,//长度限制（cm）,不能超过35.3cm，超过则需要换物流方式
    'wid_uk_tran' => 25,//宽度限制（cm）,不能超过25cm，超过则需要换物流方式
    'hei_uk_tran' => 2.5,//高度限制（cm）,不能超过2.5cm，超过则需要换物流方式
    'w_uk_tran_fee_1_1' => 0.91,//物流费用（<=100g，单位：英镑）
    'w_uk_tran_fee_1_2' => 1.23,//物流费用（<=250g，单位：英镑）
    'w_uk_tran_fee_1_3' => 1.31,//物流费用（<=500g，单位：英镑）
    'w_uk_tran_fee_1_4' => 1.35,//物流费用（<=750g，单位：英镑）


    //Yodel - Packet Home Mini物流费参数
    'transport_uk2' => 'Yodel - Packet Home Mini',//物流名称
    'w_uk_tran_2_1' => 2000,//重量分界线（g）
    'w_uk_tran_2_2' => 4000,//重量分界线（g）
    'w_uk_tran_fee_2_1' => 2.2,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_2_2' => 2.22,//物流费用（<=3kg，单位：英镑）

    'len_uk_tran_2' => 45,//长度限制（cm）,不能超过45cm，超过则需要换物流方式
    'wid_uk_tran_2' => 35,//宽度限制（cm）,不能超过35cm，超过则需要换物流方式
    'hei_uk_tran_2' => 20,//高度限制（cm）,不能超过20cm，超过则需要换物流方式

    //Royal Mail - Tracked 48 Parcel
    'transport_uk3' => 'Royal Mail - Tracked 48 Parcel',//物流名称
    'w_uk_tran_3_1' => 1000,//重量分界线（g）
    'w_uk_tran_3_2' => 2000,//重量分界线（g）
    'w_uk_tran_3_3' => 10000,//重量分界线（g）
    'w_uk_tran_3_4' => 15000,//重量分界线（g）
    'w_uk_tran_3_5' => 18000,//重量分界线（g）
    'w_uk_tran_3_6' => 20000,//重量分界线（g）
    'w_uk_tran_fee_3_1' => 2.45,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_2' => 2.6,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_3' => 2.7,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_4' => 2.8,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_5' => 2.9,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_6' => 4.0,//物流费用（<=3kg，单位：英镑）
    'w_uk_tran_fee_3_7' => 4.2,//物流费用（<=3kg，单位：英镑）

    'len_uk_tran_3' => 100,//长度限制（cm）,不能超过100cm，超过则需要换物流方式
    'wid_uk_tran_3' => 46,//宽度限制（cm）,不能超过46cm，超过则需要换物流方式
    'hei_uk_tran_3' => 46,//高度限制（cm）,不能超过46cm，超过则需要换物流方式


//===================================================================================
    //AU真仓计算参数
    'eRate_au' => 0.095, //eBay交易费率
    'bpRate_au' => 0.032, //paypal大额交易费率
    'spRate_au' => 0.06, //paypal小额交易费率
    'bpBasic_au' => 0.3, //paypal大额交易基准值（AU $）
    'spBasic_au' => 0.05, //paypal小额交易基准值（AU $）

    //出库费用参数
    'w_au_out_1' => 500,//重量分界线（g）
    'w_au_out_2' => 1000,//重量分界线（g）
    'w_au_out_3' => 2000,//重量分界线（g）
    'w_au_out_4' => 10000,//重量分界线（g）
    'w_au_out_fee_1' => 0.09,//出库费用（<=500g，单位：AU $）
    'w_au_out_fee_2' => 0.11,//出库费用（<=1000g，单位：AU $）
    'w_au_out_fee_3' => 0.16,//出库费用（<=2000g，单位：AU $）
    'w_au_out_fee_4' => 0.33,//出库费用（<=10000g，单位：AU $）
    'w_au_out_fee_5' => 0.28,//出库费用（>10000g，每增加1kg的费用，向上取整，单位：AU $）

    //AU Post - Untracked Large Letter物流费参数
    'transport_au1' => 'AU Post - Untracked Large Letter',//物流名称
    'w_au_tran_1_1' => 125,//重量分界线（g）
    'w_au_tran_1_2' => 250,//重量分界线（g）
    'w_au_tran_1_3' => 500,//重量限制（g）,不能超过500g，超过则需要换物流方式
    'len_au_tran' => 36,//长度限制（cm）,不能超过35.3cm，超过则需要换物流方式
    'wid_au_tran' => 26,//宽度限制（cm）,不能超过25cm，超过则需要换物流方式
    'hei_au_tran' => 2,//高度限制（cm）,不能超过2.5cm，超过则需要换物流方式
    'w_au_tran_fee_1_1' => 2.47,//物流费用（<=125g，单位：AU $）
    'w_au_tran_fee_1_2' => 3.71,//物流费用（<=250g，单位：AU $）
    'w_au_tran_fee_1_3' => 6.16,//物流费用（<=500g，单位：AU $）


    //MCS-Economy Parcel物流费参数
    'transport_au2' => 'MCS-Economy Parcel',//物流名称
    'w_au_tran_2_1' => 500,//重量分界线（g）
    'w_au_tran_2_2' => 1000,//重量分界线（g）
    'w_au_tran_2_3' => 2000,//重量分界线（g）
    'w_au_tran_2_4' => 3000,//重量分界线（g）
    'w_au_tran_2_5' => 4000,//重量分界线（g）
    'w_au_tran_2_6' => 5000,//重量分界线（g）
    'w_au_tran_fee_2_1' => 7.0,//物流费用（<=500g，单位：AU $）
    'w_au_tran_fee_2_2' => 7.44,//物流费用（<=1kg，单位：AU $）
    'w_au_tran_fee_2_3' => 7.79,//物流费用（<=2kg，单位：AU $）
    'w_au_tran_fee_2_4' => 8.07,//物流费用（<=3kg，单位：AU $）
    'w_au_tran_fee_2_5' => 9.13,//物流费用（<=4kg，单位：AU $）
    'w_au_tran_fee_2_6' => 9.55,//物流费用（<=5kg，单位：AU $）
    'w_au_tran_fee_base' => 10.95,//基本物流费用（>5kg，单位：AU $）
    'w_au_tran_fee_per' => 0.6,//超过5千克，每千克物流费用（>5kg，单位：AU $）







];
