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
    //'eRate' => 0.12, //eBay交易费率
    //'bpRate' => 0.0384, //paypal大额交易费率
    //'spRate' => 0.072, //paypal小额交易费率

    'eRate' => 0.11, //eBay交易费率


    'bpRate' => 0.032, //paypal大额交易费率
    'spRate' => 0.06, //paypal小额交易费率
    'bpBasic' => 0.2, //paypal大额交易基准值（英镑）
    'spBasic' => 0.05, //paypal小额交易基准值（英镑）

    // 托管 交易费
    'ppFee' => 0.3,
    //跨国交易费
    'tradeFee' => 0.3,

    //欧速通-英伦速邮物流费参数(半追踪)
    'transport1' => '欧速通-英伦速邮',//物流名称
    'weight' => 70,//重量分界线（g）70
    'weight1' => 2000,//重量分界线（g）
    'bwBasic' => 9.55, //超重操作费（￥）
    'swBasic' => 10.8, //未超重操作费（￥）
    'bwPrice' => 0.0815,//超重单价（￥/g）
    'swPrice' => 0.0628,//未超重单价（￥/g）

    //CNE-全球优先  280<=weight<540
    'transport2' => 'CNE-全球优先',//物流名称
    //'weight1' => 280,//重量分界线（g）
    'wBasic1' => 18,//[280,540)g基准值（￥）
    'price1' => 0.062,

    //欧速通-英伦速邮追踪物流费参数（全追踪）
    'transport3' => '欧速通-英伦速邮追踪',//物流名称
    'basic3' => 19.25, //操作费（￥）
    'weight3' => 2000,//重量分界线2（g）
    'weight4' => 20000,//重量分界线2（g）
    'price2' => 0.0628,//0-2000g单价（￥/g）
    'price3' => 0.0648,//2000g-20000g单价（￥/g）


    //英伦速递Hermes  物流费参数
    'transport5' => '欧速通-英伦速递Hermes',//物流名称
    'basic5' => 16, //操作费（￥）
    'weight5' => 2000,//重量分界线（g）2000
    'price4' => 0.063,//单价（￥/g）
    'len' => 120, //cm
    'hei_wei' => 220, //cm


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
    'w_uk_out_fee_1' => 0.24,//出库费用（<=500g，单位：英镑）
    'w_uk_out_fee_2' => 0.25,//出库费用（<=1000g，单位：英镑）
    'w_uk_out_fee_3' => 0.27,//出库费用（<=2000g，单位：英镑）
    'w_uk_out_fee_4' => 0.34,//出库费用（<=10000g，单位：英镑）
    'w_uk_out_fee_5' => 0.33,//出库费用（>10000g，每增加1kg的费用，向上取整，单位：英镑）

    //Royal Mail - Untracked 48 Large Letter物流费参数
    'transport_uk1' => 'Royal Mail - Untracked 48 Large Letter',//物流名称
    'w_uk_tran_1_1' => 100,//重量分界线（g）
    'w_uk_tran_1_2' => 250,//重量分界线（g）
    'w_uk_tran_1_3' => 500,//重量分界线（g）
    'w_uk_tran_1_4' => 750,//重量限制（g）,不能超过750g，超过则需要换物流方式
    'len_uk_tran' => 35.3,//长度限制（cm）,不能超过35.3cm，超过则需要换物流方式
    'wid_uk_tran' => 25,//宽度限制（cm）,不能超过25cm，超过则需要换物流方式
    'hei_uk_tran' => 2.5,//高度限制（cm）,不能超过2.5cm，超过则需要换物流方式
    'w_uk_tran_fee_1_1' => 1,//物流费用（<=100g，单位：英镑）
    'w_uk_tran_fee_1_2' => 1.28,//物流费用（<=250g，单位：英镑）
    'w_uk_tran_fee_1_3' => 1.36,//物流费用（<=500g，单位：英镑）
    'w_uk_tran_fee_1_4' => 1.39,//物流费用（<=750g，单位：英镑）


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


    //Hermes UK Standard 48（2-3 working days Service）
    'transport_uk4' => 'Hermes UK Standard 48（2-3 working days Service）',//物流名称
    'w_uk_tran_4_1' => 1000,//重量分界线（g）
    'w_uk_tran_4_2' => 2000,//重量分界线（g）
    'w_uk_tran_4_3' => 30000,//重量分界线（g）
    'w_uk_tran_fee_4_1' => 2.32,//物流费用（<=1kg，单位：英镑）
    'w_uk_tran_fee_4_2' => 2.47,//物流费用（<=2kg，单位：英镑）
    'w_uk_tran_fee_4_3' => 3.42,//物流费用（<=30kg，单位：英镑）

    'len_uk_tran_4' => 180,//长度限制（cm）,不能超过180cm，超过则需要换物流方式
    'w_h_uk_tran_4' => 120,//宽度+高度限制（cm）,不能超过120cm，超过则需要换物流方式
    'circum_uk_tran_4' => 420,//综合限制（cm）,L+2*（W+H）不能超过420cm，超过则需要换物流方式


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
    'w_au_tran_fee_2_1' => 6.97,//物流费用（<=500g，单位：AU $）
    'w_au_tran_fee_2_2' => 7.99,//物流费用（<=1kg，单位：AU $）
    'w_au_tran_fee_2_3' => 8.38,//物流费用（<=2kg，单位：AU $）
    'w_au_tran_fee_2_4' => 8.62,//物流费用（<=3kg，单位：AU $）
    'w_au_tran_fee_2_5' => 11.07,//物流费用（<=4kg，单位：AU $）
    'w_au_tran_fee_2_6' => 12.18,//物流费用（<=5kg，单位：AU $）
    'w_au_tran_fee_base' => 12.01,//基本物流费用（>5kg，单位：AU $）
    'w_au_tran_fee_per' => 1.35,//超过5千克，每千克物流费用（>5kg，单位：AU $）

    //AU Post - Parcel Post Parcel物流费参数
    'transport_au3' => 'AU Post - Parcel Post eParcel',//物流名称
    'w_au_tran_3_1' => 500,//重量分界线（g）
    'w_au_tran_3_2' => 1000,//重量分界线（g）
    'w_au_tran_3_3' => 2000,//重量分界线（g）
    'w_au_tran_3_4' => 3000,//重量分界线（g）
    'w_au_tran_3_5' => 4000,//重量分界线（g）
    'w_au_tran_3_6' => 5000,//重量分界线（g）
    'w_au_tran_3_7' => 7000,//重量分界线（g）
    'w_au_tran_3_8' => 10000,//重量分界线（g）
    'w_au_tran_3_9' => 15000,//重量分界线（g）
    'w_au_tran_fee_3_1' => 8.67,//物流费用（<=500g，单位：AU $）
    'w_au_tran_fee_3_2' => 10.18,//物流费用（<=1kg，单位：AU $）
    'w_au_tran_fee_3_3' => 11.23,//物流费用（<=2kg，单位：AU $）
    'w_au_tran_fee_3_4' => 12.26,//物流费用（<=3kg，单位：AU $）
    'w_au_tran_fee_3_5' => 13.32,//物流费用（<=4kg，单位：AU $）
    'w_au_tran_fee_3_6' => 14.37,//物流费用（<=5kg，单位：AU $）
    'w_au_tran_fee_3_7' => 18.06,//物流费用（<=7kg，单位：AU $）
    'w_au_tran_fee_3_8' => 21.02,//物流费用（<=10kg，单位：AU $）
    'w_au_tran_fee_3_9' => 26.25,//物流费用（<=15kg，单位：AU $）
    'w_au_tran_fee_3_10' => 34.42,//物流费用（<=22kg，单位：AU $）

    //AU Post - Express Post Parcel物流费参数
    'transport_au4' => 'AU Post - Express Post eParcel',//物流名称
    'w_au_tran_4_1' => 500,//重量分界线（g）
    'w_au_tran_4_2' => 1000,//重量分界线（g）
    'w_au_tran_4_3' => 2000,//重量分界线（g）
    'w_au_tran_4_4' => 3000,//重量分界线（g）
    'w_au_tran_4_5' => 4000,//重量分界线（g）
    'w_au_tran_4_6' => 5000,//重量分界线（g）
    'w_au_tran_4_7' => 7000,//重量分界线（g）
    'w_au_tran_4_8' => 10000,//重量分界线（g）
    'w_au_tran_4_9' => 15000,//重量分界线（g）
    'w_au_tran_fee_4_1' => 11.45,//物流费用（<=500g，单位：AU $）
    'w_au_tran_fee_4_2' => 11.99,//物流费用（<=1kg，单位：AU $）
    'w_au_tran_fee_4_3' => 12.47,//物流费用（<=2kg，单位：AU $）
    'w_au_tran_fee_4_4' => 12.96,//物流费用（<=3kg，单位：AU $）
    'w_au_tran_fee_4_5' => 13.41,//物流费用（<=4kg，单位：AU $）
    'w_au_tran_fee_4_6' => 13.89,//物流费用（<=5kg，单位：AU $）
    'w_au_tran_fee_4_7' => 14.85,//物流费用（<=7kg，单位：AU $）
    'w_au_tran_fee_4_8' => 16.27,//物流费用（<=10kg，单位：AU $）
    'w_au_tran_fee_4_9' => 18.64,//物流费用（<=15kg，单位：AU $）
    'w_au_tran_fee_4_10' => 22,//物流费用（<=22kg，单位：AU $）





];
